@extends('layouts.app')

@section('content')
<x-page-header title="Claims queue" subtitle="Land cost subsidy and caution deposit refund claims." />

<x-status-tabs :statuses="\App\Http\Controllers\ClaimController::STATUSES" :current="$status" route="claims.index" />

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Case</th><th>Type</th><th class="text-right">Amount</th><th>Status</th><th>Raised</th><th></th></tr></thead>
            <tbody>
                @forelse($claims as $claim)
                    <tr>
                        <td><a href="{{ route('cases.show', $claim->allotment_case_id) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $caseTitles[$claim->allotment_case_id] ?? 'Unknown case' }}</a></td>
                        <td>{{ $claim->typeLabel() }}</td>
                        <td class="text-right font-medium"><x-money :value="$claim->amount" /></td>
                        <td><x-status-badge :status="$claim->status" /></td>
                        <td class="whitespace-nowrap">{{ $claim->created_at?->format('d M Y') }}</td>
                        <td class="text-right"><a class="{{ $claim->status === 'submitted' ? 'btn-primary btn-sm' : 'link' }}" href="{{ route('claims.review', $claim->_id) }}">{{ $claim->status === 'submitted' ? 'Review' : 'View' }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state title="No claims here" message="No claims in this view." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($claims->hasPages())
        <div class="border-t border-slate-100 px-4 py-3">{{ $claims->links() }}</div>
    @endif
</div>
@endsection
