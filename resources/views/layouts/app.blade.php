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
    </style>
    @stack('styles')
</head>
<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" :class="isMobileSidebarOpen ? 'overflow-hidden lg:overflow-auto' : ''">
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

            <main class="flex-1 w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6">
                @yield('content')
            </main>
        </div>
    @endif

    @stack('scripts')
</body>
</html>
