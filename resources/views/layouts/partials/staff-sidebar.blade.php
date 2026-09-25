{{-- resources/views/layouts/partials/staff-sidebar.blade.php --}}

<!-- Mobile Top Header Bar -->
<header
    class="lg:hidden bg-white border-b border-slate-200 px-4 py-3 flex items-center justify-between sticky top-0 z-40 shadow-xs">
    <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-lg bg-slate-900 text-white flex items-center justify-center shadow-xs">
            <i class="fa-solid fa-wallet text-sm"></i>
        </div>
        <div>
            <span class="text-sm font-black text-slate-900 tracking-tight leading-none block">SmartClaim</span>
            <span class="text-[9px] font-bold text-slate-400 tracking-wider uppercase block">Staff Portal</span>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <div class="lg:hidden">
            <x-system-clock />
        </div>
        @include('layouts.partials.notification-bell')
        <button type="button" @click="isMobileSidebarOpen = true"
            class="w-9 h-9 flex items-center justify-center bg-slate-100 hover:bg-slate-200 rounded-xl text-slate-700 cursor-pointer transition">
            <i class="fa-solid fa-bars text-sm"></i>
        </button>
    </div>
</header>

<!-- Mobile Backdrop -->
<div x-show="isMobileSidebarOpen" x-cloak 
    x-transition:enter="transition-opacity ease-linear duration-300"
    x-transition:enter-start="opacity-0" 
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-300" 
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0" 
    class="fixed inset-0 bg-slate-900/60 z-40 lg:hidden backdrop-blur-sm" 
    @click="isMobileSidebarOpen = false"></div>

<!-- Unified Persistent Sidebar (Mobile & Desktop) -->
<aside :class="isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200 flex flex-col justify-between transition-transform duration-300 ease-in-out shrink-0 font-sans select-none lg:static lg:h-screen lg:sticky lg:top-0"
    x-data="{ isClaimsOpen: {{ request()->routeIs('claims.*') ? 'true' : 'false' }} }">
    
    <div>
        <!-- Brand Header (Desktop only, mobile has top bar) -->
        <div class="hidden lg:flex px-6 py-5 border-b border-slate-100 items-center gap-3 shrink-0">
            <div class="w-9 h-9 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0 shadow-xs">
                <i class="fa-solid fa-wallet text-base"></i>
            </div>
            <div>
                <h1 class="font-extrabold text-slate-900 text-base tracking-tight leading-tight">SmartClaim</h1>
                <p class="text-[10px] font-bold text-slate-400 tracking-wider uppercase">Aero Art Staff Portal</p>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="px-4 py-4 space-y-1.5 overflow-y-auto text-xs font-semibold">
            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('dashboard') ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <i class="fa-solid fa-house text-sm w-4 text-center"></i>
                <span>Dashboard</span>
            </a>

            <!-- Claims Dropdown -->
            <div>
                <button type="button" @click="isClaimsOpen = !isClaimsOpen"
                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all duration-150 cursor-pointer {{ request()->routeIs('claims.*') ? 'text-slate-900 font-bold bg-slate-100/70' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-file-pen text-sm w-4 text-center"></i>
                        <span>Claims</span>
                    </div>
                    <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200"
                        :class="isClaimsOpen ? 'rotate-180 text-slate-900' : 'text-slate-400'"></i>
                </button>

                <div class="grid transition-[grid-template-rows,opacity] duration-200 ease-out"
                    :class="isClaimsOpen ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'">
                    <div class="overflow-hidden">
                        <div class="pl-7 pr-2 pt-1 pb-1.5 space-y-1">
                            <a href="{{ route('claims.create') }}?type=Receipt"
                                class="flex items-center gap-2 px-3 py-2 rounded-lg transition-colors {{ request()->fullUrlIs(route('claims.create') . '?type=Receipt') ? 'text-emerald-700 font-bold bg-emerald-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                                <i class="fa-solid fa-file-invoice text-[11px] text-emerald-600"></i>
                                <span>Based on Receipt (OCR)</span>
                            </a>
                            <a href="{{ route('claims.create') }}?type=Mileage"
                                class="flex items-center gap-2 px-3 py-2 rounded-lg transition-colors {{ request()->fullUrlIs(route('claims.create') . '?type=Mileage') ? 'text-blue-700 font-bold bg-blue-50' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                                <i class="fa-solid fa-motorcycle text-[11px] text-blue-600"></i>
                                <span>Mileage Allowance</span>
                            </a>
                            <a href="{{ route('claims.history') }}"
                                class="flex items-center gap-2 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('claims.history') ? 'text-slate-900 font-bold bg-slate-100' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                                <i class="fa-solid fa-clipboard-list text-[11px] text-slate-700"></i>
                                <span>My Claims</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <a href="{{ route('advances.index') }}"
                class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('advances.*') ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <i class="fa-solid fa-hand-holding-dollar text-sm w-4 text-center"></i>
                <span>Cash Advances</span>
            </a>

            <a href="{{ route('vehicles.index') }}"
                class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('vehicles.*') ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <i class="fa-solid fa-car text-sm w-4 text-center"></i>
                <span>My Vehicles</span>
            </a>

            <a href="{{ route('reimbursement.index') }}"
                class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('reimbursement.*') ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <i class="fa-solid fa-receipt text-sm w-4 text-center"></i>
                <span>Reimbursement Status</span>
            </a>

            <a href="{{ route('policy.index') }}"
                class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-150 {{ request()->routeIs('policy.*') ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                <i class="fa-solid fa-file-shield text-sm w-4 text-center"></i>
                <span>Company Policy</span>
            </a>
        </nav>
    </div>

    <!-- Bottom Sticky Actions -->
    <div class="p-4 border-t border-slate-100 space-y-1 text-xs font-semibold shrink-0">
        <a href="{{ route('profile.index') }}"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-colors {{ request()->routeIs('profile.*') ? 'bg-emerald-50 text-emerald-800 font-bold border border-emerald-200' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
            <i class="fa-solid fa-user-gear text-sm w-4 text-center"></i>
            <span>My Profile</span>
        </a>

        <a href="{{ route('logout') }}"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-rose-600 hover:bg-rose-50 transition-colors">
            <i class="fa-solid fa-right-from-bracket text-sm w-4 text-center"></i>
            <span>Sign Out</span>
        </a>
    </div>
</aside>
