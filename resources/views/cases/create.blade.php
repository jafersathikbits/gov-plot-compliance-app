@extends('layouts.app')

@section('content')
<x-page-header title="New allotment case" subtitle="Register the plot, map the allottee and policy, assign officers and upload documents. Milestones are generated automatically and the case is activated on save." :back="route('cases.index')" />

<form action="{{ route('cases.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf

    <div class="card">
        <div class="card-header"><h2 class="form-section">1 · Plot and project details</h2></div>
        <div class="card-body grid grid-cols-1 gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="form-label" for="title">Case title</label>
                <input id="title" name="title" value="{{ old('title') }}" class="form-input" placeholder="e.g. ABC Industries – Plot 42, Phase II" required>
            </div>
            <div><label class="form-label" for="plot_number">Plot number</label><input id="plot_number" name="plot_number" value="{{ old('plot_number') }}" class="form-input" required></div>
            <div><label class="form-label" for="project_type">Project type</label><input id="project_type" name="project_type" value="{{ old('project_type') }}" class="form-input" placeholder="Industrial unit, warehouse…" required></div>
            <div><label class="form-label" for="area">Area (sq. m)</label><input id="area" type="number" step="0.01" min="0" name="area" value="{{ old('area') }}" class="form-input" required></div>
            <div><label class="form-label" for="lease_duration_years">Lease duration (years)</label><input id="lease_duration_years" type="number" min="1" max="99" name="lease_duration_years" value="{{ old('lease_duration_years', 30) }}" class="form-input" required></div>
            <div><label class="form-label" for="required_investment">Required investment (₹)</label><input id="required_investment" type="number" step="0.01" min="0" name="required_investment" value="{{ old('required_investment') }}" class="form-input" required></div>
            <div><label class="form-label" for="employment_commitment">Employment commitment (people)</label><input id="employment_commitment" type="number" min="0" name="employment_commitment" value="{{ old('employment_commitment') }}" class="form-input" required></div>
            <div><label class="form-label" for="construction_deadline">Construction deadline</label><input id="construction_deadline" type="date" min="{{ now()->addDay()->toDateString() }}" name="construction_deadline" value="{{ old('construction_deadline') }}" class="form-input" required></div>
            <div></div>
            <div><label class="form-label" for="subsidy_amount">Land cost subsidy (₹)</label><input id="subsidy_amount" type="number" step="0.01" min="0" name="subsidy_amount" value="{{ old('subsidy_amount') }}" class="form-input"><p class="form-hint">Maximum subsidy that can be claimed on this case.</p></div>
            <div><label class="form-label" for="caution_deposit_amount">Caution deposit (₹)</label><input id="caution_deposit_amount" type="number" step="0.01" min="0" name="caution_deposit_amount" value="{{ old('caution_deposit_amount') }}" class="form-input"><p class="form-hint">Refundable once the case is fully compliant.</p></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2 class="form-section">2 · Allottee, policy and officers</h2></div>
        <div class="card-body grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label class="form-label" for="allottee_user_id">Allottee</label>
                <select id="allottee_user_id" name="allottee_user_id" class="form-input" required>
                    <option value="">Select allottee</option>
                    @foreach($allottees as $u)<option value="{{ $u->id }}" @selected(old('allottee_user_id') == $u->id)>{{ $u->name }} ({{ $u->email }})</option>@endforeach
                </select>
                @if($allottees->isEmpty())<p class="form-hint text-amber-700">No users have the allottee role yet.</p>@endif
            </div>
            <div>
                <label class="form-label" for="policy_template_id">Policy template</label>
                <select id="policy_template_id" name="policy_template_id" class="form-input" required>
                    @foreach($templates as $t)<option value="{{ $t->_id }}" @selected(old('policy_template_id') == (string) $t->_id)>{{ $t->name }} ({{ count($t->milestones ?? []) }} milestones)</option>@endforeach
                </select>
                @if($templates->isEmpty())<p class="form-hint text-amber-700">Create a <a class="link" href="{{ route('policies.create') }}">policy template</a> first.</p>@endif
            </div>
            <div>
                <label class="form-label" for="district_officer_id">District officer</label>
                <select id="district_officer_id" name="district_officer_id" class="form-input"><option value="">Not assigned</option>@foreach($districtOfficers as $u)<option value="{{ $u->id }}" @selected(old('district_officer_id') == $u->id)>{{ $u->name }}</option>@endforeach</select>
            </div>
            <div>
                <label class="form-label" for="inspection_officer_id">Inspection officer</label>
                <select id="inspection_officer_id" name="inspection_officer_id" class="form-input"><option value="">Not assigned</option>@foreach($inspectionOfficers as $u)<option value="{{ $u->id }}" @selected(old('inspection_officer_id') == $u->id)>{{ $u->name }}</option>@endforeach</select>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2 class="form-section">3 · Documents</h2></div>
        <div class="card-body grid grid-cols-1 gap-5 md:grid-cols-2">
            <div><label class="form-label" for="allotment_order">Allotment order</label><input id="allotment_order" type="file" name="allotment_order" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-brand-700"><p class="form-hint">PDF, JPG or PNG, up to 10 MB.</p></div>
            <div><label class="form-label" for="lease_deed">Lease deed</label><input id="lease_deed" type="file" name="lease_deed" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-brand-700"><p class="form-hint">PDF, JPG or PNG, up to 10 MB.</p></div>
        </div>
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route('cases.index') }}" class="btn-secondary">Cancel</a>
        <button type="submit" class="btn-primary" @disabled($templates->isEmpty())>Create &amp; activate case</button>
    </div>
</form>
@endsection
