<?php

namespace App\Http\Controllers;

use App\Models\AllotmentCase;
use App\Models\CaseMilestone;
use App\Models\Submission;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CaseLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SubmissionController extends Controller
{
    public const STATUSES = ['submitted', 'accepted', 'returned'];

    public function queue(Request $request)
    {
        $status = $request->input('status', 'submitted');
        $query = Submission::query();
        if (in_array($status, self::STATUSES, true)) {
            $query->where('status', $status);
        }

        $submissions = $query->orderBy('submitted_at', 'desc')->paginate(20)->withQueryString();
        $caseTitles = $this->caseTitles($submissions->pluck('allotment_case_id'));

        return view('submissions.index', compact('submissions', 'caseTitles', 'status'));
    }

    public function create(string $caseId)
    {
        $case = $this->ownedOpenCase($caseId);
        $milestones = CaseMilestone::where('allotment_case_id', $caseId)->orderBy('due_date')->get();
        $lastSubmission = Submission::where('allotment_case_id', $caseId)->orderBy('submitted_at', 'desc')->first();

        return view('submissions.create', compact('case', 'milestones', 'lastSubmission'));
    }

    public function store(Request $request, string $caseId): RedirectResponse
    {
        $case = $this->ownedOpenCase($caseId);

        $validated = $request->validate([
            'milestone_id' => ['nullable', 'string'],
            'investment_spent' => ['required', 'numeric', 'min:0'],
            'employees_hired' => ['required', 'integer', 'min:0'],
            'construction_status' => ['required', 'string', Rule::in(array_keys(Submission::CONSTRUCTION_STAGES))],
            'construction_progress' => ['required', 'numeric', 'min:0', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'evidence_files' => ['nullable', 'array', 'max:10'],
            'evidence_files.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        if (! empty($validated['milestone_id'])
            && ! CaseMilestone::where('_id', $validated['milestone_id'])->where('allotment_case_id', $caseId)->exists()) {
            return back()->withInput()->withErrors(['milestone_id' => 'Selected milestone does not belong to this case.']);
        }

        $files = [];
        foreach ($request->file('evidence_files', []) as $file) {
            $files[] = $file->store('submission-evidence', 'public');
        }

        $progress = $validated['construction_status'] === 'completed' ? 100 : (float) $validated['construction_progress'];

        $submission = Submission::create([
            'allotment_case_id' => $caseId,
            'milestone_id' => $validated['milestone_id'] ?? null,
            'submitted_by' => (string) Auth::id(),
            'investment_spent' => (float) $validated['investment_spent'],
            'employees_hired' => (int) $validated['employees_hired'],
            'construction_status' => $validated['construction_status'],
            'construction_progress' => $progress,
            'remarks' => $validated['remarks'] ?? null,
            'evidence_files' => $files,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        AuditService::log($caseId, 'submission_created', 'submission', (string) $submission->_id, [
            'files' => count($files),
        ]);

        return redirect()->route('cases.show', $case->_id)->with('success', 'Progress submitted successfully. An officer will review it shortly.');
    }

    public function review(string $submissionId)
    {
        $submission = Submission::findOrFail($submissionId);
        $case = AllotmentCase::find($submission->allotment_case_id);
        $milestones = CaseMilestone::where('allotment_case_id', $submission->allotment_case_id)->orderBy('due_date')->get();
        $submitter = $submission->submitted_by ? User::find((int) $submission->submitted_by) : null;

        return view('submissions.review', compact('submission', 'case', 'milestones', 'submitter'));
    }

    public function accept(string $submissionId, CaseLifecycleService $caseLifecycleService): RedirectResponse
    {
        $submission = Submission::findOrFail($submissionId);
        if ($submission->status !== 'submitted') {
            return back()->withErrors(['submission' => 'This submission has already been '.$submission->status.'.']);
        }

        $case = AllotmentCase::findOrFail($submission->allotment_case_id);
        if ($case->isClosed()) {
            return back()->withErrors(['submission' => 'This case is closed; submissions can no longer be accepted.']);
        }

        $submission->status = 'accepted';
        $submission->reviewed_by = (string) Auth::id();
        $submission->reviewed_at = now();
        $submission->save();

        $milestones = CaseMilestone::where('allotment_case_id', $submission->allotment_case_id)->get();
        $verified = $caseLifecycleService->applySubmission($submission, $milestones, (string) Auth::id());
        $caseLifecycleService->updateCaseStatus($case, $milestones);

        AuditService::log($submission->allotment_case_id, 'submission_accepted', 'submission', (string) $submission->_id, [
            'milestones_verified' => $verified,
        ]);

        return redirect()->route('cases.show', $case->_id)->with('success', "Submission accepted. {$verified} milestone(s) newly verified.");
    }

    public function returnWithRemarks(Request $request, string $submissionId): RedirectResponse
    {
        $request->validate(['review_remarks' => ['required', 'string', 'max:2000']]);
        $submission = Submission::findOrFail($submissionId);
        if ($submission->status !== 'submitted') {
            return back()->withErrors(['submission' => 'This submission has already been '.$submission->status.'.']);
        }

        $submission->status = 'returned';
        $submission->reviewed_by = (string) Auth::id();
        $submission->reviewed_at = now();
        $submission->review_remarks = $request->input('review_remarks');
        $submission->save();

        AuditService::log($submission->allotment_case_id, 'submission_returned', 'submission', (string) $submission->_id, [
            'remarks' => $submission->review_remarks,
        ]);

        return redirect()->route('cases.show', $submission->allotment_case_id)->with('success', 'Submission returned with remarks.');
    }

    /**
     * An allottee may only submit progress for their own, still-open case.
     */
    private function ownedOpenCase(string $caseId): AllotmentCase
    {
        $case = AllotmentCase::findOrFail($caseId);
        abort_unless((string) $case->allottee_user_id === (string) Auth::id(), 403, 'This case is not assigned to you.');
        abort_if($case->isClosed(), 403, 'This case is closed and no longer accepts submissions.');

        return $case;
    }

    private function caseTitles($caseIds)
    {
        $ids = collect($caseIds)->filter()->unique()->values()->all();

        return AllotmentCase::whereIn('_id', $ids)->get(['title'])->mapWithKeys(fn ($c) => [(string) $c->_id => $c->title]);
    }
}
