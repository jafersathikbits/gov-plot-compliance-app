<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name') === 'Laravel' ? 'Gov Plot Compliance' : config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="grid min-h-screen lg:grid-cols-2">
            <div class="relative hidden flex-col justify-between overflow-hidden bg-brand-950 p-12 text-white lg:flex">
                <div class="absolute inset-0 opacity-10" style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 48px 48px;"></div>
                <a href="/" class="relative flex items-center gap-3">
                    <x-application-logo class="h-10 w-10 text-amber-400" />
                    <span class="text-lg font-semibold">Plot Compliance</span>
                </a>
                <div class="relative max-w-md">
                    <h2 class="text-3xl font-bold leading-tight">Monitor allotment obligations from activation to closure.</h2>
                    <p class="mt-4 text-brand-200">Track investment, employment and construction milestones, review progress, process subsidy and refund claims, and escalate non-compliance in one place.</p>
                </div>
                <p class="relative text-xs text-brand-300">Government Plot Compliance Monitoring System</p>
            </div>
            <div class="flex flex-col items-center justify-center bg-slate-50 px-6 py-12">
                <a href="/" class="mb-8 flex items-center gap-2 lg:hidden">
                    <x-application-logo class="h-10 w-10 text-brand-700" />
                    <span class="text-lg font-semibold">Plot Compliance</span>
                </a>
                <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
