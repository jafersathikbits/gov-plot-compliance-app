@extends('pdf.layout')
@section('content')
<h1>Monthly Compliance Summary</h1>
<p class="lead">Reporting period: {{ $monthStart->format('F Y') }}</p>

<table class="summary">
    <tr>
        <td><span class="big">{{ $cases->count() }}</span>Total cases</td>
        <td><span class="big">{{ $statusCounts->get('fully_compliant', 0) }}</span>Fully compliant</td>
        <td><span class="big">{{ $statusCounts->get('at_risk', 0) }}</span>At risk</td>
        <td><span class="big">{{ $statusCounts->get('non_compliant', 0) + $statusCounts->get('terminated', 0) }}</span>Non-compliant / terminated</td>
    </tr>
</table>

<h2>Activity this month</h2>
<table class="kv">
    <tr><th>Progress submissions received</th><td>{{ $submissions->count() }}</td></tr>
    <tr><th>Submissions accepted</th><td>{{ $submissions->where('status', 'accepted')->count() }}</td></tr>
    <tr><th>Submissions returned</th><td>{{ $submissions->where('status', 'returned')->count() }}</td></tr>
    <tr><th>Claims paid</th><td>{{ $claimsPaid->count() }} ({{ \App\Support\Format::inr($claimsPaid->sum('amount')) }})</td></tr>
</table>

<h2>Case register</h2>
<table>
    <tr><th>Case</th><th>Status</th><th>Legal</th><th class="num">Investment required</th><th class="num">Employment required</th></tr>
    @forelse($cases as $case)
        <tr>
            <td>{{ $case->title }}</td>
            <td>{{ \App\Support\Format::label($case->status) }}</td>
            <td>{{ \App\Support\Format::label($case->legal_status ?: 'none') }}</td>
            <td class="num">{{ \App\Support\Format::inr($case->investment_required) }}</td>
            <td class="num">{{ number_format((int) $case->employment_required) }}</td>
        </tr>
    @empty
        <tr><td colspan="5" class="muted">No cases recorded.</td></tr>
    @endforelse
</table>
@endsection
