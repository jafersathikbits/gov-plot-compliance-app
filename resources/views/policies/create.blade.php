@extends('layouts.app')

@section('content')
@php
    $defaults = [
        ['title' => 'Investment Completion Milestone', 'type' => 'investment', 'description' => 'Achieve committed capital investment threshold', 'due' => 6, 'value' => 1000000, 'unit' => 'INR'],
        ['title' => 'Employment Generation Target', 'type' => 'employment', 'description' => 'Meet employment target', 'due' => 9, 'value' => 25, 'unit' => 'employees'],
        ['title' => 'Construction Stage Completion', 'type' => 'construction', 'description' => 'Complete construction stage', 'due' => 12, 'value' => 100, 'unit' => 'percent'],
        ['title' => 'Project Start Requirement', 'type' => 'time_bound', 'description' => 'Project must start within lease condition window', 'due' => 12, 'value' => 1, 'unit' => 'started'],
    ];
    $oldTitles = old('milestone_titles');
    $initial = $oldTitles ? collect($oldTitles)->map(fn ($title, $i) => [
        'title' => $title,
        'type' => old("milestone_types.$i", 'generic'),
        'description' => old("milestone_descriptions.$i", ''),
        'due' => old("milestone_due_months.$i", 1),
        'value' => old("milestone_required_values.$i", 0),
        'unit' => old("milestone_units.$i", 'count'),
    ])->values()->all() : $defaults;
    $unitFor = ['investment' => 'INR', 'employment' => 'employees', 'construction' => 'percent', 'time_bound' => 'started', 'generic' => 'count'];
@endphp
<div class="mx-auto max-w-5xl" x-data="{ milestones: @js($initial), units: @js($unitFor) }">
    <x-page-header title="New policy template" subtitle="Define the obligations every case using this policy must meet." :back="route('policies.index')" />

    <form method="POST" action="{{ route('policies.store') }}" class="space-y-6">@csrf
        <div class="card card-body grid grid-cols-1 gap-5 md:grid-cols-3">
            <div class="md:col-span-2"><label class="form-label" for="name">Name</label><input id="name" name="name" value="{{ old('name') }}" class="form-input" required></div>
            <div><label class="form-label" for="code">Code</label><input id="code" name="code" value="{{ old('code') }}" class="form-input font-mono uppercase" placeholder="STD-GOV-002" required></div>
            <div class="md:col-span-2"><label class="form-label" for="description">Description</label><textarea id="description" name="description" rows="2" class="form-input">{{ old('description') }}</textarea></div>
            <div><label class="form-label" for="warning_days">Warning days before due date</label><input id="warning_days" type="number" min="1" max="365" name="warning_days" value="{{ old('warning_days', 7) }}" class="form-input" required></div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Milestones <span class="text-sm font-normal text-slate-500" x-text="'(' + milestones.length + ')'"></span></h2>
                <button type="button" class="btn-secondary btn-sm" @click="milestones.push({title:'',type:'generic',description:'',due:1,value:0,unit:'count'})">+ Add milestone</button>
            </div>
            <div class="card-body space-y-4">
                <template x-for="(m, i) in milestones" :key="i">
                    <div class="rounded-lg border border-slate-200 p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500" x-text="'Milestone ' + (i + 1)"></span>
                            <button type="button" class="text-xs font-medium text-rose-600 hover:underline" x-show="milestones.length > 1" @click="milestones.splice(i, 1)">Remove</button>
                        </div>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-6">
                            <div class="md:col-span-4"><label class="form-label">Title</label><input class="form-input" :name="`milestone_titles[${i}]`" x-model="m.title" required></div>
                            <div class="md:col-span-2"><label class="form-label">Type</label>
                                <select class="form-input" :name="`milestone_types[${i}]`" x-model="m.type" @change="m.unit = units[m.type] ?? m.unit">
                                    @foreach(\App\Http\Controllers\PolicyTemplateController::MILESTONE_TYPES as $type)
                                        <option value="{{ $type }}">{{ \App\Support\Format::label($type) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-6"><label class="form-label">Description</label><input class="form-input" :name="`milestone_descriptions[${i}]`" x-model="m.description"></div>
                            <div class="md:col-span-2"><label class="form-label">Due (months after activation)</label><input type="number" min="0" class="form-input" :name="`milestone_due_months[${i}]`" x-model="m.due" required></div>
                            <div class="md:col-span-2"><label class="form-label">Required value</label><input type="number" step="0.01" min="0" class="form-input" :name="`milestone_required_values[${i}]`" x-model="m.value" required></div>
                            <div class="md:col-span-2"><label class="form-label">Unit</label><input class="form-input" :name="`milestone_units[${i}]`" x-model="m.unit" required></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('policies.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save template</button>
        </div>
    </form>
</div>
@endsection
