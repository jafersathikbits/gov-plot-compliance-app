<?php

namespace App\Http\Controllers;

use App\Models\AllotmentCase;
use App\Models\CaseMilestone;
use App\Models\IncentiveClaim;
use App\Models\Plot;
use App\Models\Submission;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $isAllottee = $user->hasRole('allottee') && ! $user->hasAnyRole(['super_admin', 'state_admin', 'district_officer', 'inspection_officer']);

        // Only the status field is needed for the counters.
        $statusCounts = AllotmentCase::visibleTo($user)->get(['status'])->countBy('status');
        $cases = AllotmentCase::visibleTo($user)->orderBy('created_at', 'desc')->limit(10)->get();

        // Allottees only ever see data that belongs to their own cases.
        $caseIds = $isAllottee
            ? AllotmentCase::visibleTo($user)->get(['_id'])->map(fn ($c) => (string) $c->_id)->all()
            : null;
        $scope = fn ($query) => $caseIds === null ? $query : $query->whereIn('allotment_case_id', $caseIds);

        $stats = [
            'cases' => ['label' => 'Total cases', 'value' => $statusCounts->sum(), 'tone' => 'slate'],
            'compliant' => ['label' => 'Fully compliant', 'value' => $statusCounts->get('fully_compliant', 0), 'tone' => 'emerald'],
            'at_risk' => ['label' => 'At risk', 'value' => $statusCounts->get('at_risk', 0), 'tone' => 'amber'],
            'non_compliant' => ['label' => 'Non-compliant / terminated', 'value' => $statusCounts->get('non_compliant', 0) + $statusCounts->get('terminated', 0), 'tone' => 'rose'],
            'upcoming_deadlines' => ['label' => 'Due soon / overdue milestones', 'value' => $scope(CaseMilestone::whereIn('status', ['due_soon', 'overdue']))->count(), 'tone' => 'amber'],
            'pending_submissions' => ['label' => 'Submissions awaiting review', 'value' => $scope(Submission::where('status', 'submitted'))->count(), 'tone' => 'sky'],
            'pending_claims' => ['label' => 'Claims awaiting decision', 'value' => $scope(IncentiveClaim::where('status', 'submitted'))->count(), 'tone' => 'violet'],
            'plots' => ['label' => 'Registered plots', 'value' => $isAllottee ? $statusCounts->sum() : Plot::count(), 'tone' => 'slate'],
        ];

        $milestones = $scope(CaseMilestone::where('status', '!=', 'verified'))->orderBy('due_date')->limit(8)->get();
        $milestoneCaseTitles = AllotmentCase::whereIn('_id', $milestones->pluck('allotment_case_id')->unique()->values()->all())
            ->get(['title'])
            ->mapWithKeys(fn ($c) => [(string) $c->_id => $c->title]);

        $statusChart = collect(AllotmentCase::STATUSES)->mapWithKeys(fn ($s) => [$s => $statusCounts->get($s, 0)]);

        return view('dashboard', compact('user', 'cases', 'stats', 'milestones', 'milestoneCaseTitles', 'statusChart', 'isAllottee'));
    }
}
