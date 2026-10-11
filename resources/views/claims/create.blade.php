@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl">
    <x-page-header title="Initiate incentive / refund claim" :subtitle="'Case: '.$case->title" :back="route('cases.show', $case->_id)" />

    @unless($eligible)
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            This case is not yet eligible for a claim. At least one milestone must be verified and the case must not be non-compliant or terminated.
        </div>
    @endunless

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="card p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Subsidy remaining</p>
            <p class="mt-1 text-xl font-semibold">{!! $limits['subsidy'] === null ? '<span class="text-slate-400">No cap set</span>' : e(\App\Support\Format::inr($limits['subsidy'])) !!}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Caution deposit refundable</p>
            <p class="mt-1 text-xl font-semibold">{!! $limits['refund'] === null ? '<span class="text-slate-400">No deposit recorded</span>' : e(\App\Support\Format::inr($limits['refund'])) !!}</p>
            <p class="text-xs text-slate-500">Only after the case is fully compliant.</p>
        </div>
    </div>

    <form action="{{ route('claims.store', $case->_id) }}" method="POST" class="card card-body space-y-5">@csrf
        <div>
            <label class="form-label" for="type">Claim type</label>
            <select id="type" name="type" class="form-input">
                @foreach(\App\Models\IncentiveClaim::TYPES as $value => $label)
                    <option value="{{ $value }}" @selected(old('type') === $value) @disabled($value === 'refund' && $case->status !== 'fully_compliant')>{{ $label }}{{ $value === 'refund' && $case->status !== 'fully_compliant' ? ' (requires full compliance)' : '' }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" for="amount">Amount (₹)</label>
            <input id="amount" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" class="form-input" required>
        </div>
        <div>
            <label class="form-label" for="remarks">Remarks</label>
            <textarea id="remarks" name="remarks" rows="3" class="form-input">{{ old('remarks') }}</textarea>
        </div>
        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('cases.show', $case->_id) }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary" @disabled(! $eligible)>Initiate claim</button>
        </div>
    </form>
</div>
@endsection
