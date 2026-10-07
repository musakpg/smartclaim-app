<!DOCTYPE html>
<html lang="en" x-data="{ isMobileSidebarOpen: false }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Finance Reports & Analytics</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased":class="isMobileSidebarOpen ? 'overflow-hidden lg:overflow-auto' : ''">

    <div class="min-h-screen flex flex-col lg:flex-row">

        <!-- Finance Sidebar Partial -->
        @include('layouts.partials.finance-sidebar')

        <!-- Main Workspace Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">



            <!-- Main Page Content -->
            <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full pb-24 lg:pb-8 overflow-y-auto space-y-6">

                <!-- Header & Action Filter -->
                <div class="border-b border-slate-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div>
                        <div>
                        <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Finance Reports & Analytics</h1>
                        <p class="text-xs md:text-sm text-slate-500">Corporate cash outflow analytics, merchant expenditure velocity, and audit reconciliation for Aero Art Sdn Bhd.</p>
                    </div>
                    <div class="hidden lg:flex items-center gap-3">
                        <x-system-clock />
                    </div>
                </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('manager.export.claims_csv') }}?status=Approved"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center gap-2 transition cursor-pointer">
                            <i class="fa-solid fa-file-csv"></i> Export Claims Ledger (CSV)
                        </a>

                        <form method="GET" action="{{ route('finance.reports') }}" class="flex items-center gap-2">
                            <select name="year" onchange="this.form.submit()"
                                class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none cursor-pointer">
                                @foreach(range(date('Y'), date('Y') - 3) as $y)
                                    <option value="{{ $y }}" {{ ($year ?? date('Y')) == $y ? 'selected' : '' }}>FY {{ $y }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>

                <!-- KPI Metric Summary Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Settled / Disbursed</span>
                        <h3 class="text-2xl font-black font-mono text-emerald-600">
                            RM {{ number_format($totalApprovedFundsRM ?? 0, 2) }}
                        </h3>
                        <p class="text-[11px] text-slate-400">Cumulative for FY{{ $year ?? date('Y') }}</p>
                    </div>

                    <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pending Verification Exposure</span>
                        <h3 class="text-2xl font-black font-mono text-amber-500">
                            RM {{ number_format($totalPendingFundsRM ?? 0, 2) }}
                        </h3>
                        <p class="text-[11px] text-slate-400">Vouchers in audit pipeline</p>
                    </div>

                    <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Processed Vouchers Volume</span>
                        <h3 class="text-2xl font-black font-mono text-blue-600">
                            {{ $totalProcessedCount ?? 0 }} <span class="text-xs font-sans font-bold text-slate-500">Transactions</span>
                        </h3>
                        <p class="text-[11px] text-slate-400">Total audited entries</p>
                    </div>
                </div>

                <!-- Visual Charts Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Line Chart: Monthly Cash Outflow -->
                    <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-4 lg:col-span-2">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-chart-area text-blue-600"></i> Monthly Outflow Velocity (RM)
                        </h3>
                        <div class="h-64">
                            <canvas id="adminGlobalSpendingChart"></canvas>
                        </div>
                    </div>

                    <!-- Doughnut Chart: Category Share -->
                    <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-4">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-chart-pie text-emerald-600"></i> Category Cost Allocation
                        </h3>
                        <div class="h-64 flex items-center justify-center">
                            <canvas id="categoryDonutChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top Merchant Outflow Breakdown Chart -->
                <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-store text-orange-500"></i> Top Vendor & Merchant Disbursed Spending (RM)
                    </h3>
                    <div class="h-56">
                        <canvas id="adminMerchantTrafficChart"></canvas>
                    </div>
                </div>

                <!-- Automated Executive Insights -->
                <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-2">
                    <h3 class="text-xs font-bold text-slate-800 tracking-tight flex items-center gap-2">
                        <i class="fa-solid fa-lightbulb text-amber-500"></i> Automated Executive Insights
                    </h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Sistem mengesan aliran perbelanjaan paling tinggi tertumpu kepada logistik perjalanan tapak bagi klien <strong>Aero Art Sdn Bhd</strong>. Disarankan pihak pengurusan mengekalkan siling perbatuan semasa (RM0.60/KM) bagi memelihara imbangan margin tuntutan staf.
                    </p>
                </div>

            </main>
        </div>
    </div>

    <!-- Chart.js Engine -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const realLineData = @json($chartLineData ?? array_fill(0, 12, 0));
            const realMerchantLabels = @json($merchantLabels ?? []);
            const realMerchantCounts = @json($merchantCounts ?? []);
            const realCategoryLabels = @json($categoryLabels ?? []);
            const realCategoryTotals = @json($categoryTotals ?? []);

            // 1. Monthly Outflow Line Chart
            const ctxGlobalLine = document.getElementById('adminGlobalSpendingChart').getContext('2d');
            new Chart(ctxGlobalLine, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Total Expenses (RM)',
                        data: realLineData,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.04)',
                        borderWidth: 2.5,
                        tension: 0.35,
                        pointBackgroundColor: '#10b981',
                        pointHoverRadius: 6,
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
                                callback: function (value) { return 'RM ' + value; }
                            }
                        },
                        x: { grid: { display: false }, ticks: { font: { size: 9 } } }
                    }
                }
            });

            // 2. Category Donut Chart
            const ctxDonut = document.getElementById('categoryDonutChart').getContext('2d');
            new Chart(ctxDonut, {
                type: 'doughnut',
                data: {
                    labels: realCategoryLabels.length > 0 ? realCategoryLabels : ['No Data'],
                    datasets: [{
                        data: realCategoryTotals.length > 0 ? realCategoryTotals : [0],
                        backgroundColor: ['#059669', '#2563eb', '#f59e0b', '#dc2626', '#8b5cf6'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } }
                    },
                    cutout: '70%'
                }
            });

            // 3. Top Merchant Bar Chart
            const ctxMerchantBar = document.getElementById('adminMerchantTrafficChart').getContext('2d');
            new Chart(ctxMerchantBar, {
                type: 'bar',
                data: {
                    labels: realMerchantLabels.length > 0 ? realMerchantLabels : ['No Data'],
                    datasets: [{
                        label: 'Total Spent (RM)',
                        data: realMerchantCounts.length > 0 ? realMerchantCounts : [0],
                        backgroundColor: '#f97316',
                        borderRadius: 6,
                        maxBarThickness: 32
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
                                callback: function (value) { return 'RM ' + value; }
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
