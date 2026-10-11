@extends('layouts.app')

@section('content')
@php
    $user = auth()->user();
    $isAdmin = $user->hasAnyRole(['super_admin', 'state_admin']);
    $isReviewer = $user->hasAnyRole(['district_officer', 'inspection_officer', 'state_admin', 'super_admin']);
    $canClaims = $user->hasAnyRole(['district_officer', 'state_admin', 'super_admin']);
    $canReports = $user->hasAnyRole(['district_officer', 'state_admin', 'super_admin']);
    $isOwnerAllottee = (string) $case->allottee_user_id === (string) $user->id;
    $closed = $case->isClosed();
    $verifiedCount = $milestones->where('status', 'verified')->count();
    $overallProgress = $milestones->count() ? (int) round($verifiedCount / $milestones->count() * 100) : 0;
    $name = fn ($id) => $id ? ($people[(string) $id]->name ?? 'Unknown user') : 'Not assigned';
@endphp

<x-page-header :title="$case->title" :back="route('cases.index')">
    @if($isOwnerAllottee && ! $closed)
        <a href="{{ route('submissions.create', $case->_id) }}" class="btn-primary">Submit progress</a>
    @endif
    @if($canReports && $case->status === 'fully_compliant')
        <a href="{{ route('reports.certificate', $case->_id) }}" class="btn-success">Download certificate</a>
    @endif
    @if($isAdmin && ! $case->is_archived && $case->status !== 'terminated')
        <form method="POST" action="{{ route('cases.close', $case->_id) }}">@csrf<button type="submit" class="btn-secondary">Run closure check</button></form>
    @endif
</x-page-header>

