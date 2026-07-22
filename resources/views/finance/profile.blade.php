<!DOCTYPE html>
<html lang="en" x-data="{ isMobileSidebarOpen: false, isAuditingOpen: true }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Auditor Profile</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" :class="isMobileSidebarOpen ? 'overflow-hidden' : ''">

    <div class="flex min-h-screen flex-col lg:flex-row">
        
        <header class="lg:hidden bg-[#0f172a] px-4 py-4 flex items-center justify-between sticky top-0 z-40 shadow-sm text-slate-200">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-shield-halved text-emerald-400 text-xl"></i>
                <div>
                    <span class="font-black text-sm tracking-tight text-white block leading-tight">SmartClaim</span>
                    <span class="text-[9px] font-bold text-blue-400 uppercase tracking-wider">Aero Art Finance</span>
                </div>
            </div>
            <button type="button" @click="isMobileSidebarOpen = true" class="w-9 h-9 flex items-center justify-center bg-slate-800 rounded-xl text-white cursor-pointer transition-all">
                <i class="fa-solid fa-bars text-base"></i>
            </button>
        </header>

        <div x-show="isMobileSidebarOpen" x-cloak class="lg:hidden fixed inset-0 z-50 flex" role="dialog" aria-modal="true">
            <div x-show="isMobileSidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="isMobileSidebarOpen = false"></div>

            <div x-show="isMobileSidebarOpen" x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="relative flex w-full max-w-xs flex-1 flex-col bg-[#0f172a] pt-5 pb-4 text-slate-200">
                <div class="absolute top-4 right-4">
                    <button type="button" @click="isMobileSidebarOpen = false" class="w-8 h-8 flex items-center justify-center bg-slate-800 rounded-lg text-slate-400 cursor-pointer">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="px-6 pb-4 border-b border-slate-800 flex items-center gap-2.5">
                    <i class="fa-solid fa-shield-halved text-emerald-400 text-xl"></i>
                    <div>
                        <span class="font-black text-sm tracking-tight text-white block leading-tight">SmartClaim</span>
                        <span class="text-[9px] font-bold text-blue-400 uppercase tracking-wider">Aero Art Finance Portal</span>
                    </div>
                </div>
                
                <nav class="mt-4 flex-1 px-4 space-y-1 overflow-y-auto" x-data="{ isAuditingOpenMobile: false }">
                    <a href="{{ route('finance.dashboard') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white">
                        <i class="fa-solid fa-chart-pie text-base"></i> Finance Dashboard
                    </a>
                    <div>
                        <button type="button" @click.prevent="isAuditingOpenMobile = !isAuditingOpenMobile" 
                                :class="isAuditingOpenMobile ? 'text-white font-semibold' : 'text-slate-400 hover:text-white'"
                                class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm transition-all cursor-pointer">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-magnifying-glass-chart text-base" 
                                :class="isAuditingOpenMobile ? 'text-emerald-400' : 'text-slate-400'"></i> 
                                <span>Claims Auditing</span>
                            </span>
                            <i class="fa-solid text-[10px] transition-transform duration-200" 
                            :class="isAuditingOpenMobile ? 'fa-chevron-down rotate-180' : 'fa-chevron-right'"></i>
                        </button>
                        
                        <div x-show="isAuditingOpenMobile" class="pl-6 mt-1 space-y-1 py-1 bg-slate-900/40 rounded-xl border border-slate-800">
                            <a href="{{ route('finance.auditing') }}?status=Pending" class="px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white flex items-center justify-between gap-2 block w-full text-left">
                                <span class="flex items-center gap-2"><i class="fa-solid fa-hourglass-half text-[11px]"></i> Pending Verification</span>
                            </a>
                            <a href="{{ route('finance.auditing') }}?status=Approved" class="px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white flex items-center gap-2 block w-full text-left"><i class="fa-solid fa-circle-check text-[11px]"></i> Approved Claims</a>
                            <a href="{{ route('finance.auditing') }}?status=Rejected" class="px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white flex items-center gap-2 block w-full text-left"><i class="fa-solid fa-circle-xmark text-[11px]"></i> Rejected Claims</a>
                            <a href="{{ route('finance.auditing') }}?status=Reimbursed" class="px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white flex items-center gap-2 block w-full text-left"><i class="fa-solid fa-money-bill-wave text-[11px]"></i> Reimbursed Claims</a>
                        </div>
                    </div>
                    <a href="{{ route('finance.reports') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white">
                        <i class="fa-solid fa-chart-line"></i> Reports & Analytics
                    </a>
                    <a href="{{ route('finance.profile') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold bg-slate-800 text-white">
                        <i class="fa-solid fa-user-shield text-emerald-400"></i> My Profile
                    </a>
                    <a href="{{ route('logout') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-rose-500/20 hover:text-rose-400">
                        <i class="fa-solid fa-door-open"></i> Sign Out
                    </a>
                </nav>
            </div>
        </div>

        <aside class="hidden lg:flex fixed inset-y-0 left-0 z-50 w-64 bg-[#0f172a] flex-col h-screen sticky top-0 text-slate-200"
            x-data="{ isAuditingOpen: false }"> <div class="px-6 py-5 border-b border-slate-800 flex items-center gap-2.5">
                <i class="fa-solid fa-shield-halved text-emerald-400 text-2xl"></i>
                <div>
                    <span class="font-black text-base tracking-tight text-white block leading-tight">SmartClaim</span>
                    <span class="text-[10px] font-bold text-blue-400 uppercase tracking-wider">Aero Art Finance Portal</span>
                </div>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
                <a href="{{ route('finance.dashboard') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white transition-all">
                    <i class="fa-solid fa-chart-pie text-base"></i> Finance Dashboard
                </a>
                
                <div>
                    <button type="button" @click.prevent="isAuditingOpen = !isAuditingOpen" 
                            :class="isAuditingOpen ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-900 hover:text-white'"
                            class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm transition-all cursor-pointer">
                        
                        <span class="flex items-center gap-3 pointer-events-none">
                            <i class="fa-solid fa-magnifying-glass-chart text-base transition-colors duration-200"
                            :class="isAuditingOpen ? 'text-emerald-400' : 'text-slate-400'"></i> 
                            <span>Claims Auditing</span>
                        </span>
                        
                        <i class="fa-solid text-[10px] transition-transform duration-200 pointer-events-none" 
                        :class="isAuditingOpen ? 'fa-chevron-down rotate-180 text-white' : 'fa-chevron-right text-slate-400'"></i>
                    </button>
                    
                    <div x-show="isAuditingOpen" x-cloak x-transition class="pl-6 mt-1 space-y-1 py-1 bg-slate-900/40 rounded-xl border border-slate-800">
                        <a href="{{ route('finance.auditing') }}?status=Pending" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white transition-all flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><i class="fa-solid fa-hourglass-half text-[11px]"></i> Pending Verification</span>
                        </a>
                        <a href="{{ route('finance.auditing') }}?status=Approved" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white transition-all flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-[11px]"></i> Approved Claims
                        </a>
                        <a href="{{ route('finance.auditing') }}?status=Rejected" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white transition-all flex items-center gap-2">
                            <i class="fa-solid fa-circle-xmark text-[11px]"></i> Rejected Claims
                        </a>
                        <a href="{{ route('finance.auditing') }}?status=Reimbursed" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white transition-all flex items-center gap-2">
                            <i class="fa-solid fa-money-bill-wave text-[11px]"></i> Reimbursed Claims
                        </a>
                    </div>
                </div>

                <a href="{{ route('finance.reports') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white transition-all">
                    <i class="fa-solid fa-chart-line text-base"></i> Reports & Analytics
                </a>
                
                <a href="{{ route('finance.profile') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold bg-slate-800 text-white transition-all">
                    <i class="fa-solid fa-user-shield text-emerald-400 text-base"></i> My Profile
                </a>

                <a href="{{ route('logout') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-rose-950/60 hover:text-rose-400 transition-all">
                    <i class="fa-solid fa-door-open text-base"></i> Sign Out
                </a>
            </nav>
        </aside>

        <main class="flex-1 p-4 md:p-8 max-w-4xl mx-auto w-full pb-24 lg:pb-8">
            <div class="space-y-6">
                
                <div class="border-b border-slate-200 pb-5">
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Auditor Profile Security</h1>
                    <p class="text-xs md:text-sm text-slate-500">Manage account authentication credentials and device security information for financial administrators.</p>
                </div>

                <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-2xs space-y-6">
                    <div class="flex items-center gap-4 border-b border-slate-100 pb-5">
                        <div class="w-12 h-12 md:w-14 md:h-14 bg-slate-900 text-emerald-400 rounded-2xl flex items-center justify-center text-lg md:text-xl font-black border border-slate-800 shadow-inner shrink-0">
                            FA
                        </div>
                        <div class="truncate">
                            <h3 class="text-sm md:text-base font-black text-slate-900 leading-tight truncate">Finance Auditor Node</h3>
                            <p class="text-[10px] md:text-xs font-mono text-slate-400 truncate">Authority: Level 2 Admin (Aero Art Accounts)</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-semibold">
                        <div class="space-y-1">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide">Account Secure Identity</span>
                            <div class="p-3 bg-slate-50 rounded-xl font-bold text-slate-800 border border-slate-100 text-xs md:text-sm truncate">
                                @if(Auth::check()) {{ Auth::user()->name }} @else Finance Officer Account @endif
                            </div>
                        </div>
                        <div class="space-y-1">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide">System Registered Email</span>
                            <div class="p-3 bg-slate-50 rounded-xl font-mono text-slate-500 border border-slate-100 text-xs md:text-sm truncate">
                                @if(Auth::check()) {{ Auth::user()->email }} @else finance@aeroart.com @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-amber-50/50 p-4 rounded-2xl border border-amber-100 text-[11px] md:text-xs text-amber-800 space-y-1 leading-relaxed shadow-3xs">
                    <p class="font-bold uppercase text-[10px] tracking-wider flex items-center gap-1 text-amber-700">
                        <i class="fa-solid fa-triangle-exclamation text-amber-500"></i> Corporate Access Protection Policy
                    </p>
                    <p class="text-amber-700/90">This account holds absolute absolute executive approval authority for Aero Art Sdn Bhd corporate compensation funds. Any password changes or portal identity recoveries must be channeled directly through the network systems director to maintain external audit compliance..</p>
                </div>

            </div>
        </main>
    </div>
</body>
</html>