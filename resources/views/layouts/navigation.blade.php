@php
    $user = auth()->user();
    $links = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'active' => 'dashboard', 'show' => true],
        ['route' => 'cases.index', 'label' => $user->hasRole('allottee') ? 'My Cases' : 'Cases', 'active' => 'cases.*', 'show' => true],
        ['route' => 'submissions.queue', 'label' => 'Submissions', 'active' => 'submissions.*', 'show' => $user->hasAnyRole(['district_officer', 'inspection_officer', 'state_admin', 'super_admin'])],
        ['route' => 'claims.index', 'label' => 'Claims', 'active' => 'claims.*', 'show' => $user->hasAnyRole(['district_officer', 'state_admin', 'super_admin'])],
        ['route' => 'policies.index', 'label' => 'Policy Templates', 'active' => 'policies.*', 'show' => $user->hasAnyRole(['super_admin', 'state_admin'])],
    ];
    $links = array_filter($links, fn ($l) => $l['show']);
    $roles = $user->getRoleNames()->map(fn ($r) => \App\Support\Format::label($r))->implode(', ');
    $initials = collect(explode(' ', $user->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
@endphp

<nav x-data="{ open: false }" class="bg-brand-950 text-white shadow">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    <x-application-logo class="h-8 w-8 text-amber-400" />
                    <span class="leading-tight">
                        <span class="block text-sm font-semibold">Plot Compliance</span>
                        <span class="block text-[11px] text-brand-200">Government Allotment Monitoring</span>
                    </span>
                </a>
                <div class="hidden items-center gap-1 md:flex">
                    @foreach($links as $link)
                        <a href="{{ route($link['route']) }}"
                           @class(['rounded-lg px-3 py-2 text-sm font-medium transition',
                                   'bg-white/15 text-white' => request()->routeIs($link['active']),
                                   'text-brand-100 hover:bg-white/10 hover:text-white' => ! request()->routeIs($link['active'])])>
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="hidden md:block">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-white/10">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-400 text-xs font-bold text-brand-950">{{ $initials }}</span>
                            <span class="text-left leading-tight">
                                <span class="block font-medium">{{ $user->name }}</span>
                                <span class="block text-[11px] text-brand-200">{{ $roles }}</span>
                            </span>
                            <svg class="h-4 w-4 text-brand-200" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Log out</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <button @click="open = ! open" class="rounded-lg p-2 text-brand-100 hover:bg-white/10 md:hidden" aria-label="Toggle menu">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path x-show="! open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path x-show="open" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div x-show="open" x-cloak x-transition class="border-t border-white/10 md:hidden">
        <div class="space-y-1 px-4 py-3">
            @foreach($links as $link)
                <a href="{{ route($link['route']) }}" @class(['block rounded-lg px-3 py-2 text-sm font-medium', 'bg-white/15' => request()->routeIs($link['active']), 'text-brand-100' => ! request()->routeIs($link['active'])])>{{ $link['label'] }}</a>
            @endforeach
        </div>
        <div class="border-t border-white/10 px-4 py-3 text-sm">
            <div class="font-medium">{{ $user->name }}</div>
            <div class="text-xs text-brand-200">{{ $user->email }} &middot; {{ $roles }}</div>
            <div class="mt-3 flex gap-2">
                <a href="{{ route('profile.edit') }}" class="rounded-lg bg-white/10 px-3 py-1.5 text-xs">Profile</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-lg bg-white/10 px-3 py-1.5 text-xs">Log out</button></form>
            </div>
        </div>
    </div>
</nav>
