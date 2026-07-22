<!DOCTYPE html>
<html lang="en" x-data="{ isMobileSidebarOpen: false, isAuditingOpen: false, isAdminOpen: false }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Reports & Financial Analytics</title>
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
                    x-data="{ isAuditingOpenMobile: false, isAdminOpenMobile: false }">
                    <a href="{{ route('manager.dashboard') }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white">
                        <i class="fa-solid fa-chart-pie"></i> Dashboard
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
                                    :class="isAdminOpenMobile ? 'text-emerald-400' : 'text-slate-400'"></i>
                                <span>Administration</span>
                            </span>
                            <i class="fa-solid text-[10px] transition-transform duration-200"
                                :class="isAdminOpenMobile ? 'fa-chevron-down rotate-180' : 'fa-chevron-right'"></i>
                        </button>

                        <div x-show="isAdminOpenMobile" x-cloak
                            class="pl-6 mt-1 space-y-1 py-1 bg-slate-900/40 rounded-xl border border-slate-800 flex flex-col">
                            <a href="{{ route('manager.mileage_rates') }}"
                                :class="request()->routeIs('manager.mileage_rates') ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-2">
                                <i class="fa-solid fa-car-tunnel text-[11px]"></i> Mileage Rates
                            </a>
                            <a href="{{ route('manager.expense_categories') }}"
                                :class="request()->routeIs('manager.expense_categories') ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-2">
                                <i class="fa-solid fa-folder-tree text-[11px]"></i> Expense Categories
                            </a>
                            <a href="{{ route('manager.user_management') }}"
                                :class="request()->routeIs('manager.user_management') ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-bold flex items-center gap-2">
                                <i class="fa-solid fa-users-gear text-[11px]"></i> User Management
                            </a>
                            <a href="{{ route('manager.vehicles') }}"
                                :class="request()->routeIs('manager.vehicles') ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-2">
                                <i class="fa-solid fa-car text-[11px]"></i> Company Fleet CRUD
                            </a>
                            <a href="{{ route('manager.audit_logs') }}"
                                :class="request()->routeIs('manager.audit_logs') ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-2">
                                <i class="fa-solid fa-scroll text-[11px]"></i> Audit Logs
                            </a>
                        </div>
                    </div>

                    <a href="{{ route('manager.reports') }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold bg-slate-800 text-white">
                        <i class="fa-solid fa-chart-line text-emerald-400"></i> Reports & BI Analytics
                    </a>
                    <a href="{{ route('manager.profile') }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white">
                        <i class="fa-solid fa-user-shield"></i> My Profile
                    </a>
                    <a href="{{ route('logout') }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-rose-950/60 hover:text-rose-400">
                        <i class="fa-solid fa-door-open"></i> Sign Out
                    </a>
                </nav>
            </div>
        </div>

        <aside
            class="hidden lg:flex fixed inset-y-0 left-0 z-50 w-64 bg-[#0f172a] flex-col h-screen sticky top-0 text-slate-200"
            x-data="{ isAuditingOpen: false, isAdminOpen: false }">

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
                    <i class="fa-solid fa-chart-pie"></i> Dashboard
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
                                :class="isAdminOpen ? 'text-emerald-400' : 'text-slate-400'"></i> Administration
                        </span>
                        <i class="fa-solid text-[10px] transition-transform duration-200 pointer-events-none"
                            :class="isAdminOpen ? 'fa-chevron-down rotate-180 text-white' : 'fa-chevron-right text-slate-400'"></i>
                    </button>

                    <div x-show="isAdminOpen" x-cloak x-transition
                        class="pl-6 mt-1 space-y-1 py-1 bg-slate-900/40 rounded-xl border border-slate-800 flex flex-col">
                        <a href="{{ route('manager.mileage_rates') }}"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs transition-all flex items-center gap-2 {{ request()->routeIs('manager.mileage_rates') ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white' }}"><i
                                class="fa-solid fa-car-tunnel text-[11px]"></i> Mileage Rates</a>
                        <a href="{{ route('manager.expense_categories') }}"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs transition-all flex items-center gap-2 {{ request()->routeIs('manager.expense_categories') ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white' }}"><i
                                class="fa-solid fa-folder-tree text-[11px]"></i> Expense Categories</a>
                        <a href="{{ route('manager.user_management') }}"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs transition-all flex items-center gap-2 {{ request()->routeIs('manager.user_management') ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white' }}"><i
                                class="fa-solid fa-users-gear text-[11px]"></i> User Management</a>
                        <a href="{{ route('manager.vehicles') }}"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs transition-all flex items-center gap-2 {{ request()->routeIs('manager.vehicles') ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white' }}"><i
                                class="fa-solid fa-car text-[11px]"></i> Company Fleet CRUD</a>
                        <a href="{{ route('manager.audit_logs') }}"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs transition-all flex items-center gap-2 {{ request()->routeIs('manager.audit_logs') ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white' }}"><i
                                class="fa-solid fa-scroll text-[11px]"></i> Audit Logs</a>
                    </div>
                </div>

                <a href="{{ route('manager.reports') }}"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold bg-slate-800 text-white transition-all">
                    <i class="fa-solid fa-chart-line text-emerald-400 text-base"></i> Reports & BI Analytics
                </a>
                <a href="{{ route('manager.profile') }}"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white transition-all"><i
                        class="fa-solid fa-user-shield text-base"></i> My Profile</a>
                <a href="{{ route('logout') }}"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-rose-400 hover:bg-rose-950/60 transition-all"><i
                        class="fa-solid fa-door-open"></i> Sign Out</a>
            </nav>
        </aside>

        <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full overflow-hidden">
            <div class="space-y-6">

                <div class="border-b border-slate-200 pb-5">
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">BI Analytics Report</h1>
                    <p class="text-xs md:text-sm text-slate-500">Real-time organizational expenditure insights.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                    <div class="bg-white p-5 md:p-6 rounded-3xl border border-slate-100 shadow-2xs">
                        <p class="text-slate-400 text-[10px] font-black uppercase tracking-wider block">Total Claims Submitted</p>
                        <h2 class="text-2xl md:text-3xl font-black text-slate-900 mt-1 font-mono tracking-tight">
                            {{ $totalClaims }}
                        </h2>
                    </div>
                    <div class="bg-white p-5 md:p-6 rounded-3xl border border-slate-100 shadow-2xs">
                        <p class="text-slate-400 text-[10px] font-black uppercase tracking-wider block">Total Approved
                            Spend</p>
                        <h2 class="text-2xl md:text-3xl font-black text-emerald-600 mt-1 font-mono tracking-tight">RM
                            {{ number_format($totalAmount, 2) }}
                        </h2>
                    </div>
                    <div class="bg-white p-5 md:p-6 rounded-3xl border border-slate-100 shadow-2xs">
                        <p class="text-slate-400 text-[10px] font-black uppercase tracking-wider block">Average Claim
                        </p>
                        <h2 class="text-2xl md:text-3xl font-black text-blue-600 mt-1 font-mono tracking-tight">RM
                            {{ number_format($avgClaim, 2) }}
                        </h2>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-100 shadow-2xs">
                        <h3 class="text-xs md:text-sm font-bold text-slate-800 mb-4 md:mb-6">Spending by Merchant</h3>
                        <div class="relative w-full h-[220px] md:h-[250px]">
                            <canvas id="barChart"></canvas>
                        </div>
                    </div>
                    <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-100 shadow-2xs">
                        <h3 class="text-xs md:text-sm font-bold text-slate-800 mb-4 md:mb-6">Expense Categories</h3>
                        <div class="relative w-full h-[220px] md:h-[250px]">
                            <canvas id="pieChart"></canvas>
                        </div>
                    </div>
                </div>

                @php
                    $allRecentClaims = \App\Models\Claim::with('user')
                        ->orderBy('created_at', 'desc')
                        ->get();
                @endphp

                <div class="bg-white rounded-3xl border border-slate-100 shadow-2xs overflow-hidden" x-data="{ 
                        claimsData: {{ json_encode($allRecentClaims) }},
                        currentPage: 1,
                        perPage: 5,
                        get totalRecords() { return this.claimsData.length },
                        get totalPages() { return Math.ceil(this.totalRecords / this.perPage) },
                        get pagedItems() {
                            let start = (this.currentPage - 1) * this.perPage;
                            return this.claimsData.slice(start, start + this.perPage);
                        }
                     }">

                    <div class="px-6 py-4 border-b border-slate-100 font-bold text-xs md:text-sm text-slate-800">Recent
                        Audit Logs</div>

                    <div class="overflow-x-auto -mx-4 px-4 md:mx-0 md:px-0">
                        <table class="w-full text-left text-xs min-w-[600px] sm:min-w-full">
                            <thead class="bg-slate-50 text-slate-400 text-[10px] uppercase font-black tracking-wider">
                                <tr>
                                    <th class="px-6 py-3.5">Date</th>
                                    <th class="px-6 py-3.5">Merchant / Purpose</th>
                                    <th class="px-6 py-3.5">Category</th>
                                    <th class="px-6 py-3.5 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 text-slate-700 font-medium">
                                <template x-for="(item, index) in pagedItems" :key="index">
                                    <tr class="hover:bg-slate-50/50 transition-all">
                                        <td class="px-6 py-3.5 text-slate-500 whitespace-nowrap"
                                            x-text="new Date(item.transaction_date).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'})">
                                        </td>

                                        <td class="px-6 py-3.5 font-bold text-slate-950 truncate max-w-[180px]"
                                            :title="item.merchant_name"
                                            x-text="item.claim_type === 'Mileage' ? (item.title || 'Travel Allowance Claim') : item.merchant_name">
                                        </td>

                                        <td class="px-6 py-3.5 whitespace-nowrap">
                                            <span
                                                class="px-2.5 py-0.5 bg-slate-100 text-slate-600 rounded-md font-bold text-[10px]"
                                                x-text="item.claim_type === 'Mileage' ? 'Logistics' : item.predicted_category"></span>
                                        </td>

                                        <td class="px-6 py-3.5 font-black text-emerald-600 text-right whitespace-nowrap"
                                            x-text="'RM ' + parseFloat(item.amount).toFixed(2)">
                                        </td>
                                    </tr>
                                </template>

                                <template x-if="totalRecords === 0">
                                    <tr>
                                        <td colspan="4" class="py-8 text-center text-slate-400 font-semibold">
                                            <i class="fa-solid fa-folder-open block text-xl mb-1.5 text-slate-300"></i>
                                            No data rows loaded.
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div x-show="totalPages > 1"
                        class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-bold px-6 py-4">

                        <div class="text-slate-400 font-medium text-center sm:text-left">
                            Showing <span class="text-slate-700 font-bold"
                                x-text="((currentPage - 1) * perPage) + 1"></span> to
                            <span class="text-slate-700 font-bold"
                                x-text="Math.min(currentPage * perPage, totalRecords)"></span> of
                            <span class="text-slate-700 font-bold" x-text="totalRecords"></span> records
                        </div>

                        <div class="flex items-center gap-1.5 w-full sm:w-auto justify-center sm:justify-end">
                            <button type="button" @click="if(currentPage > 1) currentPage--"
                                :disabled="currentPage === 1"
                                :class="currentPage === 1 ? 'text-slate-300 cursor-not-allowed bg-slate-50 border-slate-100' : 'text-slate-700 hover:border-slate-400 bg-white border-slate-200 cursor-pointer'"
                                class="flex-1 sm:flex-initial text-center px-3 py-2 border rounded-xl transition-all flex items-center gap-1">
                                <i class="fa-solid fa-chevron-left text-blue-600"
                                    :class="currentPage === 1 ? 'opacity-30' : ''"></i> Previous
                            </button>

                            <button type="button" @click="if(currentPage < totalPages) currentPage++"
                                :disabled="currentPage === totalPages"
                                :class="currentPage === totalPages ? 'text-slate-300 cursor-not-allowed bg-slate-50 border-slate-100' : 'text-slate-700 hover:border-slate-400 bg-white border-slate-200 cursor-pointer'"
                                class="flex-1 sm:flex-initial text-center px-3 py-2 border rounded-xl transition-all flex items-center gap-1">
                                Next <i class="fa-solid fa-chevron-right text-blue-600"
                                    :class="currentPage === totalPages ? 'opacity-30' : ''"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {

            const merchantLabels = {!! json_encode($merchantData->pluck('merchant_name') ?? []) !!};
            const merchantSpend = {!! json_encode($merchantData->pluck('total_spend') ?? []) !!};
            const categoryLabels = {!! json_encode($categoryData->pluck('predicted_category') ?? []) !!};
            const categoryAmounts = {!! json_encode($categoryData->pluck('total_amount') ?? []) !!};

            new Chart(document.getElementById('barChart'), {
                type: 'bar',
                data: {
                    labels: merchantLabels.length > 0 ? merchantLabels : ['No Data'],
                    datasets: [{
                        label: 'Total Spend (RM)',
                        data: merchantSpend.length > 0 ? merchantSpend : [0],
                        backgroundColor: '#3b82f6',
                        borderRadius: 6,
                        maxBarThickness: 24
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 9 } } },
                        x: { grid: { display: false }, ticks: { font: { size: 9 } } }
                    }
                }
            });

            new Chart(document.getElementById('pieChart'), {
                type: 'doughnut',
                data: {
                    labels: categoryLabels.length > 0 ? categoryLabels : ['No Data'],
                    datasets: [{
                        data: categoryAmounts.length > 0 ? categoryAmounts : [0],
                        backgroundColor: ['#f43f5e', '#10b981', '#6366f1', '#f59e0b', '#8b5cf6']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10, weight: 'bold' } } }
                    }
                }
            });
        });
    </script>
</body>

</html>