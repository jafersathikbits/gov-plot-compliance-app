@extends('layouts.app')

@section('content')
<x-page-header title="Policy templates" subtitle="Templates define the milestones generated for each new allotment case.">
    <a href="{{ route('policies.create') }}" class="btn-primary">New template</a>
</x-page-header>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    @forelse($templates as $template)
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">{{ $template->name }}</h2>
                    <p class="font-mono text-xs text-slate-500">{{ $template->code }}</p>
                </div>
                <span class="badge bg-brand-50 text-brand-700 ring-brand-600/20">{{ count($template->milestones ?? []) }} milestones</span>
            </div>
            <div class="card-body">
                @if($template->description)<p class="mb-4 text-sm text-slate-600">{{ $template->description }}</p>@endif
                <ol class="space-y-2 text-sm">
                    @foreach($template->milestones ?? [] as $m)
                        <li class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2">
                            <span><span class="font-medium">{{ $m['title'] ?? 'Milestone' }}</span> <span class="text-xs text-slate-500">· {{ \App\Support\Format::label($m['type'] ?? 'generic') }}</span></span>
                            <span class="whitespace-nowrap text-xs text-slate-500">{{ \App\Support\Format::number($m['required_value'] ?? 0) }} {{ $m['unit'] ?? '' }} · {{ $m['due_in_months'] ?? 0 }} mo</span>
                        </li>
                    @endforeach
                </ol>
                <p class="mt-4 text-xs text-slate-500">Warning sent {{ $template->rules['warning_days'] ?? 7 }} days before each due date.</p>
            </div>
        </div>
    @empty
        <div class="card lg:col-span-2"><x-empty-state title="No templates yet" message="Create a template before registering cases." /></div>
    @endforelse
</div>
@endsection
