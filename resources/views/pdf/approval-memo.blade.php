@extends('pdf.layout')
@section('content')
<h1>Approval Memo</h1>
<p class="lead">Memo No. AM-{{ strtoupper(substr((string) $claim->_id, -8)) }} · {{ $claim->approved_at?->format('d M Y') }}</p>
<p>The {{ strtolower($claim->typeLabel()) }} claim below has been examined against the compliance record of the case and is approved for payment.</p>
<table class="kv">
    <tr><th>Case</th><td>{{ $case?->title ?? '—' }}</td></tr>
    <tr><th>Claim type</th><td>{{ $claim->typeLabel() }}</td></tr>
    <tr><th>Amount</th><td>{{ \App\Support\Format::inr($claim->amount) }}</td></tr>
    <tr><th>Status</th><td>{{ \App\Support\Format::label($claim->status) }}</td></tr>
    <tr><th>Payment reference</th><td>{{ $claim->payment_reference }}</td></tr>
    <tr><th>Approved on</th><td>{{ $claim->approved_at?->format('d M Y, H:i') }}</td></tr>
    @if($claim->remarks)<tr><th>Remarks</th><td>{{ $claim->remarks }}</td></tr>@endif
</table>
<div class="sign">Approving Officer</div>
@endsection
