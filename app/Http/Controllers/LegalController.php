<?php

namespace App\Http\Controllers;

use App\Models\AllotmentCase;
use App\Models\CaseMilestone;
use App\Services\AuditService;
use App\Services\CaseLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LegalController extends Controller
{
    public function issueNotice(Request $request, string $caseId): RedirectResponse
    {
        $data = $request->validate(['remarks' => ['nullable', 'string', 'max:2000']]);
        $case = $this->openCase($caseId);
        $case->legal_status = 'notice_issued';
        $case->legal_remarks = $data['remarks'] ?? null;
        $case->save();
        AuditService::log($caseId, 'notice_issued', 'allotment_case', $caseId, ['remarks' => $data['remarks'] ?? null]);

        return back()->with('success', 'Legal notice issued.');
    }

    public function grantExtension(Request $request, string $caseId, CaseLifecycleService $caseLifecycleService): RedirectResponse
    {
        $data = $request->validate([
            'milestone_id' => ['required', 'string'],
            'extension_days' => ['required', 'integer', 'min:1', 'max:1825'],
        ]);

        $case = $this->openCase($caseId);
        // Make sure the milestone actually belongs to this case.
        $milestone = CaseMilestone::where('_id', $data['milestone_id'])->where('allotment_case_id', $caseId)->firstOrFail();

        if ($milestone->status === 'verified') {
            return back()->withErrors(['milestone_id' => 'This milestone is already verified; no extension is needed.']);
        }

        $milestone->due_date = $milestone->due_date->copy()->addDays((int) $data['extension_days']);
        $milestone->status = $milestone->due_date->isPast() ? 'overdue' : 'pending';
        // Reset alert markers so the scheduler warns again against the new date.
        $milestone->warning_sent_at = null;
        $milestone->overdue_alert_sent_at = null;
        $milestone->save();

        $case->legal_status = 'extension_granted';
        $case->save();
        $caseLifecycleService->updateCaseStatus($case);

        AuditService::log($caseId, 'extension_granted', 'case_milestone', (string) $milestone->_id, $data + [
            'new_due_date' => $milestone->due_date->toDateString(),
        ]);

        return back()->with('success', 'Extension granted. New due date: '.$milestone->due_date->format('d M Y').'.');
    }

    public function flagLegalReview(string $caseId): RedirectResponse
    {
        $case = $this->openCase($caseId);
        $case->legal_status = 'legal_review';
        $case->save();
        AuditService::log($caseId, 'legal_review_flagged', 'allotment_case', $caseId);

        return back()->with('success', 'Case flagged for legal review.');
    }

    public function terminate(Request $request, string $caseId, CaseLifecycleService $caseLifecycleService): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        $case = $this->openCase($caseId);
        $case->legal_status = 'terminated';
        $case->legal_remarks = $data['reason'];
        $caseLifecycleService->updateCaseStatus($case);
        AuditService::log($caseId, 'case_terminated', 'allotment_case', $caseId, ['reason' => $data['reason']]);

        return back()->with('success', 'Case terminated and enforcement action logged.');
    }

    private function openCase(string $caseId): AllotmentCase
    {
        $case = AllotmentCase::findOrFail($caseId);
        if ($case->isClosed()) {
            throw ValidationException::withMessages(['case' => 'This case is already closed or terminated; no further legal action can be taken.']);
        }

        return $case;
    }
}
