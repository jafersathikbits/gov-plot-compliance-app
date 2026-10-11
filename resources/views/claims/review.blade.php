@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl">
    <x-page-header title="Review claim" :subtitle="'Case: '.($case?->title ?? 'Unknown')" :back="route('claims.index')">
        @if($claim->status === 'paid')
            <a href="{{ route('claims.memo', $claim->_id) }}" class="btn-secondary">Download approval memo</a>
        @endif
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card card-body lg:col-span-2">
            <div class="mb-4 flex items-center justify-between"><h2 class="card-title">Claim details</h2><x-status-badge :status="$claim->status" /></div>
            <dl class="grid grid-cols-2 gap-5">
                <div class="dl-item"><dt>Type</dt><dd>{{ $claim->typeLabel() }}</dd></div>
                <div class="dl-item"><dt>Amount</dt><dd class="text-lg"><x-money :value="$claim->amount" /></dd></div>
                <div class="dl-item"><dt>Raised on</dt><dd>{{ $claim->created_at?->format('d M Y') }}</dd></div>
                <div class="dl-item"><dt>Case status</dt><dd>@if($case)<x-status-badge :status="$case->status" />@else — @endif</dd></div>
                @if($claim->payment_reference)<div class="dl-item"><dt>Payment reference</dt><dd class="font-mono">{{ $claim->payment_reference }}</dd></div>@endif
                @if($claim->paid_at)<div class="dl-item"><dt>Paid on</dt><dd>{{ $claim->paid_at->format('d M Y') }}</dd></div>@endif
            </dl>
            @if($claim->remarks)
                <div class="mt-5 rounded-lg bg-slate-50 p-3 text-sm text-slate-700"><span class="font-medium">Remarks:</span> {{ $claim->remarks }}</div>
            @endif
            @if($case)
                <div class="mt-5 grid grid-cols-2 gap-4 border-t border-slate-100 pt-4 text-sm">
                    <div><span class="text-slate-500">Case subsidy:</span> <x-money :value="$case->subsidy_amount" /></div>
                    <div><span class="text-slate-500">Caution deposit:</span> <x-money :value="$case->caution_deposit_amount" /></div>
                </div>
            @endif
        </div>

        @if($claim->status === 'submitted')
            <div class="card card-body space-y-4">
                <h2 class="card-title">Decision</h2>
                <form method="POST" action="{{ route('claims.approve', $claim->_id) }}" class="space-y-2" data-confirm="Approve this claim and record the payment?">@csrf
                    <label class="form-label" for="payment_reference">Payment reference</label>
                    <input id="payment_reference" name="payment_reference" class="form-input" placeholder="Leave blank to auto-generate">
                    <button type="submit" class="btn-success w-full">Approve &amp; record payment</button>
                </form>
                <form method="POST" action="{{ route('claims.clarify', $claim->_id) }}" class="space-y-2 border-t border-slate-100 pt-4">@csrf
                    <label class="form-label" for="clarify_remarks">Return for clarification</label>
                    <textarea id="clarify_remarks" name="remarks" rows="3" class="form-input" placeholder="What needs clarification?" required></textarea>
                    <button type="submit" class="btn-warning w-full">Return for clarification</button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
