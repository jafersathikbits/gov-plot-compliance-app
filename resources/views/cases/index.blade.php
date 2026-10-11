@extends('layouts.app')

@section('content')
<x-page-header :title="auth()->user()->hasRole('allottee') ? 'My allotment cases' : 'Allotment cases'" subtitle="Every allotment under compliance monitoring.">
    @if(auth()->user()->hasAnyRole(['super_admin','state_admin']))
        <a href="{{ route('cases.create') }}" class="btn-primary">New case</a>
    @endif
</x-page-header>

<div class="card">
    <form method="GET" class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-end">
        <div class="flex-1">
            <label for="q" class="form-label">Search</label>
            <input id="q" name="q" value="{{ request('q') }}" class="form-input" placeholder="Search by case title">
        </div>
        <div class="sm:w-56">
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-input">
                <option value="">All statuses</option>
                @foreach(\App\Models\AllotmentCase::STATUSES as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ \App\Support\Format::label($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary">Filter</button>
            @if(request()->hasAny(['q', 'status']))
                <a href="{{ route('cases.index') }}" class="btn-secondary">Reset</a>
            @endif
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Case</th><th>Plot</th><th>Status</th><th>Legal</th><th>Construction deadline</th><th></th></tr></thead>
            <tbody>
                @forelse($cases as $case)
                    @php $plot = $plots[$case->plot_id] ?? null; @endphp
                    <tr>
                        <td>
                            <a href="{{ route('cases.show', $case->_id) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $case->title }}</a>
                            <div class="text-xs text-slate-500">Activated {{ $case->activated_at?->format('d M Y') ?? '—' }}</div>
                        </td>
                        <td>
                            <div class="font-medium">{{ $plot?->plot_number ?? '—' }}</div>
                            <div class="text-xs text-slate-500">{{ $plot?->project_type }}</div>
                        </td>
                        <td><x-status-badge :status="$case->status" /></td>
                        <td>@if($case->legal_status && $case->legal_status !== 'none')<x-status-badge :status="$case->legal_status" />@else<span class="text-slate-400">—</span>@endif</td>
                        <td class="whitespace-nowrap">{{ $case->construction_deadline?->format('d M Y') ?? '—' }}</td>
                        <td class="text-right"><a class="link" href="{{ route('cases.show', $case->_id) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state title="No cases found" message="Try a different search or status filter." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($cases->hasPages())
        <div class="border-t border-slate-100 px-4 py-3">{{ $cases->links() }}</div>
    @endif
</div>
@endsection
