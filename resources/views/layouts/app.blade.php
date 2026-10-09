<!DOCTYPE html>
<html lang="en" x-data="{ isMobileSidebarOpen: false }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SmartClaim - Expense Intelligence System')</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        @media (min-width: 1024px) {
            aside[x-cloak] { display: flex !important; }
        }
    </style>
    @stack('styles')
</head>
<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" :class="isMobileSidebarOpen ? 'overflow-hidden lg:overflow-auto' : ''">
    @auth
        @if(auth()->user()->is_demo)
            <!-- Demo Mode Global Isolation Banner -->
            <div class="bg-gradient-to-r from-amber-500 via-amber-600 to-amber-500 text-slate-950 font-medium px-4 py-2 text-xs flex items-center justify-between shadow-sm sticky top-0 z-[45] border-b border-amber-600">
                <div class="flex items-center gap-2.5 mx-auto sm:mx-0">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-slate-950 text-amber-300">DEMO MODE</span>
                    <span class="font-bold">Demo Mode (Read-Only) — Viewing isolated sample environment.</span>
                </div>
                <div class="hidden md:flex items-center gap-2 text-[11px] font-semibold text-slate-900">
                    <i class="fa-solid fa-shield-halved text-slate-950"></i> Protected Showcase Data
                </div>
            </div>
        @endif
    @endauth

    @if(session('warning'))
        <div class="fixed top-12 right-4 z-50 max-w-md p-3.5 bg-amber-50 border border-amber-300 text-amber-900 rounded-xl shadow-lg flex items-start gap-3" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)">
            <i class="fa-solid fa-triangle-exclamation text-amber-600 text-base mt-0.5"></i>
            <div class="flex-1 text-xs">
                <p class="font-bold">Demo Mode Notice</p>
                <p class="mt-0.5 text-slate-700 font-medium">{{ session('warning') }}</p>
            </div>
            <button type="button" @click="show = false" class="text-amber-800 hover:text-amber-950 text-sm font-bold leading-none cursor-pointer">&times;</button>
        </div>
    @endif

    @hasSection('body')
        @yield('body')
    @else
        <div class="flex min-h-screen flex-col lg:flex-row">
            @auth
                @if(strtolower(trim(auth()->user()->role ?? '')) === 'manager')
                    @include('layouts.partials.manager-sidebar')
                @elseif(in_array(strtolower(trim(auth()->user()->role ?? '')), ['finance', 'fin']))
                    @include('layouts.partials.finance-sidebar')
                @else
                    @include('layouts.partials.staff-sidebar')
                @endif
            @endauth

            <main class="flex-1 w-full px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-6 pb-28 md:pb-8">
                @yield('content')
            </main>

            @include('layouts.partials.bottom-nav')
        </div>
    @endif

    @stack('scripts')
</body>
</html>
