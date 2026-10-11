@props(['statuses', 'current', 'route'])
<div class="mb-4 inline-flex flex-wrap gap-1 rounded-xl bg-slate-100 p-1">
    @foreach($statuses as $value)
        <a href="{{ route($route, ['status' => $value]) }}" class="tab {{ $current === $value ? 'tab-active' : '' }}">{{ \App\Support\Format::label($value) }}</a>
    @endforeach
    <a href="{{ route($route, ['status' => 'all']) }}" class="tab {{ $current === 'all' ? 'tab-active' : '' }}">All</a>
</div>
