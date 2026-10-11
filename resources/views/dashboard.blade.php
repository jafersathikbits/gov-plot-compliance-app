@extends('layouts.app')

@section('content')
@php
    $canAdmin = $user->hasAnyRole(['super_admin', 'state_admin']);
    $canReports = $user->hasAnyRole(['super_admin', 'state_admin', 'district_officer']);
    $canReviewSubmissions = $user->hasAnyRole(['district_officer', 'inspection_officer', 'state_admin', 'super_admin']);
    $canClaims = $user->hasAnyRole(['district_officer', 'state_admin', 'super_admin']);
    $toneClasses = [
        'slate' => 'bg-slate-400', 'emerald' => 'bg-emerald-500', 'amber' => 'bg-amber-500',
        'rose' => 'bg-rose-500', 'sky' => 'bg-sky-500', 'violet' => 'bg-violet-500',
    ];
    $chartColors = ['active' => '#0ea5e9', 'at_risk' => '#f59e0b', 'non_compliant' => '#f43f5e', 'fully_compliant' => '#10b981', 'terminated' => '#9f1239'];
    $chartConfig = [
        'type' => 'doughnut',
        'data' => [
            'labels' => $statusChart->keys()->map(fn ($s) => \App\Support\Format::label($s))->values(),
            'datasets' => [['data' => $statusChart->values(), 'backgroundColor' => $statusChart->keys()->map(fn ($s) => $chartColors[$s])->values(), 'borderWidth' => 0]],
        ],
        'options' => ['maintainAspectRatio' => false, 'cutout' => '65%', 'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['boxWidth' => 10, 'font' => ['size' => 11]]]]],
    ];
@endphp

<x-page-header title="Welcome back, {{ $user->name }}" subtitle="{{ now()->format('l, d F Y') }} · Compliance overview{{ $isAllottee ? ' for your allotments' : '' }}">
    @if($canAdmin)
        <a href="{{ route('cases.create') }}" class="btn-primary">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"/></svg>
            New case
        </a>
    @endif
    @if($canReports)
        <a href="{{ route('reports.monthly') }}" class="btn-secondary">Monthly report (PDF)</a>
        <a href="{{ route('reports.nonCompliance') }}" class="btn-secondary">Non-compliance report (PDF)</a>
    @endif
</x-page-header>

<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    @foreach($stats as $stat)
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                <span class="h-2.5 w-2.5 rounded-full {{ $toneClasses[$stat['tone']] }}"></span>
            </div>
            <p class="mt-3 text-3xl font-bold tabular-nums text-slate-900">{{ number_format($stat['value']) }}</p>
        </div>
    @endforeach
</div>

<div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="card xl:col-span-2">
        <div class="card-header">
            <h2 class="card-title">Recent cases</h2>
            <a href="{{ route('cases.index') }}" class="link text-sm">View all</a>
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Case</th><th>Status</th><th class="hidden sm:table-cell">Deadline</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                    @forelse($cases as $case)
                        <tr>
                            <td>
                                <a href="{{ route('cases.show', $case->_id) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $case->title }}</a>
                                <div class="text-xs text-slate-500">Created {{ $case->created_at?->format('d M Y') }}</div>
                            </td>
                            <td><x-status-badge :status="$case->status" /></td>
                            <td class="hidden whitespace-nowrap sm:table-cell">{{ $case->construction_deadline?->format('d M Y') ?? '—' }}</td>
                            <td class="text-right">
                                <div class="flex justify-end gap-3 whitespace-nowrap">
                                    <a href="{{ route('cases.show', $case->_id) }}" class="link">Open</a>
                                    @if($user->hasRole('allottee') && ! $case->isClosed())
                                        <a href="{{ route('submissions.create', $case->_id) }}" class="link">Submit progress</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-empty-state title="No cases yet" :message="$canAdmin ? 'Create the first allotment case to start monitoring.' : 'Cases assigned to you will appear here.'" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Case status mix</h2></div>
            <div class="card-body">
                @if($statusChart->sum() > 0)
                    <div class="mx-auto h-52 max-w-[16rem]">
                        <canvas data-chart='@json($chartConfig)' aria-label="Case status distribution"></canvas>
                    </div>
                @else
                    <x-empty-state title="No data yet" class="py-4" />
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Upcoming milestones</h2></div>
            <ul class="divide-y divide-slate-100">
                @forelse($milestones as $milestone)
                    <li class="px-5 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('cases.show', $milestone->allotment_case_id) }}" class="block truncate text-sm font-medium text-slate-900 hover:text-brand-700">{{ $milestone->title }}</a>
                                <p class="truncate text-xs text-slate-500">{{ $milestoneCaseTitles[$milestone->allotment_case_id] ?? 'Unknown case' }}</p>
                            </div>
                            <x-status-badge :status="$milestone->status" />
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            Due {{ $milestone->due_date?->format('d M Y') }}
                            <span @class(['font-medium', 'text-rose-600' => $milestone->due_date?->isPast(), 'text-slate-600' => ! $milestone->due_date?->isPast()])>({{ $milestone->due_date?->diffForHumans() }})</span>
                        </p>
                    </li>
                @empty
                    <li><x-empty-state title="All milestones verified" class="py-6" /></li>
                @endforelse
            </ul>
        </div>

        @if($canReviewSubmissions || $canClaims)
            <div class="card card-body space-y-3">
                <h2 class="card-title">Review queues</h2>
                @if($canReviewSubmissions)
                    <a href="{{ route('submissions.queue') }}" class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 text-sm hover:border-brand-300 hover:bg-brand-50">
                        <span>Progress submissions</span>
                        <span class="badge bg-sky-50 text-sky-700 ring-sky-600/20">{{ $stats['pending_submissions']['value'] }} pending</span>
                    </a>
                @endif
                @if($canClaims)
                    <a href="{{ route('claims.index') }}" class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3 text-sm hover:border-brand-300 hover:bg-brand-50">
                        <span>Incentive &amp; refund claims</span>
                        <span class="badge bg-violet-50 text-violet-700 ring-violet-600/20">{{ $stats['pending_claims']['value'] }} pending</span>
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
