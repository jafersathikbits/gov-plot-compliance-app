@extends('pdf.layout')
@section('content')
<h1 style="text-align:center; margin-top: 10px;">Final Compliance Certificate</h1>
<p class="lead" style="text-align:center;">Certificate No. FCC-{{ strtoupper(substr((string) $case->_id, -8)) }}</p>
<p>This is to certify that the allotment case described below has fulfilled all obligations prescribed under its allotment policy, every milestone having been verified by the competent officer, and is accordingly declared <strong>fully compliant</strong>.</p>
<table class="kv">
    <tr><th>Case title</th><td>{{ $case->title }}</td></tr>
    <tr><th>Status</th><td>{{ \App\Support\Format::label($case->status) }}</td></tr>
    <tr><th>Investment required</th><td>{{ \App\Support\Format::inr($case->investment_required) }}</td></tr>
    <tr><th>Employment required</th><td>{{ number_format((int) $case->employment_required) }}</td></tr>
    <tr><th>Activated on</th><td>{{ $case->activated_at?->format('d M Y') ?? '—' }}</td></tr>
    <tr><th>Closed on</th><td>{{ $case->closed_at?->format('d M Y') ?? '—' }}</td></tr>
</table>
<h2>Verified milestones</h2>
<table>
    <tr><th>Milestone</th><th>Requirement</th><th>Achieved</th><th>Verified on</th></tr>
    @foreach($milestones as $m)
        <tr>
            <td>{{ $m->title }}</td>
            <td>{{ \App\Support\Format::number($m->required_value) }} {{ $m->unit }}</td>
            <td>{{ \App\Support\Format::number($m->current_value) }} {{ $m->unit }}</td>
            <td>{{ $m->verified_at?->format('d M Y') ?? '—' }}</td>
        </tr>
    @endforeach
</table>
<div class="sign">Authorised Signatory</div>
@endsection
