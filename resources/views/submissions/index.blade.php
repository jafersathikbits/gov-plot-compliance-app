@extends('layouts.app')

@section('content')
<x-page-header title="Submission review queue" subtitle="Progress updates submitted by allottees, awaiting officer verification." />

<x-status-tabs :statuses="\App\Http\Controllers\SubmissionController::STATUSES" :current="$status" route="submissions.queue" />

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Case</th><th>Reported progress</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
            <tbody>
                @forelse($submissions as $submission)
                    <tr>
                        <td><a href="{{ route('cases.show', $submission->allotment_case_id) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $caseTitles[$submission->allotment_case_id] ?? 'Unknown case' }}</a></td>
                        <td class="text-xs text-slate-600">
                            <x-money :value="$submission->investment_spent" /> · {{ (int) $submission->employees_hired }} employed<br>
                            {{ $submission->constructionLabel() }}@if($submission->construction_progress !== null) ({{ \App\Support\Format::number($submission->construction_progress) }}%)@endif
                        </td>
                        <td><x-status-badge :status="$submission->status" /></td>
                        <td class="whitespace-nowrap">{{ $submission->submitted_at?->format('d M Y, H:i') }}<div class="text-xs text-slate-500">{{ $submission->submitted_at?->diffForHumans() }}</div></td>
                        <td class="text-right"><a class="{{ $submission->status === 'submitted' ? 'btn-primary btn-sm' : 'link' }}" href="{{ route('submissions.review', $submission->_id) }}">{{ $submission->status === 'submitted' ? 'Review' : 'View' }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state title="Queue is clear" message="No submissions in this view." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($submissions->hasPages())
        <div class="border-t border-slate-100 px-4 py-3">{{ $submissions->links() }}</div>
    @endif
</div>
@endsection
