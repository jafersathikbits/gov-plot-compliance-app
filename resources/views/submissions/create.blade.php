@extends('layouts.app')

@section('content')
@php
    $stages = \App\Models\Submission::CONSTRUCTION_STAGES;
    $initialStage = old('construction_status', $lastSubmission?->construction_status);
    $initialStage = array_key_exists((string) $initialStage, $stages) ? $initialStage : 'not_started';
    $initialProgress = max(0, min(100, (float) old('construction_progress', $lastSubmission?->construction_progress ?? 0)));
@endphp
<div class="mx-auto max-w-3xl">
    <x-page-header title="Submit compliance progress" :subtitle="'Case: '.$case->title" :back="route('cases.show', $case->_id)" />

    @if($lastSubmission)
        <div class="mb-6 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
            Last update on {{ $lastSubmission->submitted_at?->format('d M Y') }}: <x-money :value="$lastSubmission->investment_spent" /> invested, {{ (int) $lastSubmission->employees_hired }} employed, {{ $lastSubmission->constructionLabel() }}.
            <x-status-badge :status="$lastSubmission->status" class="ml-1" />
            @if($lastSubmission->status === 'returned' && $lastSubmission->review_remarks)
                <p class="mt-2 rounded bg-amber-50 px-2 py-1 text-amber-800">Officer remarks: {{ $lastSubmission->review_remarks }}</p>
            @endif
        </div>
    @endif

    <form action="{{ route('submissions.store', $case->_id) }}" method="POST" enctype="multipart/form-data" class="card card-body space-y-5"
          x-data="{ stage: @js($initialStage), progress: {{ $initialProgress }} }">
        @csrf
        <div>
            <label class="form-label" for="milestone_id">Related milestone</label>
            <select id="milestone_id" name="milestone_id" class="form-input">
                <option value="">General progress update (applies to all milestones)</option>
                @foreach($milestones as $milestone)
                    <option value="{{ $milestone->_id }}" @selected(old('milestone_id') == (string) $milestone->_id)>{{ $milestone->title }} · due {{ $milestone->due_date?->format('d M Y') }}{{ $milestone->status === 'verified' ? ' · verified' : '' }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label class="form-label" for="investment_spent">Cumulative investment made (₹)</label>
                <input id="investment_spent" type="number" step="0.01" min="0" name="investment_spent" value="{{ old('investment_spent', $lastSubmission?->investment_spent) }}" class="form-input" required>
                <p class="form-hint">Target: <x-money :value="$case->investment_required" /></p>
            </div>
            <div>
                <label class="form-label" for="employees_hired">Total people employed</label>
                <input id="employees_hired" type="number" min="0" name="employees_hired" value="{{ old('employees_hired', $lastSubmission?->employees_hired) }}" class="form-input" required>
                <p class="form-hint">Target: {{ number_format((int) $case->employment_required) }} people</p>
            </div>
            <div>
                <label class="form-label" for="construction_status">Construction stage</label>
                <select id="construction_status" name="construction_status" class="form-input" x-model="stage" @change="if (stage === 'completed') progress = 100; if (stage === 'not_started') progress = 0;" required>
                    @foreach(\App\Models\Submission::CONSTRUCTION_STAGES as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="construction_progress">Construction progress: <span x-text="progress + '%'"></span></label>
                <input id="construction_progress" type="range" min="0" max="100" step="5" name="construction_progress" x-model="progress" class="mt-3 w-full accent-brand-700" :disabled="stage === 'completed'">
                <input type="hidden" name="construction_progress" value="100" :disabled="stage !== 'completed'" disabled>
            </div>
        </div>

        <div>
            <label class="form-label" for="remarks">Remarks</label>
            <textarea id="remarks" name="remarks" rows="3" class="form-input" placeholder="Anything the reviewing officer should know">{{ old('remarks') }}</textarea>
        </div>

        <div>
            <label class="form-label" for="evidence_files">Evidence files</label>
            <input id="evidence_files" type="file" name="evidence_files[]" multiple accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-brand-700">
            <p class="form-hint">Photos, invoices, payroll or CA certificates. PDF, JPG or PNG, up to 10 files of 10 MB each.</p>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('cases.show', $case->_id) }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Submit for review</button>
        </div>
    </form>
</div>
@endsection
