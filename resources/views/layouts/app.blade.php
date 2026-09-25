<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim | Manager Portal</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-[#f8fafc] text-[#1e293b]">

<div class="flex flex-col lg:flex-row min-h-screen" x-data="{ isMobileSidebarOpen: false }">
    <!-- Mobile Header -->
    <div class="lg:hidden fixed top-0 left-0 right-0 h-16 bg-[#0b1727] text-white flex items-center justify-between px-4 z-40 shadow-sm">
        <div class="flex items-center gap-3">
            <i class="fa-solid fa-crown text-amber-400 text-xl"></i>
            <h1 class="font-bold text-sm tracking-tight">SmartClaim</h1>
        </div>
        <button type="button" @click="isMobileSidebarOpen = true" class="text-slate-300 hover:text-white p-2 cursor-pointer">
            <i class="fa-solid fa-bars text-xl"></i>
        </button>
    </div>

    <!-- Mobile Backdrop -->
    <div x-show="isMobileSidebarOpen" style="display: none;" class="fixed inset-0 bg-slate-900/60 z-40 lg:hidden backdrop-blur-sm" @click="isMobileSidebarOpen = false"></div>

    <aside class="w-64 bg-[#0b1727] text-white shrink-0 fixed lg:static inset-y-0 left-0 z-50 transform transition-transform duration-300 ease-in-out lg:translate-x-0"
        :class="isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        <div class="p-6">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-crown text-amber-400 text-2xl"></i>
                <div>
                    <h1 class="font-bold text-lg">SmartClaim</h1>
                    <p class="text-[10px] text-emerald-400 font-bold uppercase">Manager Portal</p>
                </div>
            </div>
        </div>

        <nav class="px-4 py-4 space-y-2">
            <a href="{{ route('manager.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white">
                <i class="fa-solid fa-chart-pie"></i> Dashboard
            </a>
            <a href="{{ route('manager.verification') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white">
                <i class="fa-solid fa-file-check"></i> Claims Verification
            </a>
            <a href="{{ route('manager.reports') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold bg-slate-800 text-white">
                <i class="fa-solid fa-chart-line"></i> Reports & BI Analytics
            </a>
        </nav>
    </aside>

    <main class="flex-1 p-8 pt-20 lg:pt-8 overflow-x-hidden">
        @yield('content')
    </main>
</div>
</body>
</html>
