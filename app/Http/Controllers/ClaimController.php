<?php

namespace App\Http\Controllers;

use App\Models\AllotmentCase;
use App\Models\IncentiveClaim;
use App\Services\AuditService;
use App\Services\CaseLifecycleService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClaimController extends Controller
{
    public const STATUSES = ['submitted', 'clarification_required', 'paid'];

    public function index(Request $request)
    {
        $status = $request->input('status', 'submitted');
        $query = IncentiveClaim::query();
        if (in_array($status, self::STATUSES, true)) {
            $query->where('status', $status);
        }

        $claims = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        $caseTitles = AllotmentCase::whereIn('_id', $claims->pluck('allotment_case_id')->filter()->unique()->values()->all())
            ->get(['title'])
            ->mapWithKeys(fn ($c) => [(string) $c->_id => $c->title]);

        return view('claims.index', compact('claims', 'caseTitles', 'status'));
    }

    public function create(string $caseId, CaseLifecycleService $caseLifecycleService)
    {
        $case = AllotmentCase::findOrFail($caseId);
        $eligible = $caseLifecycleService->verifyEligibleForClaim($case);
        $limits = $this->remainingLimits($case);

        return view('claims.create', compact('case', 'eligible', 'limits'));
    }

    public function store(Request $request, string $caseId, CaseLifecycleService $caseLifecycleService): RedirectResponse
    {
        $case = AllotmentCase::findOrFail($caseId);

        if (! $caseLifecycleService->verifyEligibleForClaim($case)) {
            return back()->withInput()->withErrors(['claim' => 'Case is not yet eligible for refund or subsidy claim. At least one milestone must be verified and the case must not be non-compliant or terminated.']);
        }

        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(IncentiveClaim::TYPES))],
            'amount' => ['required', 'numeric', 'gt:0'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        // Caution deposit refunds are released only once every obligation is met.
        if ($validated['type'] === 'refund' && $case->status !== 'fully_compliant') {
            return back()->withInput()->withErrors(['type' => 'Caution deposit can only be refunded once the case is fully compliant.']);
        }

        $remaining = $this->remainingLimits($case)[$validated['type']];
        if ($remaining !== null && (float) $validated['amount'] > $remaining) {
            return back()->withInput()->withErrors(['amount' => 'Amount exceeds the remaining entitlement of ₹'.number_format($remaining, 2).'.']);
        }

        $claim = IncentiveClaim::create([
            'allotment_case_id' => $caseId,
            'type' => $validated['type'],
            'amount' => (float) $validated['amount'],
            'status' => 'submitted',
            'submitted_by' => (string) Auth::id(),
            'remarks' => $validated['remarks'] ?? null,
        ]);

        AuditService::log($caseId, 'claim_initiated', 'incentive_claim', (string) $claim->_id, $validated);

        return redirect()->route('claims.review', $claim->_id)->with('success', 'Claim initiated.');
    }

    public function review(string $claimId)
    {
        $claim = IncentiveClaim::findOrFail($claimId);
        $case = AllotmentCase::find($claim->allotment_case_id);

        return view('claims.review', compact('claim', 'case'));
    }

    public function approve(Request $request, string $claimId): RedirectResponse
    {
        $request->validate(['payment_reference' => ['nullable', 'string', 'max:100']]);

        $claim = IncentiveClaim::findOrFail($claimId);
        if ($claim->status !== 'submitted') {
            return back()->withErrors(['claim' => 'Only submitted claims can be approved (current status: '.str_replace('_', ' ', $claim->status).').']);
        }

        $claim->status = 'paid';
        $claim->reviewed_by = (string) Auth::id();
        // filled() so an empty input still gets a generated reference (input() would return null here).
        $claim->payment_reference = $request->filled('payment_reference')
            ? $request->input('payment_reference')
            : 'PAY-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        $claim->approved_at = now();
        $claim->paid_at = now();
        $claim->save();

        AuditService::log($claim->allotment_case_id, 'claim_approved_and_paid', 'incentive_claim', (string) $claim->_id, [
            'payment_reference' => $claim->payment_reference,
        ]);

        return redirect()->route('claims.review', $claim->_id)->with('success', 'Claim approved and payment recorded. The approval memo is ready to download.');
    }

    public function clarify(Request $request, string $claimId): RedirectResponse
    {
        $request->validate(['remarks' => ['required', 'string', 'max:2000']]);
        $claim = IncentiveClaim::findOrFail($claimId);
        if ($claim->status !== 'submitted') {
            return back()->withErrors(['claim' => 'Only submitted claims can be returned for clarification.']);
        }

        $claim->status = 'clarification_required';
        $claim->reviewed_by = (string) Auth::id();
        $claim->remarks = $request->input('remarks');
        $claim->save();

        AuditService::log($claim->allotment_case_id, 'claim_returned_for_clarification', 'incentive_claim', (string) $claim->_id, [
            'remarks' => $claim->remarks,
        ]);

        return redirect()->route('cases.show', $claim->allotment_case_id)->with('success', 'Claim returned for clarification.');
    }

    public function memoPdf(string $claimId)
    {
        $claim = IncentiveClaim::findOrFail($claimId);
        abort_unless($claim->status === 'paid', 404, 'An approval memo is only available for approved claims.');

        $case = AllotmentCase::find($claim->allotment_case_id);
        $pdf = Pdf::loadView('pdf.approval-memo', compact('claim', 'case'));

        return $pdf->download('approval-memo-'.$claimId.'.pdf');
    }

    /**
     * Remaining amount that can still be claimed per type (null = no cap configured on the case).
     */
    private function remainingLimits(AllotmentCase $case): array
    {
        $committed = IncentiveClaim::where('allotment_case_id', (string) $case->_id)
            ->whereIn('status', ['submitted', 'paid'])
            ->get(['type', 'amount'])
            ->groupBy('type')
            ->map(fn ($claims) => $claims->sum('amount'));

        $limit = fn (float $cap, string $type) => $cap > 0 ? max(0, $cap - (float) $committed->get($type, 0)) : null;

        return [
            'subsidy' => $limit((float) $case->subsidy_amount, 'subsidy'),
            'refund' => $limit((float) $case->caution_deposit_amount, 'refund'),
        ];
    }
}
