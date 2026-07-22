<!DOCTYPE html>
<html lang="en" x-data="{ isMobileSidebarOpen: false, isAuditingOpen: false, isAdminOpen: false }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Finance Dashboard</title>
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
                    <a href="{{ route('finance.dashboard') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold bg-slate-800 text-white">
                        <i class="fa-solid fa-chart-pie text-emerald-400"></i> Finance Dashboard
                    </a>
                    <div>
                        <button type="button" @click.prevent="isAuditingOpenMobile = !isAuditingOpenMobile" class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white cursor-pointer">
                            <span class="flex items-center gap-3"><i class="fa-solid fa-magnifying-glass-chart"></i> Claims Auditing</span>
                            <i class="fa-solid text-[10px] transition-transform duration-200" :class="isAuditingOpenMobile ? 'fa-chevron-down rotate-180' : 'fa-chevron-right'"></i>
                        </button>
                        <div x-show="isAuditingOpenMobile" class="pl-6 mt-1 space-y-1 py-1 bg-slate-900/40 rounded-xl border border-slate-800">
                            <a href="{{ route('finance.auditing') }}?status=Pending" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white flex items-center justify-between gap-2">
                                <span class="flex items-center gap-2"><i class="fa-solid fa-hourglass-half text-[11px]"></i> Pending Verification</span>
                                @if(($pendingCount ?? 0) > 0) <span class="px-2 py-0.5 bg-amber-500 text-slate-950 font-black rounded-md text-[9px]">{{ $pendingCount }}</span> @endif
                            </a>
                            <a href="{{ route('finance.auditing') }}?status=Approved" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white flex items-center gap-2"><i class="fa-solid fa-circle-check text-[11px]"></i> Approved Claims</a>
                            <a href="{{ route('finance.auditing') }}?status=Rejected" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white flex items-center gap-2"><i class="fa-solid fa-circle-xmark text-[11px]"></i> Rejected Claims</a>
                            <a href="{{ route('finance.auditing') }}?status=Reimbursed" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white flex items-center gap-2"><i class="fa-solid fa-money-bill-wave text-[11px]"></i> Reimbursed Claims</a>
                        </div>
                    </div>
                    <a href="{{ route('finance.reports') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white">
                        <i class="fa-solid fa-chart-line"></i> Reports & Analytics
                    </a>
                    <a href="{{ route('finance.profile') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white">
                        <i class="fa-solid fa-user-shield"></i> My Profile
                    </a>
                    <a href="{{ route('logout') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-rose-950/60 hover:text-rose-400">
                        <i class="fa-solid fa-door-open"></i> Sign Out
                    </a>
                </nav>
            </div>
        </div>

        <aside class="hidden lg:flex fixed inset-y-0 left-0 z-50 w-64 bg-[#0f172a] flex-col h-screen sticky top-0 text-slate-200"> 
            <div class="px-6 py-5 border-b border-slate-800 flex items-center gap-2.5">
                <i class="fa-solid fa-shield-halved text-emerald-400 text-2xl"></i>
                <div>
                    <span class="font-black text-base tracking-tight text-white block leading-tight">SmartClaim</span>
                    <span class="text-[10px] font-bold text-blue-400 uppercase tracking-wider">Aero Art Finance Portal</span>
                </div>
            </div>
            
            <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
                <a href="{{ route('finance.dashboard') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold bg-slate-800 text-white transition-all">
                    <i class="fa-solid fa-chart-pie text-emerald-400 text-base"></i> Finance Dashboard
                </a>
                
                <div>
                    <button type="button" @click.prevent="isAuditingOpen = !isAuditingOpen" class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white transition-all cursor-pointer">
                        <span class="flex items-center gap-3 pointer-events-none"><i class="fa-solid fa-magnifying-glass-chart text-base"></i> Claims Auditing</span>
                        <i class="fa-solid text-[10px] transition-transform duration-200 pointer-events-none" :class="isAuditingOpen ? 'fa-chevron-down rotate-180' : 'fa-chevron-right'"></i>
                    </button>
                    <div x-show="isAuditingOpen" x-cloak x-transition class="pl-6 mt-1 space-y-1 py-1 bg-slate-900/40 rounded-xl border border-slate-800">
                        <a href="{{ route('finance.auditing') }}?status=Pending" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white transition-all flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><i class="fa-solid fa-hourglass-half text-[11px]"></i> Pending Verification</span>
                            @if(($pendingCount ?? 0) > 0) <span class="px-2 py-0.5 bg-amber-500 text-slate-950 font-black rounded-md text-[9px] animate-pulse">{{ $pendingCount }}</span> @endif
                        </a>
                        <a href="{{ route('finance.auditing') }}?status=Approved" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white transition-all flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-[11px]"></i> Approved Claims</span>
                        </a>
                        <a href="{{ route('finance.auditing') }}?status=Rejected" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white transition-all flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><i class="fa-solid fa-circle-xmark text-[11px]"></i> Rejected Claims</span>
                        </a>
                        <a href="{{ route('finance.auditing') }}?status=Reimbursed" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white transition-all flex items-center gap-2">
                            <i class="fa-solid fa-money-bill-wave text-[11px]"></i> Reimbursed Claims
                        </a>
                    </div>
                </div>

                <a href="{{ route('finance.reports') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white transition-all"><i class="fa-solid fa-chart-line text-base"></i> Reports & Analytics</a>
                <a href="{{ route('finance.profile') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white transition-all"><i class="fa-solid fa-user-shield text-base"></i> My Profile</a>
                <a href="{{ route('logout') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-rose-950/60 hover:text-rose-400 transition-all"><i class="fa-solid fa-door-open text-base"></i> Sign Out</a>
            </nav>
        </aside>

        <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full overflow-hidden">
            <div class="space-y-6">
                
                <div class="border-b border-slate-200 pb-5">
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Finance Management Executive Dashboard</h1>
                    <p class="text-xs md:text-sm text-slate-500">Executive summary of global financial claim operations for the Aero Art Sdn Bhd organization.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-5">
                    <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs flex items-center justify-between">
                        <div class="space-y-1 truncate">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block truncate">Pending Approvals</span>
                            <h3 class="text-xl md:text-2xl font-black text-amber-600 font-mono tracking-tight">{{ $pendingCount ?? 0 }} Claims</h3>
                        </div>
                        <div class="w-11 h-11 bg-amber-50 rounded-xl flex items-center justify-center border border-amber-100 shrink-0 ml-2"><i class="fa-solid fa-hourglass-half text-amber-600"></i></div>
                    </div>
                    <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs flex items-center justify-between">
                        <div class="space-y-1 truncate">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block truncate">Total Approved Funds</span>
                            <h3 class="text-xl md:text-2xl font-black text-emerald-600 font-mono tracking-tight">{{ $approvedCount ?? 0 }} Claims</h3>
                        </div>
                        <div class="w-11 h-11 bg-emerald-50 rounded-xl flex items-center justify-center border border-emerald-100 shrink-0 ml-2"><i class="fa-solid fa-circle-check text-emerald-600"></i></div>
                    </div>
                    <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs flex items-center justify-between">
                        <div class="space-y-1 truncate">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block truncate">Total Rejected Logs</span>
                            <h3 class="text-xl md:text-2xl font-black text-rose-600 font-mono tracking-tight">{{ $rejectedCount ?? 0 }} Logs</h3>
                        </div>
                        <div class="w-11 h-11 bg-rose-50 rounded-xl flex items-center justify-center border border-rose-100 shrink-0 ml-2"><i class="fa-solid fa-circle-xmark text-rose-600"></i></div>
                    </div>
                </div>

                <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-xs">
                    <h3 class="text-xs md:text-sm font-bold text-slate-800 mb-4"><i class="fa-solid fa-wave-square text-blue-500 mr-1"></i> Monthly Approved Expense Trends (2026)</h3>
                    <div class="relative w-full h-[220px] md:h-[260px]"><canvas id="dashboardQuickLineChart"></canvas></div>
                </div>

                <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 gap-2">
                        <div class="space-y-0.5">
                            <h3 class="text-xs md:text-sm font-bold text-slate-800"><i class="fa-solid fa-list-ol text-slate-400 mr-1"></i> Latest Submitted Claims Queue</h3>
                            <p class="text-[11px] text-slate-400 hidden sm:block">Queue records fetched natively across local data repositories.</p>
                        </div>
                        <a href="{{ route('finance.auditing') }}?status=Pending" class="text-[11px] md:text-xs font-bold text-blue-600 hover:underline shrink-0">Open Auditing Workspace →</a>
                    </div>
                    
                    <div class="overflow-x-auto -mx-4 px-4 md:mx-0 md:px-0">
                        <table class="w-full text-left border-collapse text-xs min-w-[600px] sm:min-w-full">
                            <thead>
                                <tr class="border-b border-slate-100 text-slate-400 font-bold tracking-wide uppercase bg-slate-50/50 rounded-xl">
                                    <th class="py-3 px-4">Employee</th>
                                    <th class="py-3 px-4">Claim ID</th>
                                    <th class="py-3 px-4">Particulars</th>
                                    <th class="py-3 px-4 text-right">Amount</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 text-slate-700 font-medium">
                                @forelse(($claims ?? [])->take(5) as $claim)
                                    <tr class="hover:bg-slate-50/60 transition-all">
                                        <td class="py-3.5 px-4 font-bold text-slate-900 whitespace-nowrap">{{ $claim->user->name ?? 'Staff' }}</td>
                                        <td class="py-3.5 px-4 font-mono text-slate-400 whitespace-nowrap">CLM-{{ $claim->claim_id }}</td>
                                        <td class="py-3.5 px-4 font-bold text-slate-950 truncate max-w-[160px]" title="{{ $claim->merchant_name }}">
                                            {{ $claim->claim_type === 'Mileage' ? ($claim->title ?? 'Travel Allowance Claim') : $claim->merchant_name }}
                                        </td>
                                        <td class="py-3.5 px-4 text-right font-black text-slate-900 whitespace-nowrap">RM {{ number_format($claim->amount, 2) }}</td>
                                        <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                            <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase tracking-wide
                                                {{ $claim->status === 'Approved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '' }}
                                                {{ $claim->status === 'Pre-Approved' ? 'bg-blue-50 text-blue-700 border border-blue-200' : '' }}
                                                {{ $claim->status === 'Pending' ? 'bg-amber-50 text-amber-700 border border-amber-200' : '' }}
                                                {{ $claim->status === 'Rejected' ? 'bg-rose-50 text-rose-700 border border-rose-200' : '' }}">
                                                {{ $claim->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-slate-400 font-semibold">
                                            <i class="fa-solid fa-folder-open block text-xl mb-1.5 text-slate-300"></i> No historical entries found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const realDashboardLineData = {!! json_encode($dashboardLineData ?? []) !!};

            const ctxDashboardLine = document.getElementById('dashboardQuickLineChart').getContext('2d');
            new Chart(ctxDashboardLine, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Approved Expenses (RM)',
                        data: realDashboardLineData, 
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.02)',
                        borderWidth: 2,
                        tension: 0.35,
                        fill: true
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
        });
    </script>
</body>
</html>