<div class="mb-6 flex flex-wrap items-center gap-2">
    <x-status-badge :status="$case->status" />
    @if($case->legal_status && $case->legal_status !== 'none')<x-status-badge :status="$case->legal_status" />@endif
    @if($case->is_archived)<span class="badge bg-slate-100 text-slate-600 ring-slate-400/30">Archived</span>@endif
    @if($case->legal_remarks)<span class="text-sm text-slate-500">Legal note: {{ $case->legal_remarks }}</span>@endif
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="card card-body lg:col-span-2">
        <h2 class="card-title mb-4">Allotment details</h2>
        <dl class="grid grid-cols-2 gap-x-6 gap-y-5 md:grid-cols-3">
            <div class="dl-item"><dt>Plot</dt><dd>{{ $plot?->plot_number ?? '—' }}</dd></div>
            <div class="dl-item"><dt>Project type</dt><dd>{{ $plot?->project_type ?? '—' }}</dd></div>
            <div class="dl-item"><dt>Area</dt><dd>{{ $plot ? \App\Support\Format::number($plot->area).' sq. m' : '—' }}</dd></div>
            <div class="dl-item"><dt>Policy</dt><dd>{{ $template?->name ?? '—' }}</dd></div>
            <div class="dl-item"><dt>Activated</dt><dd>{{ $case->activated_at?->format('d M Y') ?? '—' }}</dd></div>
            <div class="dl-item"><dt>Construction deadline</dt><dd>{{ $case->construction_deadline?->format('d M Y') ?? '—' }}</dd></div>
            <div class="dl-item"><dt>Investment required</dt><dd><x-money :value="$case->investment_required" /></dd></div>
            <div class="dl-item"><dt>Employment required</dt><dd>{{ number_format((int) $case->employment_required) }} people</dd></div>
            <div class="dl-item"><dt>Lease</dt><dd>{{ $plot?->lease_duration_years ? $plot->lease_duration_years.' years' : '—' }}</dd></div>
            <div class="dl-item"><dt>Subsidy</dt><dd><x-money :value="$case->subsidy_amount" /></dd></div>
            <div class="dl-item"><dt>Caution deposit</dt><dd><x-money :value="$case->caution_deposit_amount" /></dd></div>
            @if($case->closed_at)<div class="dl-item"><dt>Closed</dt><dd>{{ $case->closed_at->format('d M Y') }}</dd></div>@endif
        </dl>
        @if(! empty($case->documents))
            <div class="mt-6 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                @foreach($case->documents as $label => $path)
                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($path) }}" target="_blank" rel="noopener" class="btn-secondary btn-sm">{{ \App\Support\Format::label($label) }}</a>
                @endforeach
            </div>
        @endif
    </div>

    <div class="card card-body">
        <h2 class="card-title mb-4">Compliance progress</h2>
        <div class="flex items-end justify-between">
            <span class="text-3xl font-bold tabular-nums">{{ $overallProgress }}%</span>
            <span class="text-sm text-slate-500">{{ $verifiedCount }} of {{ $milestones->count() }} milestones verified</span>
        </div>
        <div class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full bg-emerald-500" style="width: {{ $overallProgress }}%"></div>
        </div>
        <dl class="mt-6 space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Allottee</dt><dd class="font-medium">{{ $name($case->allottee_user_id) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">District officer</dt><dd class="font-medium">{{ $name($case->district_officer_id) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Inspection officer</dt><dd class="font-medium">{{ $name($case->inspection_officer_id) }}</dd></div>
        </dl>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="card">
        <div class="card-header"><h2 class="card-title">Milestones</h2></div>
        <ul class="divide-y divide-slate-100">
            @forelse($milestones as $m)
                @php $pct = $m->progressPercent(); @endphp
                <li class="px-5 py-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-medium text-slate-900">{{ $m->title }}</p>
                            <p class="text-xs text-slate-500">{{ \App\Support\Format::label($m->type) }} · due {{ $m->due_date?->format('d M Y') }} ({{ $m->due_date?->diffForHumans() }})</p>
                        </div>
                        <x-status-badge :status="$m->status" />
                    </div>
                    <div class="mt-3 flex items-center gap-3">
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                            <div @class(['h-full rounded-full', 'bg-emerald-500' => $m->status === 'verified', 'bg-rose-500' => $m->status === 'overdue', 'bg-amber-500' => $m->status === 'due_soon', 'bg-brand-500' => ! in_array($m->status, ['verified', 'overdue', 'due_soon'])]) style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="w-10 text-right text-xs font-medium tabular-nums text-slate-600">{{ $pct }}%</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">
                        @if($m->unit === 'INR')
                            <x-money :value="$m->current_value" /> of <x-money :value="$m->required_value" />
                        @else
                            {{ \App\Support\Format::number($m->current_value) }} of {{ \App\Support\Format::number($m->required_value) }} {{ $m->unit }}
                        @endif
                        @if($m->verified_at) · verified {{ $m->verified_at->format('d M Y') }} @endif
                    </p>
                </li>
            @empty
                <li><x-empty-state title="No milestones generated" /></li>
            @endforelse
        </ul>
    </div>

    <div class="space-y-6">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Progress submissions</h2>
                @if($isReviewer)<a href="{{ route('submissions.queue') }}" class="link text-sm">Review queue</a>@endif
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse($submissions as $s)
                    <li class="flex items-start justify-between gap-4 px-5 py-3">
                        <div class="min-w-0 text-sm">
                            <p class="font-medium text-slate-900">{{ $s->constructionLabel() }}@if($s->construction_progress !== null) · {{ \App\Support\Format::number($s->construction_progress) }}%@endif</p>
                            <p class="text-xs text-slate-500">{{ $s->submitted_at?->format('d M Y, H:i') }} · <x-money :value="$s->investment_spent" /> invested · {{ (int) $s->employees_hired }} employed</p>
                            @if($s->status === 'returned' && $s->review_remarks)
                                <p class="mt-1 rounded bg-amber-50 px-2 py-1 text-xs text-amber-800">Returned: {{ $s->review_remarks }}</p>
                            @endif
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <x-status-badge :status="$s->status" />
                            @if($isReviewer)<a href="{{ route('submissions.review', $s->_id) }}" class="link text-xs">{{ $s->status === 'submitted' ? 'Review' : 'View' }}</a>@endif
                        </div>
                    </li>
                @empty
                    <li><x-empty-state title="No submissions yet" :message="$isOwnerAllottee ? 'Submit your first progress update with evidence.' : null" /></li>
                @endforelse
            </ul>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Incentive &amp; refund claims</h2>
                @if($canClaims && $case->status !== 'terminated')<a href="{{ route('claims.create', $case->_id) }}" class="link text-sm">New claim</a>@endif
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse($claims as $c)
                    <li class="flex items-start justify-between gap-4 px-5 py-3">
                        <div class="text-sm">
                            <p class="font-medium text-slate-900">{{ $c->typeLabel() }} · <x-money :value="$c->amount" /></p>
                            <p class="text-xs text-slate-500">{{ $c->created_at?->format('d M Y') }}@if($c->payment_reference) · Ref {{ $c->payment_reference }}@endif</p>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <x-status-badge :status="$c->status" />
                            @if($canClaims)<a href="{{ route('claims.review', $c->_id) }}" class="link text-xs">{{ $c->status === 'submitted' ? 'Review' : 'View' }}</a>@endif
                        </div>
                    </li>
                @empty
                    <li><x-empty-state title="No claims yet" /></li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

@if($isAdmin)
    <div class="mt-6 card">
        <div class="card-header"><h2 class="card-title">Officer and allottee assignment</h2></div>
        <form method="POST" action="{{ route('cases.assign', $case->_id) }}" class="card-body grid grid-cols-1 gap-4 md:grid-cols-4 md:items-end">@csrf
            <div><label class="form-label" for="a_allottee">Allottee</label><select id="a_allottee" name="allottee_user_id" class="form-input">@foreach($allottees as $u)<option value="{{ $u->id }}" @selected($case->allottee_user_id == $u->id)>{{ $u->name }}</option>@endforeach</select></div>
            <div><label class="form-label" for="a_district">District officer</label><select id="a_district" name="district_officer_id" class="form-input"><option value="">Not assigned</option>@foreach($districtOfficers as $u)<option value="{{ $u->id }}" @selected($case->district_officer_id == $u->id)>{{ $u->name }}</option>@endforeach</select></div>
            <div><label class="form-label" for="a_inspection">Inspection officer</label><select id="a_inspection" name="inspection_officer_id" class="form-input"><option value="">Not assigned</option>@foreach($inspectionOfficers as $u)<option value="{{ $u->id }}" @selected($case->inspection_officer_id == $u->id)>{{ $u->name }}</option>@endforeach</select></div>
            <div><button type="submit" class="btn-primary w-full">Save assignments</button></div>
        </form>
    </div>

    @unless($closed)
        <div class="mt-6 card border-rose-200">
            <div class="card-header"><h2 class="card-title">Legal and escalation actions</h2><span class="text-xs text-slate-500">All actions are recorded in the audit trail</span></div>
            <div class="card-body grid grid-cols-1 gap-4 md:grid-cols-2">
                <form method="POST" action="{{ route('legal.notice', $case->_id) }}" class="space-y-3 rounded-lg border border-slate-200 p-4">@csrf
                    <p class="text-sm font-medium">Issue show-cause notice</p>
                    <textarea name="remarks" rows="2" class="form-input" placeholder="Notice remarks (optional)"></textarea>
                    <button type="submit" class="btn-warning">Issue notice</button>
                </form>
                <form method="POST" action="{{ route('legal.extension', $case->_id) }}" class="space-y-3 rounded-lg border border-slate-200 p-4">@csrf
                    <p class="text-sm font-medium">Grant milestone extension</p>
                    <select name="milestone_id" class="form-input" required>
                        @foreach($milestones->where('status', '!=', 'verified') as $m)<option value="{{ $m->_id }}">{{ $m->title }} (due {{ $m->due_date?->format('d M Y') }})</option>@endforeach
                    </select>
                    <div class="flex gap-3">
                        <input type="number" name="extension_days" min="1" max="1825" class="form-input" placeholder="Days" required>
                        <button type="submit" class="btn-primary whitespace-nowrap">Grant extension</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('legal.review', $case->_id) }}" class="space-y-3 rounded-lg border border-slate-200 p-4">@csrf
                    <p class="text-sm font-medium">Refer to legal cell</p>
                    <p class="text-xs text-slate-500">Marks the case for legal review before enforcement.</p>
                    <button type="submit" class="btn-secondary">Flag for legal review</button>
                </form>
                <form method="POST" action="{{ route('legal.terminate', $case->_id) }}" data-confirm="Terminate this allotment? This cannot be undone." class="space-y-3 rounded-lg border border-rose-200 bg-rose-50/50 p-4">@csrf
                    <p class="text-sm font-medium text-rose-800">Terminate allotment</p>
                    <textarea name="reason" rows="2" class="form-input" placeholder="Reason for termination (required)" required></textarea>
                    <button type="submit" class="btn-danger">Terminate case</button>
                </form>
            </div>
        </div>
    @endunless
@endif

@if($auditLogs->isNotEmpty())
    <div class="mt-6 card">
        <div class="card-header"><h2 class="card-title">Audit trail</h2><span class="text-xs text-slate-500">Latest {{ $auditLogs->count() }} events</span></div>
        <ol class="divide-y divide-slate-100">
            @foreach($auditLogs as $log)
                <li class="flex flex-col gap-1 px-5 py-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <span class="font-medium text-slate-900">{{ \App\Support\Format::label($log->action) }}</span>
                        <span class="text-slate-500">by {{ $log->user_id ? $name($log->user_id) : 'System' }}</span>
                    </div>
                    <time class="text-xs text-slate-500">{{ ($log->logged_at ?? $log->created_at)?->format('d M Y, H:i') }}</time>
                </li>
            @endforeach
        </ol>
    </div>
@endif
@endsection
