<!-- Mobile Hamburger Button & Header -->
<div class="lg:hidden bg-[#0d1527] text-white flex items-center justify-between p-4 sticky top-0 z-30 w-full shrink-0 shadow-sm border-b border-slate-800">
    <div class="flex items-center gap-3">
        <i class="fa-solid fa-crown text-amber-400"></i>
        <h1 class="font-bold text-sm tracking-tight">SmartClaim</h1>
    </div>
    <div class="flex items-center gap-2">
        <div class="lg:hidden">
            <x-system-clock />
        </div>
        <button type="button" @click="isMobileSidebarOpen = true" class="text-slate-300 hover:text-white p-2 cursor-pointer">
            <i class="fa-solid fa-bars text-xl"></i>
        </button>
    </div>
</div>

<!-- Mobile Backdrop -->
<div x-show="isMobileSidebarOpen" x-cloak 
    x-transition:enter="transition-opacity ease-linear duration-200"
    x-transition:enter-start="opacity-0" 
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-150" 
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0" 
    class="fixed inset-0 bg-slate-900/80 z-[55] lg:hidden backdrop-blur-sm" 
    @click="isMobileSidebarOpen = false"></div>

<!-- Manager Sidebar Navigation -->
<aside
    class="w-64 bg-[#0d1527] text-slate-300 min-h-screen flex flex-col border-r border-slate-800 shrink-0 font-sans select-none fixed lg:static top-0 bottom-0 left-0 z-[60] transform transition-transform duration-200 ease-in-out lg:translate-x-0"
    :class="isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'">

    <!-- Brand Header -->
    <div class="px-6 py-5 border-b border-slate-800/80 flex items-center gap-3 shrink-0">
        <div
            class="w-9 h-9 rounded-xl bg-amber-400/10 border border-amber-400/20 flex items-center justify-center text-amber-400 shrink-0 shadow-inner">
            <i class="fa-solid fa-crown text-base"></i>
        </div>
        <div>
            <h1 class="font-extrabold text-white text-base tracking-tight leading-tight">SmartClaim</h1>
            <p class="text-[10px] font-bold text-emerald-400 tracking-wider uppercase">Aero Art Manager Portal</p>
        </div>
    </div>

    <!-- Navigation Menu (Persistent Scrollbar Gutter to Prevent Width Jumps) -->
    <nav class="flex-1 px-4 py-4 space-y-1.5 overflow-y-auto text-xs font-semibold" style="scrollbar-gutter: stable;">

        <!-- 1. DASHBOARD -->
        <a href="{{ route('manager.dashboard') }}"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('manager.dashboard') ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
            <i class="fa-solid fa-chart-pie text-sm w-4 text-center"></i>
            <span>Dashboard</span>
        </a>

        <!-- 2. CLAIMS VERIFICATION -->
        <a href="{{ route('manager.verification') }}"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('manager.verification') ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
            <i class="fa-solid fa-file-circle-check text-sm w-4 text-center"></i>
            <span>Claims Verification</span>
        </a>

        <!-- 3. FLEET MANAGEMENT (DROPDOWN) -->
        <div x-data="{ open: {{ request()->is('manager/vehicles*') || request()->is('manager/vehicle*') || request()->routeIs('manager.vehicles*') || request()->routeIs('manager.vehicle_history*') ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open"
                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 cursor-pointer"
                :class="open ? 'text-slate-200 bg-slate-800/40' : ''">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-car-side text-sm w-4 text-center"></i>
                    <span>Fleet Management</span>
                </div>
                <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-300 ease-out"
                    :class="open ? 'rotate-180 text-emerald-400' : 'text-slate-500'"></i>
            </button>

            <div x-show="open" x-cloak
                x-transition:enter="transition-all ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition-all ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1">
                <div class="pl-7 pr-2 pt-1 pb-1.5 space-y-1">
                    <a href="{{ route('manager.vehicles') }}"
                        class="block px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 transition-colors {{ request()->routeIs('manager.vehicles*') ? 'text-emerald-400 font-bold bg-emerald-500/10' : '' }}">
                        <i class="fa-solid fa-id-card-clip text-[11px] mr-2"></i> Fleet & Verification
                    </a>
                    <a href="{{ route('manager.vehicle_history') }}"
                        class="block px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 transition-colors {{ request()->routeIs('manager.vehicle_history*') ? 'text-emerald-400 font-bold bg-emerald-500/10' : '' }}">
                        <i class="fa-solid fa-clock-rotate-left text-[11px] mr-2"></i> Trip & Usage History
                    </a>
                </div>
            </div>
        </div>

        <!-- 4. ADMINISTRATION (DROPDOWN) -->
        <div x-data="{ open: {{ request()->is('manager/user*') || request()->is('manager/expense*') || request()->is('manager/mileage*') || request()->is('manager/audit*') || request()->routeIs('manager.user_management*') || request()->routeIs('manager.expense_policies*') || request()->routeIs('manager.expense_categories*') || request()->routeIs('manager.mileage_rates*') || request()->routeIs('manager.audit_logs*') || request()->routeIs('manager.audit_reasons*') ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open"
                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 cursor-pointer"
                :class="open ? 'text-slate-200 bg-slate-800/40' : ''">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-sliders text-sm w-4 text-center"></i>
                    <span>Administration</span>
                </div>
                <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-300 ease-out"
                    :class="open ? 'rotate-180 text-emerald-400' : 'text-slate-500'"></i>
            </button>

            <div x-show="open" x-cloak
                x-transition:enter="transition-all ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition-all ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1">
                <div class="pl-7 pr-2 pt-1 pb-1.5 space-y-1">
                    <a href="{{ route('manager.user_management') }}"
                        class="block px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 transition-colors {{ request()->routeIs('manager.user_management*') ? 'text-emerald-400 font-bold bg-emerald-500/10' : '' }}">
                        <i class="fa-solid fa-users-gear text-[11px] mr-2"></i> User Management
                    </a>
                    <a href="{{ route('manager.expense_policies') }}"
                        class="block px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 transition-colors {{ request()->routeIs('manager.expense_policies*') ? 'text-emerald-400 font-bold bg-emerald-500/10' : '' }}">
                        <i class="fa-solid fa-shield-halved text-[11px] mr-2"></i> Expense Policies & Caps
                    </a>
                    <a href="{{ route('manager.expense_categories') }}"
                        class="block px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 transition-colors {{ request()->routeIs('manager.expense_categories*') ? 'text-emerald-400 font-bold bg-emerald-500/10' : '' }}">
                        <i class="fa-solid fa-tags text-[11px] mr-2"></i> Expense Categories
                    </a>
                    <a href="{{ route('manager.mileage_rates') }}"
                        class="block px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 transition-colors {{ request()->routeIs('manager.mileage_rates*') ? 'text-emerald-400 font-bold bg-emerald-500/10' : '' }}">
                        <i class="fa-solid fa-gauge text-[11px] mr-2"></i> Mileage Rates
                    </a>
                    <a href="{{ route('manager.audit_logs') }}"
                        class="block px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 transition-colors {{ request()->routeIs('manager.audit_logs*') ? 'text-emerald-400 font-bold bg-emerald-500/10' : '' }}">
                        <i class="fa-solid fa-list-check text-[11px] mr-2"></i> Audit Logs
                    </a>
                    <a href="{{ route('manager.audit_reasons') }}"
                        class="block px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 transition-colors {{ request()->routeIs('manager.audit_reasons*') ? 'text-emerald-400 font-bold bg-emerald-500/10' : '' }}">
                        <i class="fa-solid fa-clipboard-question text-[11px] mr-2"></i> Audit Exception Codes
                    </a>
                </div>
            </div>
        </div>

        <!-- 5. AI & SYSTEM HEALTH (DROPDOWN) -->
        <div x-data="{ open: {{ request()->is('manager/model-evaluation*') || request()->is('manager/ai-feedback*') || request()->routeIs('manager.model-evaluation*') || request()->routeIs('manager.ai_feedback*') ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open"
                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 cursor-pointer"
                :class="open ? 'text-slate-200 bg-slate-800/40' : ''">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-microchip text-sm w-4 text-center"></i>
                    <span>AI & System Health</span>
                </div>
                <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-300 ease-out"
                    :class="open ? 'rotate-180 text-emerald-400' : 'text-slate-500'"></i>
            </button>

            <div x-show="open" x-cloak
                x-transition:enter="transition-all ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition-all ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1">
                <div class="pl-7 pr-2 pt-1 pb-1.5 space-y-1">
                    <a href="{{ route('manager.model-evaluation') }}"
                        class="block px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 transition-colors {{ request()->routeIs('manager.model-evaluation*') ? 'text-emerald-400 font-bold bg-emerald-500/10' : '' }}">
                        <i class="fa-solid fa-chart-line text-[11px] mr-2"></i> Model Benchmark
                    </a>
                    <a href="{{ route('manager.ai_feedback') }}"
                        class="block px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 transition-colors {{ request()->routeIs('manager.ai_feedback*') ? 'text-emerald-400 font-bold bg-emerald-500/10' : '' }}">
                        <i class="fa-solid fa-brain text-[11px] mr-2"></i> Active Learning Ledger
                    </a>
                </div>
            </div>
        </div>

        <!-- 6. PRICE INTELLIGENCE -->
        <a href="{{ route('manager.price_intelligence') }}"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('manager.price_intelligence') ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
            <i class="fa-solid fa-scale-balanced text-sm w-4 text-center"></i>
            <span>Price Intelligence</span>
        </a>

        <!-- 7. REPORTS & BI ANALYTICS (DROPDOWN) -->
        <div x-data="{ open: {{ request()->is('manager/reports*') || request()->is('manager/sla*') || request()->routeIs('manager.reports*') ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open"
                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 cursor-pointer"
                :class="open ? 'text-slate-200 bg-slate-800/40' : ''">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-chart-simple text-sm w-4 text-center"></i>
                    <span>Reports & BI Analytics</span>
                </div>
                <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-300 ease-out"
                    :class="open ? 'rotate-180 text-emerald-400' : 'text-slate-500'"></i>
            </button>

            <div x-show="open" x-cloak
                x-transition:enter="transition-all ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition-all ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1">
                <div class="pl-7 pr-2 pt-1 pb-1.5 space-y-1">
                    <a href="{{ route('manager.reports') }}"
                        class="block px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 transition-colors {{ request()->routeIs('manager.reports') ? 'text-emerald-400 font-bold bg-emerald-500/10' : '' }}">
                        <i class="fa-solid fa-chart-pie text-[11px] mr-2"></i> Overview
                    </a>
                    <a href="{{ route('manager.reports.sla_analytics') }}"
                        class="block px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 transition-colors {{ request()->routeIs('manager.reports.sla_analytics*') ? 'text-emerald-400 font-bold bg-emerald-500/10' : '' }}">
                        <i class="fa-solid fa-stopwatch text-[11px] mr-2"></i> SLA & Approval Velocity
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Bottom Actions -->
    <div class="p-4 border-t border-slate-800/80 space-y-1 text-xs font-semibold shrink-0 pb-28 lg:pb-4">
        <a href="{{ route('manager.profile') }}"
            class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/60 transition-colors {{ request()->routeIs('manager.profile') ? 'text-emerald-400 font-bold bg-emerald-500/10' : '' }}">
            <i class="fa-solid fa-user-gear text-sm w-4 text-center"></i>
            <span>My Profile</span>
        </a>

        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit"
                class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 transition-colors text-left font-semibold text-xs cursor-pointer">
                <i class="fa-solid fa-right-from-bracket text-sm w-4 text-center"></i>
                <span>Sign Out</span>
            </button>
        </form>
    </div>
</aside>
