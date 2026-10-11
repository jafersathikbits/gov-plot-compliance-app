@props(['status'])

@php
$tones = [
    'fully_compliant' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    'verified' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    'accepted' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    'active' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
    'pending' => 'bg-slate-100 text-slate-700 ring-slate-500/20',
    'submitted' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
    'at_risk' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
    'due_soon' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
    'returned' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
    'clarification_required' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
    'notice_issued' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
    'extension_granted' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
    'legal_review' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
    'non_compliant' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
    'overdue' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
    'terminated' => 'bg-rose-100 text-rose-800 ring-rose-700/30',
];
$dots = [
    'emerald' => 'bg-emerald-500', 'sky' => 'bg-sky-500', 'slate' => 'bg-slate-400',
    'amber' => 'bg-amber-500', 'violet' => 'bg-violet-500', 'rose' => 'bg-rose-500',
];
$class = $tones[$status] ?? 'bg-slate-100 text-slate-700 ring-slate-500/20';
preg_match('/text-(\w+)-/', $class, $m);
@endphp

<span {{ $attributes->merge(['class' => 'badge '.$class]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dots[$m[1] ?? 'slate'] ?? 'bg-slate-400' }}"></span>
    {{ \App\Support\Format::label($status ?: 'unknown') }}
</span>
