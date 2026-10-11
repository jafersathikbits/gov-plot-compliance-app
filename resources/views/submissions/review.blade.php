@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl">
    <x-page-header title="Review submission" :subtitle="'Case: '.($case?->title ?? 'Unknown')" :back="route('submissions.queue')">
        <x-status-badge :status="$submission->status" class="self-center" />
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card card-body">
                <h2 class="card-title mb-4">Reported progress</h2>
                <dl class="grid grid-cols-2 gap-5 md:grid-cols-3">
                    <div class="dl-item"><dt>Investment made</dt><dd><x-money :value="$submission->investment_spent" /></dd></div>
                    <div class="dl-item"><dt>People employed</dt><dd>{{ number_format((int) $submission->employees_hired) }}</dd></div>
                    <div class="dl-item"><dt>Construction</dt><dd>{{ $submission->constructionLabel() }}@if($submission->construction_progress !== null) · {{ \App\Support\Format::number($submission->construction_progress) }}%@endif</dd></div>
                    <div class="dl-item"><dt>Submitted by</dt><dd>{{ $submitter?->name ?? '—' }}</dd></div>
                    <div class="dl-item"><dt>Submitted on</dt><dd>{{ $submission->submitted_at?->format('d M Y, H:i') }}</dd></div>
                    @if($submission->reviewed_at)<div class="dl-item"><dt>Reviewed on</dt><dd>{{ $submission->reviewed_at->format('d M Y, H:i') }}</dd></div>@endif
                </dl>
                @if($submission->remarks)
                    <div class="mt-5 rounded-lg bg-slate-50 p-3 text-sm text-slate-700"><span class="font-medium">Allottee remarks:</span> {{ $submission->remarks }}</div>
                @endif
                @if($submission->review_remarks)
                    <div class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-800"><span class="font-medium">Review remarks:</span> {{ $submission->review_remarks }}</div>
                @endif
            </div>

            <div class="card card-body">
                <h2 class="card-title mb-4">Evidence</h2>
                @forelse($submission->evidence_files ?? [] as $file)
                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($file) }}" target="_blank" rel="noopener" class="mb-2 mr-2 inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm hover:bg-slate-50">
                        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="M3 3.5A1.5 1.5 0 014.5 2h6.879a1.5 1.5 0 011.06.44l4.122 4.12A1.5 1.5 0 0117 7.622V16.5a1.5 1.5 0 01-1.5 1.5h-11A1.5 1.5 0 013 16.5v-13z"/></svg>
                        {{ basename($file) }}
                    </a>
                @empty
                    <p class="text-sm text-amber-700">No evidence files were attached.</p>
                @endforelse
            </div>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">Current milestones</h2></div>
                <ul class="divide-y divide-slate-100">
                    @foreach($milestones as $m)
                        <li class="px-5 py-3 text-sm">
                            <div class="flex justify-between gap-2"><span class="font-medium">{{ $m->title }}</span><x-status-badge :status="$m->status" /></div>
                            <p class="text-xs text-slate-500">{{ \App\Support\Format::number($m->current_value) }} / {{ \App\Support\Format::number($m->required_value) }} {{ $m->unit }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if($submission->status === 'submitted')
                <div class="card card-body space-y-4">
                    <h2 class="card-title">Decision</h2>
                    <form method="POST" action="{{ route('submissions.accept', $submission->_id) }}" data-confirm="Accept this submission and update milestone progress?">@csrf
                        <button type="submit" class="btn-success w-full">Accept &amp; update milestones</button>
                    </form>
                    <form method="POST" action="{{ route('submissions.return', $submission->_id) }}" class="space-y-2 border-t border-slate-100 pt-4">@csrf
                        <label class="form-label" for="review_remarks">Return with remarks</label>
                        <textarea id="review_remarks" name="review_remarks" rows="3" class="form-input" placeholder="What needs to be corrected or added?" required></textarea>
                        <button type="submit" class="btn-warning w-full">Return to allottee</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
