<!DOCTYPE html>
<html lang="en"
    x-data="{ isMobileSidebarOpen: false, isAuditingOpen: false, isAdminOpen: true, activeSubTab: 'audit_logs' }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - System Audit Logs</title>
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
                                class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white text-left flex items-center gap-2"><i
                                    class="fa-solid fa-circle-check text-[11px]"></i> Accepted Review</a>
                        </div>
                    </div>

                    <div>
                        <button type="button" @click.prevent="isAdminOpenMobile = !isAdminOpenMobile"
                            :class="isAdminOpenMobile ? 'text-white font-semibold' : 'text-slate-400 hover:text-white'"
                            class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm transition-all cursor-pointer">
                            <span class="flex items-center gap-3"><i class="fa-solid fa-sliders-file text-base"
                                    :class="isAdminOpenMobile || activeSubTab === 'audit_logs' ? 'text-emerald-400' : 'text-slate-400'"></i>
                                <span>Administration</span></span>
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
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white"><i
                            class="fa-solid fa-chart-line text-base"></i> Reports & BI Analytics</a>
                    <a href="{{ route('manager.profile') }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white"><i
                            class="fa-solid fa-user-shield text-base"></i> My Profile</a>
                    <a href="{{ route('logout') }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-rose-950/60 hover:text-rose-400"><i
                            class="fa-solid fa-door-open text-base"></i> Sign Out</a>
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
                        class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white transition-all cursor-pointer">
                        <span class="flex items-center gap-3 pointer-events-none"><i
                                class="fa-solid fa-shield-check text-base"></i> Claims Verification</span>
                        <i class="fa-solid text-[10px] transition-transform duration-200 pointer-events-none"
                            :class="isAuditingOpen ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
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
                        <span class="flex items-center gap-3 pointer-events-none"><i
                                class="fa-solid fa-sliders-file text-base"
                                :class="isAdminOpen || activeSubTab === 'audit_logs' ? 'text-emerald-400' : 'text-slate-400'"></i>
                            Administration</span>
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

        <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full overflow-hidden">
            <div class="space-y-6">
                <div class="border-b border-slate-200 pb-5">
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">System Audit Trails</h1>
                    <p class="text-xs md:text-sm text-slate-500">Immutable forensic security trace streams safeguarding
                        corporate accounting structures.</p>
                </div>

                <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 gap-2">
                        <h3 class="text-xs md:text-sm font-bold text-slate-800 truncate"><i
                                class="fa-solid fa-shield-check text-blue-500 mr-1"></i> Security Event Stream</h3>
                        <span
                            class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-100 font-bold text-[10px] rounded-lg flex items-center gap-1 shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span> Logging Engine
                            Active
                        </span>
                    </div>

                    <div class="overflow-x-auto -mx-4 px-4 md:mx-0 md:px-0">
                        <table class="w-full text-left border-collapse text-xs min-w-[750px] sm:min-w-full">
                            <thead>
                                <tr
                                    class="border-b border-slate-100 text-slate-400 font-bold tracking-wide uppercase bg-slate-50/50 rounded-xl">
                                    <th class="py-3 px-4">Timestamp Log</th>
                                    <th class="py-3 px-4">Operator Node</th>
                                    <th class="py-3 px-4">Action Segment Event</th>
                                    <th class="py-3 px-4 font-mono">Payload Metadata Scope</th>
                                    <th class="py-3 px-4 text-center">IP Address</th>
                                </tr>
                                </tbody>
                            <tbody class="divide-y divide-slate-50 text-slate-700 font-medium font-mono">
                                <tr class="hover:bg-slate-50/60 transition-all text-[11px]">
                                    <td class="py-3.5 px-4 text-slate-500 font-sans font-semibold whitespace-nowrap">
                                        2026-06-12 14:05</td>
                                    <td class="py-3.5 px-4 font-sans font-bold text-slate-900 whitespace-nowrap">Hazman
                                        (Finance)</td>
                                    <td class="py-3.5 px-4 text-emerald-600 font-bold whitespace-nowrap"><i
                                            class="fa-solid fa-circle-check text-[9px] mr-1"></i> CLAIM_PRE_APPROVE</td>
                                    <td class="py-3.5 px-4 text-slate-500 truncate max-w-[200px]"
                                        title='{"claim_id": 10, "amount": 180.00}'>{"claim_id": 10, "amount": 180.00}
                                    </td>
                                    <td class="py-3.5 px-4 text-center text-slate-400 font-sans whitespace-nowrap">
                                        127.0.0.1</td>
                                </tr>
                                <tr class="hover:bg-slate-50/60 transition-all text-[11px]">
                                    <td class="py-3.5 px-4 text-slate-500 font-sans font-semibold whitespace-nowrap">
                                        2026-06-12 11:32</td>
                                    <td class="py-3.5 px-4 font-sans font-bold text-slate-900 whitespace-nowrap">
                                        Muhammad Musa</td>
                                    <td class="py-3.5 px-4 text-blue-600 font-bold whitespace-nowrap"><i
                                            class="fa-solid fa-cloud-arrow-up text-[9px] mr-1"></i> CLAIM_SUBMIT</td>
                                    <td class="py-3.5 px-4 text-slate-500 truncate max-w-[200px]"
                                        title='{"claim_type": "Mileage", "km": 247.0}'>{"claim_type": "Mileage", "km":
                                        247.0}</td>
                                    <td class="py-3.5 px-4 text-center text-slate-400 font-sans whitespace-nowrap">
                                        192.168.0.226</td>
                                </tr>
                                <tr class="hover:bg-slate-50/60 transition-all text-[11px]">
                                    <td class="py-3.5 px-4 text-slate-500 font-sans font-semibold whitespace-nowrap">
                                        2026-06-11 09:15</td>
                                    <td class="py-3.5 px-4 font-sans font-bold text-slate-900 whitespace-nowrap">Aero
                                        Art Manager</td>
                                    <td class="py-3.5 px-4 text-amber-600 font-bold whitespace-nowrap"><i
                                            class="fa-solid fa-sliders text-[9px] mr-1"></i> CONFIG_UPDATE</td>
                                    <td class="py-3.5 px-4 text-slate-500 truncate max-w-[200px]"
                                        title='{"target": "mileage_car_rate", "val": 0.60}'>{"target":
                                        "mileage_car_rate", "val": 0.60}</td>
                                    <td class="py-3.5 px-4 text-center text-slate-400 font-sans whitespace-nowrap">
                                        127.0.0.1</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>