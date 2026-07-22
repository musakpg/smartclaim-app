<!DOCTYPE html>
<html lang="en" x-data="{ isMobileSidebarOpen: false, isAuditingOpen: false }">
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
                                :class="isAuditingOpenMobile ? 'text-white font-semibold' : 'text-slate-400'"
                                class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm transition-all cursor-pointer">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-magnifying-glass-chart text-base" :class="isAuditingOpenMobile ? 'text-emerald-400' : 'text-slate-400'"></i> 
                                <span>Claims Auditing</span>
                            </span>
                            <i class="fa-solid text-[10px] transition-transform duration-200" :class="isAuditingOpenMobile ? 'fa-chevron-down rotate-180' : 'fa-chevron-right'"></i>
                        </button>
                        <div x-show="isAuditingOpenMobile" class="pl-6 mt-1 space-y-1 py-1 bg-slate-900/40 rounded-xl border border-slate-800">
                            <a href="{{ route('finance.auditing') }}?status=Pending" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white flex items-center justify-between gap-2">
                                <span class="flex items-center gap-2"><i class="fa-solid fa-hourglass-half text-[11px]"></i> Pending Verification</span>
                            </a>
                            <a href="{{ route('finance.auditing') }}?status=Approved" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white flex items-center gap-2"><i class="fa-solid fa-circle-check text-[11px]"></i> Approved Claims</a>
                            <a href="{{ route('finance.auditing') }}?status=Rejected" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white flex items-center gap-2"><i class="fa-solid fa-circle-xmark text-[11px]"></i> Rejected Claims</a>
                            <a href="{{ route('finance.auditing') }}?status=Reimbursed" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white flex items-center gap-2"><i class="fa-solid fa-money-bill-wave text-[11px]"></i> Reimbursed Claims</a>
                        </div>
                    </div>
                    <a href="{{ route('finance.reports') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold bg-slate-800 text-white">
                        <i class="fa-solid fa-chart-line text-emerald-400"></i> Reports & Analytics
                    </a>
                    <a href="{{ route('finance.profile') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white">
                        <i class="fa-solid fa-user-shield"></i> My Profile
                    </a>
                    <a href="{{ route('logout') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-rose-500/20 hover:text-rose-400">
                        <i class="fa-solid fa-door-open"></i> Sign Out
                    </a>
                </nav>
            </div>
        </div>

        <aside class="hidden lg:flex fixed inset-y-0 left-0 z-50 w-64 bg-[#0f172a] flex-col h-screen sticky top-0 text-slate-200"
               x-data="{ isAuditingOpen: false }"> 
            <div class="px-6 py-5 border-b border-slate-800 flex items-center gap-2.5">
                <i class="fa-solid fa-shield-halved text-emerald-400 text-2xl"></i>
                <div>
                    <span class="font-black text-base tracking-tight text-white block leading-tight">SmartClaim</span>
                    <span class="text-[10px] font-bold text-blue-400 uppercase tracking-wider">Aero Art Finance Portal</span>
                </div>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
                <a href="{{ route('finance.dashboard') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white transition-all">
                    <i class="fa-solid fa-house text-base"></i> Finance Dashboard
                </a>
                <div>
                    <button type="button" @click.prevent="isAuditingOpen = !isAuditingOpen" 
                            :class="isAuditingOpen ? 'bg-slate-800 text-white font-semibold' : 'text-slate-400 hover:bg-slate-900 hover:text-white'"
                            class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm transition-all cursor-pointer">
                        <span class="flex items-center gap-3 pointer-events-none">
                            <i class="fa-solid fa-magnifying-glass-chart text-base transition-colors duration-200" :class="isAuditingOpen ? 'text-emerald-400' : 'text-slate-400'"></i> 
                            <span>Claims Auditing</span>
                        </span>
                        <i class="fa-solid text-[10px] transition-transform duration-200 pointer-events-none" :class="isAuditingOpen ? 'fa-chevron-down rotate-180 text-white' : 'fa-chevron-right text-slate-400'"></i>
                    </button>
                    <div x-show="isAuditingOpen" x-cloak x-transition class="pl-6 mt-1 space-y-1 py-1 bg-slate-900/40 rounded-xl border border-slate-800">
                        <a href="{{ route('finance.auditing') }}?status=Pending" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white transition-all flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2"><i class="fa-solid fa-hourglass-half text-[11px]"></i> Pending Verification</span>
                            @if(($pendingCount ?? 0) > 0) <span class="px-2 py-0.5 bg-amber-500 text-slate-950 font-black rounded-md text-[9px] animate-pulse">{{ $pendingCount }}</span> @endif
                        </a>
                        <a href="{{ route('finance.auditing') }}?status=Approved" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white transition-all flex items-center gap-2"><i class="fa-solid fa-circle-check text-[11px]"></i> Approved Claims</a>
                        <a href="{{ route('finance.auditing') }}?status=Rejected" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white transition-all flex items-center gap-2"><i class="fa-solid fa-circle-xmark text-[11px]"></i> Rejected Claims</a>
                        <a href="{{ route('finance.auditing') }}?status=Reimbursed" class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white transition-all flex items-center gap-2"><i class="fa-solid fa-money-bill-wave text-[11px]"></i> Reimbursed Claims</a>
                    </div>
                </div>

                <a href="{{ route('finance.reports') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold bg-slate-800 text-white transition-all">
                    <i class="fa-solid fa-chart-line text-emerald-400 text-base"></i> Reports & Analytics
                </a>
                <a href="{{ route('finance.profile') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white transition-all"><i class="fa-solid fa-user-shield text-base"></i> My Profile</a>
                <a href="{{ route('logout') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-rose-950/60 hover:text-rose-400 transition-all"><i class="fa-solid fa-door-open text-base"></i> Sign Out</a>
            </nav>
        </aside>

        <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full overflow-hidden">
            <div class="space-y-6">
                
                <div class="border-b border-slate-200 pb-5">
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Reports & Analytics</h1>
                    <p class="text-xs md:text-sm text-slate-500">Macro visualization of company claim data and trend analysis of official organizational approved expenses.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-xs">
                        <h3 class="text-xs md:text-sm font-bold text-slate-800 tracking-tight mb-4"><i class="fa-solid fa-money-bill-trend-up text-blue-500 mr-1"></i> Org-Wide Spending Curve (2026)</h3>
                        <div class="relative w-full h-[220px] md:h-[280px]">
                            <canvas id="adminGlobalSpendingChart"></canvas>
                        </div>
                    </div>

                    <div class="lg:col-span-1 bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-xs">
                        <h3 class="text-xs md:text-sm font-bold text-slate-800 tracking-tight mb-4"><i class="fa-solid fa-store text-orange-500 mr-1"></i> Top Merchant Traffic</h3>
                        <div class="relative w-full h-[220px] md:h-[280px]">
                            <canvas id="adminMerchantTrafficChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-xs space-y-3">
                    <h3 class="text-xs md:text-sm font-bold text-slate-800 tracking-tight"><i class="fa-solid fa-lightbulb text-amber-500 mr-1"></i> Automated Executive Insights</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Sistem mengesan aliran perbelanjaan paling tinggi tertumpu kepada logistik perjalanan tapak bagi klien <strong>Aero Art Sdn Bhd</strong>. Disarankan pihak pengurusan mengekalkan siling rate semasa (RM0.60/KM) bagi memelihara imbangan margin pampasan perjalanan kakitangan syarikat.</p>
                </div>

            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            
            const realLineData = {!! $chartLineData ?? '[]' !!};
            const realMerchantLabels = {!! $merchantLabels ?? '[]' !!};
            const realMerchantCounts = {!! $merchantCounts ?? '[]' !!};

            // 1. GLOBAL LINE CHART INITIALIZATION
            const ctxGlobalLine = document.getElementById('adminGlobalSpendingChart').getContext('2d');
            new Chart(ctxGlobalLine, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Total Org Expenses (RM)',
                        data: realLineData, 
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.03)',
                        borderWidth: 2.5,
                        tension: 0.35,
                        pointBackgroundColor: '#10b981',
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
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

            // 2. MERCHANT TRAFFIC BAR CHART INITIALIZATION
            const ctxMerchantBar = document.getElementById('adminMerchantTrafficChart').getContext('2d');
            
            const finalLabels = realMerchantLabels.length > 0 ? realMerchantLabels : ['No Data'];
            const finalCounts = realMerchantCounts.length > 0 ? realMerchantCounts : [0];

            new Chart(ctxMerchantBar, {
                type: 'bar',
                data: {
                    labels: finalLabels, 
                    datasets: [{
                        label: 'Claims Count',
                        data: finalCounts, 
                        backgroundColor: '#f97316',
                        borderRadius: 6,
                        maxBarThickness: 24
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: '#f1f5f9' }, 
                            ticks: { 
                                font: { size: 9 },
                                stepSize: 1
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