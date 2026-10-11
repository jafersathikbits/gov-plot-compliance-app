<?php

namespace App\Http\Controllers;

use App\Models\AllotmentCase;
use App\Models\AuditLog;
use App\Models\CaseMilestone;
use App\Models\IncentiveClaim;
use App\Models\Plot;
use App\Models\PolicyTemplate;
use App\Models\Submission;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CaseLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MongoDB\BSON\Regex;

class CaseSetupController extends Controller
{
    public function index(Request $request)
    {
        $query = AllotmentCase::visibleTo(Auth::user());

        if ($request->filled('status') && in_array($request->input('status'), AllotmentCase::STATUSES, true)) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('q')) {
            $query->where('title', 'regex', new Regex(preg_quote((string) $request->input('q')), 'i'));
        }

        $cases = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $plots = Plot::whereIn('_id', $cases->pluck('plot_id')->filter()->unique()->values()->all())->get()->keyBy(fn ($p) => (string) $p->_id);

        return view('cases.index', compact('cases', 'plots'));
    }

    public function create()
    {
        return view('cases.create', $this->assignableUsers() + [
            'templates' => PolicyTemplate::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, CaseLifecycleService $caseLifecycleService): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'plot_number' => ['required', 'string', 'max:100'],
            'area' => ['required', 'numeric', 'gt:0'],
            'lease_duration_years' => ['required', 'integer', 'min:1', 'max:99'],
            'project_type' => ['required', 'string', 'max:255'],
            'required_investment' => ['required', 'numeric', 'min:0'],
            'employment_commitment' => ['required', 'integer', 'min:0'],
            'construction_deadline' => ['required', 'date', 'after:today'],
            'subsidy_amount' => ['nullable', 'numeric', 'min:0'],
            'caution_deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'allottee_user_id' => ['required', 'integer', 'exists:users,id'],
            'district_officer_id' => ['nullable', 'integer', 'exists:users,id'],
            'inspection_officer_id' => ['nullable', 'integer', 'exists:users,id'],
            'policy_template_id' => ['required', 'string'],
            'allotment_order' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'lease_deed' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        // Resolve the template before writing anything so a bad id does not leave orphan plot/case records.
        $template = PolicyTemplate::find($validated['policy_template_id']);
        if (! $template) {
            return back()->withInput()->withErrors(['policy_template_id' => 'Selected policy template was not found.']);
        }

        $plot = Plot::create([
            'plot_number' => $validated['plot_number'],
            'area' => (float) $validated['area'],
            'lease_duration_years' => (int) $validated['lease_duration_years'],
            'project_type' => $validated['project_type'],
            'required_investment' => (float) $validated['required_investment'],
            'employment_commitment' => (int) $validated['employment_commitment'],
            'construction_deadline' => $validated['construction_deadline'],
            'financial_conditions' => [
                'subsidy_amount' => (float) ($validated['subsidy_amount'] ?? 0),
                'caution_deposit_amount' => (float) ($validated['caution_deposit_amount'] ?? 0),
            ],
            'status' => 'registered',
        ]);

        $documents = [];
        foreach (['allotment_order', 'lease_deed'] as $doc) {
            if ($request->hasFile($doc)) {
                $documents[$doc] = $request->file($doc)->store('case-documents', 'public');
            }
        }

        $case = AllotmentCase::create([
            'plot_id' => (string) $plot->_id,
            'allottee_user_id' => (string) $validated['allottee_user_id'],
            'policy_template_id' => (string) $template->_id,
            'title' => $validated['title'],
            'status' => 'active',
            'investment_required' => (float) $validated['required_investment'],
            'employment_required' => (int) $validated['employment_commitment'],
            'construction_deadline' => $validated['construction_deadline'],
            'subsidy_amount' => (float) ($validated['subsidy_amount'] ?? 0),
            'caution_deposit_amount' => (float) ($validated['caution_deposit_amount'] ?? 0),
            'documents' => $documents,
            'district_officer_id' => ! empty($validated['district_officer_id']) ? (string) $validated['district_officer_id'] : null,
            'inspection_officer_id' => ! empty($validated['inspection_officer_id']) ? (string) $validated['inspection_officer_id'] : null,
            'legal_status' => 'none',
            'is_archived' => false,
            'activated_at' => now(),
        ]);

        $caseLifecycleService->generateMilestones($case, $template);

        AuditService::log((string) $case->_id, 'case_created_and_activated', 'allotment_case', (string) $case->_id, [
            'plot' => $validated['plot_number'],
        ]);

        return redirect()->route('cases.show', $case->_id)->with('success', 'Case created, timeline generated, and case activated.');
    }

    public function show(string $caseId)
    {
        $case = AllotmentCase::findOrFail($caseId);
        abort_unless($case->isVisibleTo(Auth::user()), 403);

        $plot = Plot::find($case->plot_id);
        $template = $case->policy_template_id ? PolicyTemplate::find($case->policy_template_id) : null;
        $milestones = CaseMilestone::where('allotment_case_id', $caseId)->orderBy('due_date')->get();
        $submissions = Submission::where('allotment_case_id', $caseId)->orderBy('submitted_at', 'desc')->get();
        $claims = IncentiveClaim::where('allotment_case_id', $caseId)->orderBy('created_at', 'desc')->get();

        $isAdmin = Auth::user()->hasAnyRole(['super_admin', 'state_admin']);
        $auditLogs = Auth::user()->hasRole('allottee') && ! $isAdmin
            ? collect()
            : AuditLog::where('allotment_case_id', $caseId)->orderBy('logged_at', 'desc')->limit(20)->get();

        // One query for every user referenced on the page.
        $userIds = collect([$case->allottee_user_id, $case->district_officer_id, $case->inspection_officer_id])
            ->merge($auditLogs->pluck('user_id'))
            ->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $people = User::whereIn('id', $userIds)->get()->keyBy(fn ($u) => (string) $u->id);

        return view('cases.show', compact('case', 'plot', 'template', 'milestones', 'submissions', 'claims', 'auditLogs', 'people')
            + ($isAdmin ? $this->assignableUsers() : ['allottees' => collect(), 'districtOfficers' => collect(), 'inspectionOfficers' => collect()]));
    }

    public function assign(Request $request, string $caseId): RedirectResponse
    {
        $data = $request->validate([
            'allottee_user_id' => ['required', 'integer', 'exists:users,id'],
            'district_officer_id' => ['nullable', 'integer', 'exists:users,id'],
            'inspection_officer_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $case = AllotmentCase::findOrFail($caseId);
        $case->allottee_user_id = (string) $data['allottee_user_id'];
        $case->district_officer_id = ! empty($data['district_officer_id']) ? (string) $data['district_officer_id'] : null;
        $case->inspection_officer_id = ! empty($data['inspection_officer_id']) ? (string) $data['inspection_officer_id'] : null;

        if (! $case->isDirty()) {
            return back()->with('success', 'No assignment changes to save.');
        }

        $case->save();

        AuditService::log((string) $case->_id, 'officers_assigned', 'allotment_case', (string) $case->_id, $data);
        return back()->with('success', 'Assignments updated.');
    }

    public function close(string $caseId, CaseLifecycleService $caseLifecycleService): RedirectResponse
    {
        $case = AllotmentCase::findOrFail($caseId);
        $caseLifecycleService->updateCaseStatus($case);

        if ($case->status !== 'fully_compliant') {
            return back()->with('warning', 'Case is not yet fully compliant; all milestones must be verified before closure.');
        }

        if (! $case->is_archived) {
            $case->is_archived = true;
            $case->save();
            AuditService::log((string) $case->_id, 'case_archived', 'allotment_case', (string) $case->_id);
        }

        return back()->with('success', 'Case is fully compliant and has been closed and archived.');
    }

    private function assignableUsers(): array
    {
        // Single query for all role-holders instead of three.
        $users = User::role(['allottee', 'district_officer', 'inspection_officer'])->with('roles')->orderBy('name')->get();
        $byRole = fn (string $role) => $users->filter(fn ($u) => $u->roles->contains('name', $role))->values();

        return [
            'allottees' => $byRole('allottee'),
            'districtOfficers' => $byRole('district_officer'),
            'inspectionOfficers' => $byRole('inspection_officer'),
        ];
    }
}
