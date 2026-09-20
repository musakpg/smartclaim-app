<!DOCTYPE html>
<html lang="en" x-data="{ isMobileSidebarOpen: false }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Auditor Profile</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased"
    :class="isMobileSidebarOpen ? 'overflow-hidden lg:overflow-auto' : ''">

    <div class="min-h-screen flex flex-col lg:flex-row">

        <!-- Finance Sidebar Partial -->
        @include('layouts.partials.finance-sidebar')

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Mobile Sticky Top Header -->
            <header
                class="lg:hidden flex items-center justify-between bg-[#0d1527] border-b border-slate-800 px-4 py-3 sticky top-0 z-30 shadow-md">
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-8 h-8 rounded-lg bg-[#00d1b2]/10 border border-[#00d1b2]/20 flex items-center justify-center text-[#00d1b2]">
                        <i class="fa-solid fa-shield text-sm"></i>
                    </div>
                    <div>
                        <span class="text-sm font-black text-white tracking-tight leading-none block">SmartClaim</span>
                        <span class="text-[9px] font-bold text-[#00d1b2] tracking-wider uppercase block">Finance
                            Portal</span>
                    </div>
                </div>

                <button type="button" @click="isMobileSidebarOpen = true"
                    class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-800 text-slate-300 hover:text-white transition cursor-pointer">
                    <i class="fa-solid fa-bars text-sm"></i>
                </button>
            </header>

            <!-- Main Page Content Area -->
            <main class="flex-1 p-4 md:p-8 max-w-4xl mx-auto w-full pb-24 lg:pb-8 overflow-y-auto">
                <div class="space-y-6">

                    <!-- Title & Header -->
                    <div class="border-b border-slate-200 pb-5">
                        <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Auditor Profile
                            Security</h1>
                        <p class="text-xs md:text-sm text-slate-500">Manage account authentication credentials and
                            device security information for financial administrators.</p>
                    </div>

                    <!-- Profile Card -->
                    <div class="bg-white p-5 md:p-7 rounded-3xl border border-slate-200/60 shadow-2xs space-y-6">
                        <div class="flex items-center gap-4 border-b border-slate-100 pb-5">
                            <div
                                class="w-12 h-12 md:w-14 md:h-14 bg-slate-900 text-emerald-400 rounded-2xl flex items-center justify-center text-lg md:text-xl font-black border border-slate-800 shadow-inner shrink-0 font-mono">
                                {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 2)) : 'FA' }}
                            </div>
                            <div class="truncate">
                                <h3 class="text-sm md:text-base font-black text-slate-900 leading-tight truncate">
                                    {{ Auth::check() ? Auth::user()->name : 'Finance Auditor Node' }}
                                </h3>
                                <p class="text-[10px] md:text-xs font-mono text-slate-400 truncate mt-0.5">Authority:
                                    Level 2 Admin (Aero Art Accounts)</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-semibold">
                            <div class="space-y-1.5">
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide">Account
                                    Secure Identity</span>
                                <div
                                    class="p-3 bg-slate-50 rounded-xl font-bold text-slate-800 border border-slate-100 text-xs md:text-sm truncate">
                                    {{ Auth::check() ? Auth::user()->name : 'Finance Officer Account' }}
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide">System
                                    Registered Email</span>
                                <div
                                    class="p-3 bg-slate-50 rounded-xl font-mono text-slate-500 border border-slate-100 text-xs md:text-sm truncate">
                                    {{ Auth::check() ? Auth::user()->email : 'finance@aeroart.com' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Corporate Security Policy Notice -->
                    <div
                        class="bg-amber-50/60 p-4 sm:p-5 rounded-2xl border border-amber-100 text-[11px] md:text-xs text-amber-900 space-y-1.5 leading-relaxed shadow-3xs">
                        <p
                            class="font-bold uppercase text-[10px] tracking-wider flex items-center gap-1.5 text-amber-800">
                            <i class="fa-solid fa-triangle-exclamation text-amber-600"></i> Corporate Access Protection
                            Policy
                        </p>
                        <p class="text-amber-800/90">
                            This account holds executive approval authority for Aero Art Sdn Bhd corporate compensation
                            funds. Any password changes or portal identity recoveries must be channeled directly through
                            the network systems director to maintain external audit compliance.
                        </p>
                    </div>

                </div>
            </main>
        </div>
    </div>
</body>

</html>