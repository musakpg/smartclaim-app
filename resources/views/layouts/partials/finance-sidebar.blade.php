<!-- Mobile Hamburger Button & Header -->
<div class="lg:hidden bg-[#0d1527] text-white flex items-center justify-between p-4 sticky top-0 z-30 w-full shrink-0 shadow-sm border-b border-slate-800">
    <div class="flex items-center gap-3">
        <div class="w-7 h-7 rounded-lg bg-[#00d1b2]/10 border border-[#00d1b2]/20 flex items-center justify-center text-[#00d1b2] shadow-inner">
            <i class="fa-solid fa-shield text-sm"></i>
        </div>
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
<div x-show="isMobileSidebarOpen" x-cloak x-transition:enter="transition-opacity ease-linear duration-300"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0" @click="isMobileSidebarOpen = false"
    class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-40 lg:hidden">
</div>

<aside :class="isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="fixed inset-y-0 left-0 z-50 w-64 bg-[#0d1527] border-r border-slate-800 text-slate-300 flex flex-col justify-between transition-transform duration-300 ease-in-out shrink-0 font-sans select-none lg:static lg:h-screen lg:sticky lg:top-0"
    x-data="{ 
        activeDropdown: '{{ request()->routeIs('finance.auditing*') ? 'auditing' : '' }}',
        toggle(menu) {
            this.activeDropdown = this.activeDropdown === menu ? '' : menu;
        }
    }">

    <div class="px-6 py-5 border-b border-slate-800/80 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3">
            <div
                class="w-9 h-9 rounded-xl bg-[#00d1b2]/10 border border-[#00d1b2]/20 flex items-center justify-center text-[#00d1b2] shrink-0 shadow-inner">
                <i class="fa-solid fa-shield text-base"></i>
            </div>
            <div>
                <h1 class="font-extrabold text-white text-base tracking-tight leading-tight">SmartClaim</h1>
                <p class="text-[10px] font-bold text-[#00d1b2] tracking-wider uppercase">Aero Art Finance Portal</p>
            </div>
        </div>

        <button type="button" @click="isMobileSidebarOpen = false"
            class="lg:hidden w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer">
            <i class="fa-solid fa-xmark text-base"></i>
        </button>
    </div>

    <nav class="flex-1 px-4 py-4 space-y-1.5 overflow-y-auto text-xs font-semibold" style="scrollbar-gutter: stable;">

        <a href="{{ route('finance.dashboard') }}"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('finance.dashboard') ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
            <i class="fa-solid fa-chart-pie text-sm w-4 text-center"></i>
            <span>Finance Dashboard</span>
        </a>

        <div x-data="{ open: {{ request()->is('finance/auditing*') || request()->routeIs('finance.auditing*') ? 'true' : 'false' }} }">
            <button type="button" @click="open = !open"
                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-200 text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 cursor-pointer"
                :class="open ? 'text-slate-200 bg-slate-800/40' : ''">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-magnifying-glass-chart text-sm w-4 text-center"></i>
                    <span>Claims Auditing</span>
                </div>
                <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-300 ease-out"
                    :class="open ? 'rotate-180 text-emerald-400' : 'text-slate-500'"></i>
            </button>

            <div class="grid transition-[grid-template-rows,opacity] duration-300 ease-out"
                :class="open ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'"
                x-cloak>
                <div class="overflow-hidden">
                    <div class="pl-7 pr-2 pt-1 pb-1.5 space-y-1">
                        <a href="{{ route('finance.auditing', ['status' => 'Pending']) }}"
                            class="flex items-center justify-between px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 transition-colors {{ request('status') === 'Pending' || (request()->routeIs('finance.auditing') && !request('status')) ? 'text-emerald-400 font-bold bg-emerald-500/10' : '' }}">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-hourglass-half text-[11px] text-amber-400"></i>
                                <span>Pending Verification</span>
                            </div>
                            @if(($pendingCount ?? 0) > 0)
                                <span
                                    class="px-1.5 py-0.5 rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30 font-black text-[9px] font-mono">
                                    {{ $pendingCount }}
                                </span>
                            @endif
                        </a>

                        <a href="{{ route('finance.auditing', ['status' => 'Approved']) }}"
                            class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 transition-colors {{ request('status') === 'Approved' ? 'text-emerald-400 font-bold bg-emerald-500/10' : '' }}">
                            <i class="fa-solid fa-circle-check text-[11px] text-emerald-400"></i>
                            <span>Approved Claims</span>
                        </a>

                        <a href="{{ route('finance.auditing', ['status' => 'Rejected']) }}"
                            class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 transition-colors {{ request('status') === 'Rejected' ? 'text-emerald-400 font-bold bg-emerald-500/10' : '' }}">
                            <i class="fa-solid fa-circle-xmark text-[11px] text-rose-400"></i>
                            <span>Rejected Claims</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <a href="{{ route('finance.disbursement') }}"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('finance.disbursement*') ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
            <i class="fa-solid fa-money-bill-transfer text-sm w-4 text-center"></i>
            <span>Payment Disbursement</span>
        </a>

        <a href="{{ route('finance.cash-advances.index') }}"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('finance.cash-advances.index') ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 font-bold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
            <i class="fa-solid fa-wallet text-sm w-4 text-center"></i>
            <span>Cash Advance Reconciliation</span>
        </a>

        <!-- 4. BENEFICIARY STAFF DIRECTORY -->
        <a href="{{ route('finance.staff_directory') }}"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('finance.staff_directory*') ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 font-bold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
            <i class="fa-solid fa-users-viewfinder text-sm w-4 text-center"></i>
            <span>Staff Directory</span>
        </a>

        <a href="{{ route('finance.reports') }}"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-200 {{ request()->routeIs('finance.reports') ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60' }}">
            <i class="fa-solid fa-chart-simple text-sm w-4 text-center"></i>
            <span>Reports & BI Analytics</span>
        </a>
    </nav>

    <div class="p-4 border-t border-slate-800/80 space-y-1 text-xs font-semibold shrink-0">
        <a href="{{ route('finance.profile') }}"
            class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/60 transition-colors {{ request()->routeIs('finance.profile') ? 'text-emerald-400 font-bold bg-emerald-500/10' : '' }}">
            <i class="fa-solid fa-user-shield text-sm w-4 text-center"></i>
            <span>My Profile</span>
        </a>

        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit"
                class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 transition-colors text-left font-semibold text-xs">
                <i class="fa-solid fa-right-from-bracket text-sm w-4 text-center"></i>
                <span>Sign Out</span>
            </button>
        </form>
    </div>
</aside>
