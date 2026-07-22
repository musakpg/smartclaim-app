<!DOCTYPE html>
<html lang="en"
    x-data="{ isMobileSidebarOpen: false, isAuditingOpen: false, isAdminOpen: true, activeSubTab: 'mileage_rates' }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Mileage Rates Administration</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" :class="isMobileSidebarOpen ? 'overflow-hidden' : ''">

    <div class="flex min-h-screen flex-col lg:flex-row">

        <header
            class="lg:hidden bg-[#0f172a] px-4 py-4 flex items-center justify-between sticky top-0 z-40 shadow-sm text-slate-200">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-crown text-amber-400 text-xl"></i>
                <div>
                    <span class="font-black text-sm tracking-tight text-white block leading-tight">SmartClaim</span>
                    <span class="text-[9px] font-bold text-emerald-400 uppercase tracking-wider">Aero Art Manager</span>
                </div>
            </div>
            <button type="button" @click="isMobileSidebarOpen = true"
                class="w-9 h-9 flex items-center justify-center bg-slate-800 rounded-xl text-white cursor-pointer transition-all">
                <i class="fa-solid fa-bars text-base"></i>
            </button>
        </header>

        <div x-show="isMobileSidebarOpen" x-cloak class="lg:hidden fixed inset-0 z-50 flex" role="dialog"
            aria-modal="true">
            <div x-show="isMobileSidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"
                @click="isMobileSidebarOpen = false"></div>

            <div x-show="isMobileSidebarOpen" x-transition:enter="transition ease-in-out duration-300 transform"
                x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in-out duration-300 transform"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                class="relative flex w-full max-w-xs flex-1 flex-col bg-[#0f172a] pt-5 pb-4 text-slate-200">
                <div class="absolute top-4 right-4">
                    <button type="button" @click="isMobileSidebarOpen = false"
                        class="w-8 h-8 flex items-center justify-center bg-slate-800 rounded-lg text-slate-400 cursor-pointer">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="px-6 pb-4 border-b border-slate-800 flex items-center gap-2.5">
                    <i class="fa-solid fa-crown text-amber-400 text-xl"></i>
                    <div>
                        <span class="font-black text-sm tracking-tight text-white block leading-tight">SmartClaim</span>
                        <span class="text-[9px] font-bold text-emerald-400 uppercase tracking-wider">Aero Art Manager
                            Portal</span>
                    </div>
                </div>

                <nav class="mt-4 flex-1 px-4 space-y-1 overflow-y-auto"
                    x-data="{ isAuditingOpenMobile: false, isAdminOpenMobile: true }">
                    <a href="{{ route('manager.dashboard') }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white">
                        <i class="fa-solid fa-chart-pie text-base"></i> Dashboard
                    </a>

                    <div>
                        <button type="button" @click.prevent="isAuditingOpenMobile = !isAuditingOpenMobile"
                            :class="isAuditingOpenMobile ? 'text-white' : 'text-slate-400'"
                            class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-medium transition-all cursor-pointer">
                            <span class="flex items-center gap-3"><i class="fa-solid fa-shield-check text-base"
                                    :class="isAuditingOpenMobile ? 'text-emerald-400' : 'text-slate-400'"></i> Claims
                                Verification</span>
                            <i class="fa-solid text-[10px] transition-transform duration-200"
                                :class="isAuditingOpenMobile ? 'fa-chevron-down rotate-180' : 'fa-chevron-right'"></i>
                        </button>
                        <div x-show="isAuditingOpenMobile" x-cloak
                            class="pl-6 mt-1 space-y-1 py-1 bg-slate-900/40 rounded-xl border border-slate-800">
                            <a href="{{ route('manager.verification') }}?status=Pre-Approved"
                                class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white text-left flex items-center justify-between gap-1 transition-all">
                                <span class="flex items-center gap-2"><i
                                        class="fa-solid fa-hourglass-half text-[11px]"></i> Pending Review</span>
                                @if(($preApprovedCount ?? 0) > 0) <span
                                    class="px-1.5 py-0.5 bg-amber-500 text-slate-950 font-black rounded-sm text-[8px] font-mono animate-pulse">{{ $preApprovedCount }}</span>
                                @endif
                            </a>
                            <a href="{{ route('manager.verification') }}?status=Approved"
                                class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white flex items-center gap-2"><i
                                    class="fa-solid fa-circle-check text-[11px]"></i> Accepted Review</a>
                        </div>
                    </div>

                    <div>
                        <button type="button" @click.prevent="isAdminOpenMobile = !isAdminOpenMobile"
                            :class="isAdminOpenMobile ? 'text-white font-semibold' : 'text-slate-400 hover:text-white'"
                            class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm transition-all cursor-pointer">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-sliders-file text-base"
                                    :class="isAdminOpenMobile || activeSubTab === 'mileage_rates' ? 'text-emerald-400' : 'text-slate-400'"></i>
                                <span>Administration</span>
                            </span>
                            <i class="fa-solid text-[10px] transition-transform duration-200"
                                :class="isAdminOpenMobile ? 'fa-chevron-down rotate-180 text-white' : 'fa-chevron-right text-slate-400'"></i>
                        </button>

                        <div x-show="isAdminOpenMobile" x-cloak
                            class="pl-6 mt-1 space-y-1 py-1 bg-slate-900/40 rounded-xl border border-slate-800 flex flex-col">
                            <a href="{{ route('manager.mileage_rates') }}"
                                :class="activeSubTab === 'mileage_rates' ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-2"><i
                                    class="fa-solid fa-car-tunnel text-[11px]"></i> Mileage Rates</a>
                            <a href="{{ route('manager.expense_categories') }}"
                                :class="activeSubTab === 'expense_categories' ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-2"><i
                                    class="fa-solid fa-folder-tree text-[11px]"></i> Expense Categories</a>
                            <a href="{{ route('manager.user_management') }}"
                                :class="activeSubTab === 'user_management' ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-2"><i
                                    class="fa-solid fa-users-gear text-[11px]"></i> User Management</a>
                            <a href="{{ route('manager.vehicles') }}"
                                :class="activeSubTab === 'vehicles' ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-2"><i
                                    class="fa-solid fa-car text-[11px]"></i> Company Fleet CRUD</a>
                            <a href="{{ route('manager.audit_logs') }}"
                                :class="activeSubTab === 'audit_logs' ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-2"><i
                                    class="fa-solid fa-scroll text-[11px]"></i> Audit Logs</a>
                        </div>
                    </div>

                    <a href="{{ route('manager.reports') }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white">
                        <i class="fa-solid fa-chart-line"></i> Reports & BI Analytics
                    </a>
                    <a href="{{ route('manager.profile') }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white">
                        <i class="fa-solid fa-user-shield"></i> My Profile
                    </a>
                    <a href="{{ route('logout') }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-rose-500/20 hover:text-rose-400">
                        <i class="fa-solid fa-door-open"></i> Sign Out
                    </a>
                </nav>
            </div>
        </div>

        <aside
            class="hidden lg:flex fixed inset-y-0 left-0 z-50 w-64 bg-[#0f172a] flex-col h-screen sticky top-0 text-slate-200">
            <div class="px-6 py-5 border-b border-slate-800 flex items-center gap-2.5">
                <i class="fa-solid fa-crown text-amber-400 text-2xl"></i>
                <div>
                    <span class="font-black text-base tracking-tight text-white block leading-tight">SmartClaim</span>
                    <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider">Aero Art Manager
                        Portal</span>
                </div>
            </div>

            <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
                <a href="{{ route('manager.dashboard') }}"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white transition-all">
                    <i class="fa-solid fa-chart-pie text-base"></i> Dashboard
                </a>

                <div>
                    <button type="button" @click.prevent="isAuditingOpen = !isAuditingOpen"
                        :class="isAuditingOpen ? 'bg-slate-900 text-white font-semibold' : 'text-slate-400 hover:bg-slate-900 hover:text-white'"
                        class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm transition-all cursor-pointer">
                        <span class="flex items-center gap-3 pointer-events-none">
                            <i class="fa-solid fa-shield-check text-base"
                                :class="isAuditingOpen ? 'text-emerald-400' : 'text-slate-400'"></i> Claims Verification
                        </span>
                        <i class="fa-solid text-[10px] transition-transform duration-200 pointer-events-none"
                            :class="isAuditingOpen ? 'fa-chevron-down rotate-180 text-white' : 'fa-chevron-right text-slate-400'"></i>
                    </button>
                    <div x-show="isAuditingOpen" x-cloak x-transition
                        class="pl-6 mt-1 space-y-1 py-1 bg-slate-900/40 rounded-xl border border-slate-800">
                        <a href="{{ route('manager.verification') }}?status=Pre-Approved"
                            class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white text-left flex items-center justify-between gap-1 transition-all">
                            <span class="flex items-center gap-2"><i class="fa-solid fa-hourglass-half text-[11px]"></i>
                                Pending Review</span>
                            @if(($preApprovedCount ?? 0) > 0) <span
                                class="px-1.5 py-0.5 bg-amber-500 text-slate-950 font-black rounded-sm text-[8px] font-mono animate-pulse">{{ $preApprovedCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('manager.verification') }}?status=Approved"
                            class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white text-left flex items-center gap-2"><i
                                class="fa-solid fa-circle-check text-[11px]"></i> Accepted Review</a>
                    </div>
                </div>

                <div>
                    <button type="button" @click.prevent="isAdminOpen = !isAdminOpen"
                        :class="isAdminOpen ? 'bg-slate-900 text-white font-semibold' : 'text-slate-400 hover:bg-slate-900 hover:text-white'"
                        class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm transition-all cursor-pointer">
                        <span class="flex items-center gap-3 pointer-events-none">
                            <i class="fa-solid fa-sliders-file text-base"
                                :class="isAdminOpen || activeSubTab === 'mileage_rates' ? 'text-emerald-400' : 'text-slate-400'"></i>
                            Administration
                        </span>
                        <i class="fa-solid text-[10px] transition-transform duration-200 pointer-events-none"
                            :class="isAdminOpen ? 'fa-chevron-down rotate-180 text-white' : 'fa-chevron-right text-slate-400'"></i>
                    </button>

                    <div x-show="isAdminOpen" x-cloak x-transition
                        class="pl-6 mt-1 space-y-1 py-1 bg-slate-900/40 rounded-xl border border-slate-800 flex flex-col">
                        <a href="{{ route('manager.mileage_rates') }}"
                            :class="activeSubTab === 'mileage_rates' ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs transition-all flex items-center gap-2"><i
                                class="fa-solid fa-car-tunnel text-[11px]"></i> Mileage Rates</a>
                        <a href="{{ route('manager.expense_categories') }}"
                            :class="activeSubTab === 'expense_categories' ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs transition-all flex items-center gap-2"><i
                                class="fa-solid fa-folder-tree text-[11px]"></i> Expense Categories</a>
                        <a href="{{ route('manager.user_management') }}"
                            :class="activeSubTab === 'user_management' ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs transition-all flex items-center gap-2"><i
                                class="fa-solid fa-users-gear text-[11px]"></i> User Management</a>
                        <a href="{{ route('manager.vehicles') }}"
                            :class="activeSubTab === 'vehicles' ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs transition-all flex items-center gap-2"><i
                                class="fa-solid fa-car text-[11px]"></i> Company Fleet CRUD</a>
                        <a href="{{ route('manager.audit_logs') }}"
                            :class="activeSubTab === 'audit_logs' ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs transition-all flex items-center gap-2"><i
                                class="fa-solid fa-scroll text-[11px]"></i> Audit Logs</a>
                    </div>
                </div>

                <a href="{{ route('manager.reports') }}"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white transition-all"><i
                        class="fa-solid fa-chart-line text-base"></i> Reports & BI Analytics</a>
                <a href="{{ route('manager.profile') }}"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white transition-all"><i
                        class="fa-solid fa-user-shield text-base"></i> My Profile</a>
                <a href="{{ route('logout') }}"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-rose-950/60 hover:text-rose-400 transition-all"><i
                        class="fa-solid fa-door-open text-base"></i> Sign Out</a>
            </nav>
        </aside>

        <main class="flex-1 p-4 md:p-8 max-w-4xl mx-auto w-full overflow-hidden">
            <div class="space-y-6">
                <div class="border-b border-slate-200 pb-5">
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Mileage Allowance Rates
                    </h1>
                    <p class="text-xs md:text-sm text-slate-500">Corporate distance financial multi-tier multiplier
                        matrices for Aero Art Sdn Bhd.</p>
                </div>

                @if(session('success'))
                    <div
                        class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-semibold flex items-center gap-2.5 shadow-3xs">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6">

                    <div class="bg-white p-4 md:p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-4">
                        <div
                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-50 pb-2">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-400"><i
                                    class="fa-solid fa-car text-blue-500 mr-1"></i> Car Rate Configuration</span>
                            <span
                                class="px-2.5 py-1 bg-blue-50 text-blue-700 font-mono font-black text-xs rounded-xl self-start sm:self-auto">RM
                                0.60 / KM</span>
                        </div>
                        <form action="#" method="POST" class="space-y-3">
                            @csrf
                            <div class="space-y-1">
                                <label class="block text-[10px] md:text-[11px] font-bold text-slate-600 uppercase">Set
                                    New Car Multiplier Rate</label>
                                <div class="relative text-xs">
                                    <span
                                        class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">RM</span>
                                    <input type="number" step="0.01" name="car_rate" value="0.60" required
                                        class="w-full pl-9 pr-16 py-2.5 bg-slate-50 border border-slate-100 rounded-xl outline-none font-bold font-mono text-slate-800">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">/
                                        KM</span>
                                </div>
                            </div>
                            <button type="submit" disabled
                                class="w-full py-2.5 bg-slate-200 text-slate-400 rounded-xl text-xs font-bold transition-all cursor-not-allowed uppercase tracking-wider border border-slate-300/40">
                                <i class="fa-solid fa-floppy-disk mr-1"></i> Save Changes (Active)
                            </button>
                        </form>
                    </div>

                    <div class="bg-white p-4 md:p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-4">
                        <div
                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-50 pb-2">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-400"><i
                                    class="fa-solid fa-motorcycle text-orange-500 mr-1"></i> Motorcycle
                                Configuration</span>
                            <span
                                class="px-2.5 py-1 bg-orange-50 text-orange-700 font-mono font-black text-xs rounded-xl self-start sm:self-auto">RM
                                0.30 / KM</span>
                        </div>
                        <form action="#" method="POST" class="space-y-3">
                            @csrf
                            <div class="space-y-1">
                                <label class="block text-[10px] md:text-[11px] font-bold text-slate-600 uppercase">Set
                                    New Motorcycle Multiplier Rate</label>
                                <div class="relative text-xs">
                                    <span
                                        class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">RM</span>
                                    <input type="number" step="0.01" name="motor_rate" value="0.30" required
                                        class="w-full pl-9 pr-16 py-2.5 bg-slate-50 border border-slate-100 rounded-xl outline-none font-bold font-mono text-slate-800">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">/
                                        KM</span>
                                </div>
                            </div>
                            <button type="submit" disabled
                                class="w-full py-2.5 bg-slate-200 text-slate-400 rounded-xl text-xs font-bold transition-all cursor-not-allowed uppercase tracking-wider border border-slate-300/40">
                                <i class="fa-solid fa-floppy-disk mr-1"></i> Save Changes (Active)
                            </button>
                        </form>
                    </div>
                </div>

                <div
                    class="bg-slate-900/5 p-4 rounded-2xl border border-slate-100 text-xs text-slate-500 space-y-1.5 leading-relaxed">
                    <p class="font-bold text-slate-800 uppercase text-[10px] tracking-wider flex items-center gap-1">
                        <i class="fa-solid fa-circle-info text-blue-500"></i> Audit Compliance Notice
                    </p>
                    <p>Global multipliers configured here instantly manipulate subsequent logistics form entries.
                        Historical records logged in <strong>Approved</strong> matrices remain untouched to guarantee
                        ledger security integrity.</p>
                </div>
            </div>
        </main>
    </div>
</body>

</html>