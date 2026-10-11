<?php

namespace App\Http\Controllers;

use App\Models\AllotmentCase;
use App\Models\CaseMilestone;
use App\Models\IncentiveClaim;
use App\Models\Submission;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function monthly()
    {
        $monthStart = now()->startOfMonth();
        $cases = AllotmentCase::orderBy('created_at', 'desc')->get();
        // Previously referenced in the view but never passed, which crashed the report.
        $submissions = Submission::where('submitted_at', '>=', $monthStart)->get(['status']);
        $claimsPaid = IncentiveClaim::where('status', 'paid')->where('paid_at', '>=', $monthStart)->get(['amount']);
        $statusCounts = $cases->countBy('status');

        return Pdf::loadView('pdf.monthly-report', compact('cases', 'submissions', 'claimsPaid', 'statusCounts', 'monthStart'))
            ->download('monthly-compliance-report-'.$monthStart->format('Y-m').'.pdf');
    }

    public function nonCompliance()
    {
        $cases = AllotmentCase::whereIn('status', ['non_compliant', 'terminated', 'at_risk'])->orderBy('created_at', 'desc')->get();
        $overdue = CaseMilestone::whereIn('allotment_case_id', $cases->map(fn ($c) => (string) $c->_id)->all())
            ->whereIn('status', ['overdue', 'due_soon'])
            ->orderBy('due_date')
            ->get()
            ->groupBy('allotment_case_id');

        return Pdf::loadView('pdf.non-compliance-report', compact('cases', 'overdue'))->download('non-compliance-report-'.now()->format('Y-m-d').'.pdf');
    }

    public function certificate(string $caseId)
    {
        $case = AllotmentCase::findOrFail($caseId);

        if ($case->status !== 'fully_compliant') {
            return back()->withErrors(['certificate' => 'A compliance certificate can only be issued once every milestone is verified.']);
        }

        $milestones = CaseMilestone::where('allotment_case_id', $caseId)->orderBy('due_date')->get();

        return Pdf::loadView('pdf.final-certificate', compact('case', 'milestones'))->download('compliance-certificate-'.$caseId.'.pdf');
    }
}
