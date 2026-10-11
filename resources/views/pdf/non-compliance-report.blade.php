@extends('pdf.layout')
@section('content')
<h1>Non-Compliance Report</h1>
<p class="lead">Cases at risk, non-compliant or terminated as of {{ now()->format('d M Y') }}. Total: {{ $cases->count() }}</p>
<table>
    <tr><th>Case</th><th>Status</th><th>Legal status</th><th>Construction deadline</th><th>Overdue / due-soon milestones</th></tr>
    @forelse($cases as $case)
        <tr>
            <td>{{ $case->title }}</td>
            <td>{{ \App\Support\Format::label($case->status) }}</td>
            <td>{{ \App\Support\Format::label($case->legal_status ?: 'none') }}</td>
            <td>{{ $case->construction_deadline?->format('d M Y') ?? '—' }}</td>
            <td>
                @forelse($overdue->get((string) $case->_id, collect()) as $m)
                    {{ $m->title }} ({{ \App\Support\Format::label($m->status) }}, due {{ $m->due_date?->format('d M Y') }})<br>
                @empty
                    <span class="muted">—</span>
                @endforelse
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="muted">No cases are currently at risk or non-compliant.</td></tr>
    @endforelse
</table>
@endsection
