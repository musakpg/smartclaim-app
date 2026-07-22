<!DOCTYPE html>
<html lang="en" x-data="{ isMobileSidebarOpen: false, isAuditingOpen: false, isAdminOpen: false }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Manager Analytics Command</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" :class="isMobileSidebarOpen ? 'overflow-hidden' : ''">

    <div class="flex min-h-screen flex-col lg:flex-row">
        
        <header class="lg:hidden bg-[#0f172a] px-4 py-4 flex items-center justify-between sticky top-0 z-40 shadow-sm text-slate-200">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-crown text-amber-400 text-xl"></i>
                <div>
                    <span class="font-black text-sm tracking-tight text-white block leading-tight">SmartClaim</span>
                    <span class="text-[9px] font-bold text-emerald-400 uppercase tracking-wider">Aero Art Manager</span>
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
                    <i class="fa-solid fa-crown text-amber-400 text-xl"></i>
                    <div>
                        <span class="font-black text-sm tracking-tight text-white block leading-tight">SmartClaim</span>
                        <span class="text-[9px] font-bold text-emerald-400 uppercase tracking-wider">Aero Art Manager Portal</span>
                    </div>
                </div>
                
                <nav class="mt-4 flex-1 px-4 space-y-1 overflow-y-auto" x-data="{ isAuditingOpenMobile: false, isAdminOpenMobile: false }">
                    <a href="{{ route('manager.dashboard') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold bg-slate-800 text-white">
                        <i class="fa-solid fa-chart-pie text-emerald-400"></i> Dashboard
                    </a>
                    
                    <div>
                        <button type="button" @click.prevent="isAuditingOpenMobile = !isAuditingOpenMobile" 
                                :class="isAuditingOpenMobile ? 'text-white' : 'text-slate-400'"
                                class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-medium transition-all cursor-pointer">
                            <span class="flex items-center gap-3"><i class="fa-solid fa-shield-check text-base" :class="isAuditingOpenMobile ? 'text-emerald-400' : 'text-slate-400'"></i> Claims Verification</span>
                            <i class="fa-solid text-[10px] transition-transform duration-200" :class="isAuditingOpenMobile ? 'fa-chevron-down rotate-180' : 'fa-chevron-right'"></i>
                        </button>
                        <div x-show="isAuditingOpenMobile" x-cloak class="pl-6 mt-1 space-y-1 py-1 bg-slate-900/40 rounded-xl border border-slate-800">
                            <a href="{{ route('manager.verification') }}?status=Pending" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white flex items-center justify-between gap-1">
                                <span class="flex items-center gap-2"><i class="fa-solid fa-hourglass-half text-[11px]"></i> Pending Review</span>
                                @if(($preApprovedCount ?? 0) > 0) <span class="px-1.5 py-0.5 bg-amber-500 text-slate-950 font-black rounded-sm text-[8px] font-mono">{{ $preApprovedCount }}</span> @endif
                            </a>
                            <a href="{{ route('manager.verification') }}?status=Approved" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white flex items-center gap-2"><i class="fa-solid fa-circle-check text-[11px]"></i> Accepted Review</a>
                        </div>
                    </div>

                    <div>
                        <button type="button" @click.prevent="isAdminOpenMobile = !isAdminOpenMobile" 
                                :class="isAdminOpenMobile ? 'text-white font-semibold' : 'text-slate-400 hover:text-white'"
                                class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm transition-all cursor-pointer">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-sliders-file text-base" :class="isAdminOpenMobile ? 'text-emerald-400' : 'text-slate-400'"></i> 
                                <span>Administration</span>
                            </span>
                            <i class="fa-solid text-[10px] transition-transform duration-200" :class="isAdminOpenMobile ? 'fa-chevron-down rotate-180' : 'fa-chevron-right'"></i>
                        </button>
                        
                        <div x-show="isAdminOpenMobile" x-cloak class="pl-6 mt-1 space-y-1 py-1 bg-slate-900/40 rounded-xl border border-slate-800 flex flex-col">
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

                    <a href="{{ route('manager.reports') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white">
                        <i class="fa-solid fa-chart-line"></i> Reports & BI Analytics
                    </a>
                    <a href="{{ route('manager.profile') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white">
                        <i class="fa-solid fa-user-shield"></i> My Profile
                    </a>
                    <a href="{{ route('logout') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-rose-950/60 hover:text-rose-400">
                        <i class="fa-solid fa-door-open"></i> Sign Out
                    </a>
                </nav>
            </div>
        </div>

        <aside class="hidden lg:flex fixed inset-y-0 left-0 z-50 w-64 bg-[#0f172a] flex-col h-screen sticky top-0 text-slate-200"
               x-data="{ isAuditingOpen: false, isAdminOpen: false }"> 
            
            <div class="px-6 py-5 border-b border-slate-800 flex items-center gap-2.5">
                <i class="fa-solid fa-crown text-amber-400 text-2xl"></i>
                <div>
                    <span class="font-black text-base tracking-tight text-white block leading-tight">SmartClaim</span>
                    <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider">Aero Art Manager Portal</span>
                </div>
            </div>
            
            <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
                <a href="{{ route('manager.dashboard') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold bg-slate-800 text-white transition-all">
                    <i class="fa-solid fa-chart-pie text-emerald-400 text-base"></i> Dashboard
                </a>
                
                <div>
                    <button type="button" @click.prevent="isAuditingOpen = !isAuditingOpen"
                            :class="isAuditingOpen ? 'bg-slate-900 text-white font-semibold' : 'text-slate-400 hover:bg-slate-900 hover:text-white'"
                            class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm transition-all cursor-pointer">
                        <span class="flex items-center gap-3 pointer-events-none">
                            <i class="fa-solid fa-shield-check text-base" :class="isAuditingOpen ? 'text-emerald-400' : 'text-slate-400'"></i> Claims Verification
                        </span>
                        <i class="fa-solid text-[10px] transition-transform duration-200 pointer-events-none" :class="isAuditingOpen ? 'fa-chevron-down rotate-180 text-white' : 'fa-chevron-right text-slate-400'"></i>
                    </button>
                    <div x-show="isAuditingOpen" x-cloak x-transition class="pl-6 mt-1 space-y-1 py-1 bg-slate-900/40 rounded-xl border border-slate-800">
                        <a href="{{ route('manager.verification') }}?status=Pre-Approved" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white text-left flex items-center justify-between gap-1 transition-all">
                            <span class="flex items-center gap-2"><i class="fa-solid fa-hourglass-half text-[11px]"></i> Pending Review</span>
                            @if(($preApprovedCount ?? 0) > 0) <span class="px-1.5 py-0.5 bg-amber-500 text-slate-950 font-black rounded-sm text-[8px] font-mono animate-pulse">{{ $preApprovedCount }}</span> @endif
                        </a>
                        <a href="{{ route('manager.verification') }}?status=Approved" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white text-left flex items-center gap-2"><i class="fa-solid fa-circle-check text-[11px]"></i> Accepted Review</a>
                    </div>
                </div>

                <div>
                    <button type="button" @click.prevent="isAdminOpen = !isAdminOpen"
                            :class="isAdminOpen ? 'bg-slate-900 text-white font-semibold' : 'text-slate-400 hover:bg-slate-900 hover:text-white'"
                            class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm transition-all cursor-pointer">
                        <span class="flex items-center gap-3 pointer-events-none">
                            <i class="fa-solid fa-sliders-file text-base" :class="isAdminOpen ? 'text-emerald-400' : 'text-slate-400'"></i> Administration
                        </span>
                        <i class="fa-solid text-[10px] transition-transform duration-200 pointer-events-none" :class="isAdminOpen ? 'fa-chevron-down rotate-180 text-white' : 'fa-chevron-right text-slate-400'"></i>
                    </button>
                    
                    <div x-show="isAdminOpen" x-cloak x-transition class="pl-6 mt-1 space-y-1 py-1 bg-slate-900/40 rounded-xl border border-slate-800 flex flex-col">
                        <a href="{{ route('manager.mileage_rates') }}" class="w-full text-left px-3 py-2 rounded-lg text-xs transition-all flex items-center gap-2 {{ request()->routeIs('manager.mileage_rates') ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white' }}"><i class="fa-solid fa-car-tunnel text-[11px]"></i> Mileage Rates</a>
                        <a href="{{ route('manager.expense_categories') }}" class="w-full text-left px-3 py-2 rounded-lg text-xs transition-all flex items-center gap-2 {{ request()->routeIs('manager.expense_categories') ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white' }}"><i class="fa-solid fa-folder-tree text-[11px]"></i> Expense Categories</a>
                        <a href="{{ route('manager.user_management') }}" class="w-full text-left px-3 py-2 rounded-lg text-xs transition-all flex items-center gap-2 {{ request()->routeIs('manager.user_management') ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white' }}"><i class="fa-solid fa-users-gear text-[11px]"></i> User Management</a>
                        <a href="{{ route('manager.vehicles') }}" class="w-full text-left px-3 py-2 rounded-lg text-xs transition-all flex items-center gap-2 {{ request()->routeIs('manager.vehicles') ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white' }}"><i class="fa-solid fa-car text-[11px]"></i> Company Fleet CRUD</a>
                        <a href="{{ route('manager.audit_logs') }}" class="w-full text-left px-3 py-2 rounded-lg text-xs transition-all flex items-center gap-2 {{ request()->routeIs('manager.audit_logs') ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white' }}"><i class="fa-solid fa-scroll text-[11px]"></i> Audit Logs</a>
                    </div>
                </div>

                <a href="{{ route('manager.reports') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white transition-all"><i class="fa-solid fa-chart-line text-base"></i> Reports & BI Analytics</a>
                <a href="{{ route('manager.profile') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white transition-all"><i class="fa-solid fa-user-shield text-base"></i> My Profile</a>
                <a href="{{ route('logout') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-rose-950/60 hover:text-rose-400 transition-all"><i class="fa-solid fa-door-open text-base"></i> Sign Out</a>
            </nav>
        </aside>

        <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full overflow-hidden">
            <div class="space-y-6">
                
                <div class="border-b border-slate-200 pb-5">
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Manager BI Dashboard</h1>
                    <p class="text-xs md:text-sm text-slate-500">Real-time organizational expenditure metrics, operational data charts, and institutional sign-off summaries.</p>
                </div>

                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between text-xs text-slate-500 pb-2 font-medium border-b border-slate-100">
                    <div>Welcome back, <span class="font-bold text-slate-800">Executive Manager</span></div>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
                    
                    <div class="bg-white p-4 md:p-5 rounded-2xl md:rounded-3xl border border-slate-200/60 shadow-2xs flex items-center justify-between">
                        <div class="space-y-0.5 truncate">
                            <span class="text-[9px] md:text-[11px] font-bold uppercase tracking-wider text-slate-400 block truncate">Pending Sign-off</span>
                            <h3 class="text-base md:text-xl font-bold text-amber-600 tracking-tight truncate">{{ $preApprovedCount ?? 0 }} Claims</h3>
                        </div>
                        <div class="w-9 h-9 md:w-11 md:h-11 bg-amber-50 rounded-xl flex items-center justify-center border border-amber-100 text-amber-600 shrink-0 ml-2"><i class="fa-solid fa-hourglass-half text-xs md:text-base"></i></div>
                    </div>

                    <div class="bg-white p-4 md:p-5 rounded-2xl md:rounded-3xl border border-slate-200/60 shadow-2xs flex items-center justify-between">
                        <div class="space-y-0.5 truncate">
                            <span class="text-[9px] md:text-[11px] font-bold uppercase tracking-wider text-slate-400 block truncate">Accepted Review</span>
                            <h3 class="text-base md:text-xl font-bold text-emerald-600 tracking-tight truncate">{{ $approvedCount ?? 0 }} Claims</h3>
                        </div>
                        <div class="w-9 h-9 md:w-11 md:h-11 bg-emerald-50 rounded-xl flex items-center justify-center border border-emerald-100 text-emerald-600 shrink-0 ml-2"><i class="fa-solid fa-circle-check text-xs md:text-base"></i></div>
                    </div>

                    <div class="bg-white p-4 md:p-5 rounded-2xl md:rounded-3xl border border-slate-200/60 shadow-2xs flex items-center justify-between">
                        <div class="space-y-0.5 truncate">
                            <span class="text-[9px] md:text-[11px] font-bold uppercase tracking-wider text-slate-400 block truncate">Rejected Review</span>
                            <h3 class="text-base md:text-xl font-bold text-rose-600 tracking-tight truncate">{{ $rejectedCount ?? 0 }} Claims</h3>
                        </div>
                        <div class="w-9 h-9 md:w-11 md:h-11 bg-rose-50 rounded-xl flex items-center justify-center border border-rose-100 text-rose-600 shrink-0 ml-2"><i class="fa-solid fa-circle-xmark text-xs md:text-base"></i></div>
                    </div>

                    <div class="bg-white p-4 md:p-5 rounded-2xl md:rounded-3xl border border-slate-200/60 shadow-2xs flex items-center justify-between">
                        <div class="space-y-0.5 truncate">
                            <span class="text-[9px] md:text-[11px] font-bold uppercase tracking-wider text-slate-400 block truncate">Total System Logs</span>
                            <h3 class="text-base md:text-xl font-bold text-indigo-600 tracking-tight truncate">{{ $totalReviewCount ?? 0 }} Records</h3>
                        </div>
                        <div class="w-9 h-9 md:w-11 md:h-11 bg-indigo-50 rounded-xl flex items-center justify-center border border-indigo-100 text-indigo-600 shrink-0 ml-2"><i class="fa-solid fa-database text-xs md:text-base"></i></div>
                    </div>

                </div>

                <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-2xs">
                    <h3 class="text-xs md:text-sm font-bold text-slate-800 tracking-tight mb-4">Monthly Approved Expense Trends (2026)
</h3>
                    <div class="relative w-full h-[240px] md:h-[300px]">
                        <canvas id="managerBIChart"></canvas>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('managerBIChart').getContext('2d');
            const realDatabaseData = @json($managerLineData ?? []);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Total Approved Expenditures (RM)',
                        data: realDatabaseData,
                        borderColor: '#059669', 
                        backgroundColor: 'rgba(5, 150, 105, 0.02)',
                        borderWidth: 2.5,
                        tension: 0.3, 
                        fill: true,
                        pointBackgroundColor: '#059669',
                        pointHoverRadius: 6
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let value = context.parsed.y || 0;
                                    return ' Approved: RM ' + value.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                font: { size: 9 },
                                callback: function(value) { return 'RM ' + value; }
                            }
                        },
                        x: { grid: { display: false }, ticks: { font: { size: 9 } } }
                    }
                }
            });
        });
    </script>
</body>
</html>