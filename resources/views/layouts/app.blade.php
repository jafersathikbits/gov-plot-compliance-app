<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') === 'Laravel' ? 'Gov Plot Compliance' : config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
    <div class="min-h-full">
        @include('layouts.navigation')

        @isset($header)
            <header class="border-b border-slate-200 bg-white">
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">{{ $header }}</div>
            </header>
        @endisset

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @foreach(['success' => 'border-emerald-200 bg-emerald-50 text-emerald-800', 'warning' => 'border-amber-200 bg-amber-50 text-amber-800'] as $key => $tone)
                @if(session($key))
                    <div x-data="{ show: true }" x-show="show" x-transition role="status"
                         class="mb-6 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm {{ $tone }}">
                        <svg class="mt-0.5 h-5 w-5 flex-none" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                        <div class="flex-1">{{ session($key) }}</div>
                        <button type="button" @click="show = false" class="opacity-60 hover:opacity-100" aria-label="Dismiss">&times;</button>
                    </div>
                @endif
            @endforeach

            @if($errors->any())
                <div role="alert" class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <p class="font-semibold">Please fix the following:</p>
                    <ul class="mt-1 list-disc space-y-0.5 pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{ $slot ?? '' }}
            @yield('content')
        </main>

        <footer class="mx-auto max-w-7xl px-4 pb-8 text-xs text-slate-400 sm:px-6 lg:px-8">
            Government Plot Compliance Monitoring System &middot; {{ now()->year }}
        </footer>
    </div>
</body>
</html>
