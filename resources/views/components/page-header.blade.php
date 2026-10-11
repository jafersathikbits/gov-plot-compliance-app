@props(['title', 'subtitle' => null, 'back' => null])
<div class="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
    <div>
        @if($back)
            <a href="{{ $back }}" class="mb-2 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd"/></svg>
                Back
            </a>
        @endif
        <h1 class="page-title">{{ $title }}</h1>
        @if($subtitle)<p class="page-subtitle">{{ $subtitle }}</p>@endif
    </div>
    @if(trim($slot))
        <div class="flex flex-wrap gap-2">{{ $slot }}</div>
    @endif
</div>
