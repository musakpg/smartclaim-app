<!DOCTYPE html>
<html lang="en" x-data="{ isMobileSidebarOpen: false }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Finance Dashboard</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased"
    :class="isMobileSidebarOpen ? 'overflow-hidden lg:overflow-auto' : ''">

    <div class="min-h-screen flex flex-col lg:flex-row">

        <!-- Finance Sidebar Partial -->
        @include('layouts.partials.finance-sidebar')

        <!-- Main Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">



            <!-- Main Content -->
            <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full overflow-y-auto">
                <div class="space-y-6">

                    <!-- Page Title Header -->
                    <div class="border-b border-slate-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                            <div>
                        <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Finance Management
                            Executive Dashboard</h1>
                        <p class="text-xs md:text-sm text-slate-500">Executive summary of global financial claim
                            operations for Aero Art Sdn Bhd.</p>
                    </div>
                    <div class="hidden lg:flex items-center gap-3">
                        <x-system-clock />
                    </div>
                </div>

                    <!-- 4 KPI Metrics Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
                        <!-- 1. Pending Audit Verification -->
                        <div
                            class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs flex items-center justify-between">
                            <div class="space-y-1 truncate">
                                <span
                                    class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block truncate">Pending
                                    Verification</span>
                                <h3 class="text-xl md:text-2xl font-black text-amber-600 font-mono tracking-tight">
                                    {{ $pendingCount ?? 0 }} Claims
                                </h3>
                            </div>
                            <div
                                class="w-11 h-11 bg-amber-50 rounded-xl flex items-center justify-center border border-amber-100 shrink-0 ml-2">
                                <i class="fa-solid fa-hourglass-half text-amber-600"></i>
                            </div>
                        </div>

                        <!-- 2. Awaiting Payout -->
                        <div
                            class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs flex items-center justify-between">
                            <div class="space-y-1 truncate">
                                <span
                                    class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block truncate">Awaiting
                                    Payout</span>
                                <h3 class="text-xl md:text-2xl font-black text-teal-600 font-mono tracking-tight">
                                    {{ $approvedCount ?? 0 }} Claims
                                </h3>
                            </div>
                            <div
                                class="w-11 h-11 bg-teal-50 rounded-xl flex items-center justify-center border border-teal-100 shrink-0 ml-2">
                                <i class="fa-solid fa-clock-rotate-left text-teal-600"></i>
                            </div>
                        </div>

                        <!-- 3. Total Reimbursed / Settled -->
                        <div
                            class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs flex items-center justify-between">
                            <div class="space-y-1 truncate">
                                <span
                                    class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block truncate">Reimbursed
                                    / Paid</span>
                                <h3 class="text-xl md:text-2xl font-black text-emerald-600 font-mono tracking-tight">
                                    {{ $reimbursedCount ?? 0 }} Claims
                                </h3>
                            </div>
                            <div
                                class="w-11 h-11 bg-emerald-50 rounded-xl flex items-center justify-center border border-emerald-100 shrink-0 ml-2">
                                <i class="fa-solid fa-circle-check text-emerald-600"></i>
                            </div>
                        </div>

                        <!-- 4. Total Rejected Logs -->
                        <div
                            class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs flex items-center justify-between">
                            <div class="space-y-1 truncate">
                                <span
                                    class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block truncate">Total
                                    Rejected</span>
                                <h3 class="text-xl md:text-2xl font-black text-rose-600 font-mono tracking-tight">
                                    {{ $rejectedCount ?? 0 }} Logs
                                </h3>
                            </div>
                            <div
                                class="w-11 h-11 bg-rose-50 rounded-xl flex items-center justify-center border border-rose-100 shrink-0 ml-2">
                                <i class="fa-solid fa-circle-xmark text-rose-600"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Trends Chart -->
                    <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-xs">
                        <h3 class="text-xs md:text-sm font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <i class="fa-solid fa-wave-square text-blue-500"></i> Monthly Approved & Disbursed Expense
                            Trends (2026)
                        </h3>
                        <div class="relative w-full h-[220px] md:h-[260px]">
                            <canvas id="dashboardQuickLineChart"></canvas>
                        </div>
                    </div>

                    <!-- Latest Submitted Claims Queue -->
                    <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-xs space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3 gap-2">
                            <div class="space-y-0.5">
                                <h3 class="text-xs md:text-sm font-bold text-slate-800">
                                    <i class="fa-solid fa-list-ol text-slate-400 mr-1"></i> Latest Submitted Claims
                                    Queue
                                </h3>
                                <p class="text-[11px] text-slate-400 hidden sm:block">Queue records fetched natively
                                    across local data repositories.</p>
                            </div>
                            <a href="{{ route('finance.auditing') }}?status=Pending"
                                class="text-[11px] md:text-xs font-bold text-blue-600 hover:underline shrink-0">Open
                                Auditing Workspace →</a>
                        </div>

                        <div class="overflow-x-auto -mx-4 px-4 md:mx-0 md:px-0">
                            <table class="w-full text-left border-collapse text-xs min-w-[600px] sm:min-w-full">
                                <thead>
                                    <tr
                                        class="border-b border-slate-100 text-slate-400 font-bold tracking-wide uppercase bg-slate-50/50 rounded-xl">
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
                                            <td class="py-3.5 px-4 font-bold text-slate-900 whitespace-nowrap">
                                                {{ $claim->user->name ?? 'Staff' }}
                                            </td>
                                            <td class="py-3.5 px-4 font-mono text-slate-400 whitespace-nowrap">
                                                CLM-{{ $claim->claim_id }}
                                            </td>
                                            <td class="py-3.5 px-4 font-bold text-slate-950 truncate max-w-[160px]"
                                                title="{{ $claim->merchant_name }}">
                                                {{ $claim->claim_type === 'Mileage' ? ($claim->title ?? 'Travel Allowance Claim') : $claim->merchant_name }}
                                            </td>
                                            <td
                                                class="py-3.5 px-4 text-right font-black text-slate-900 whitespace-nowrap font-mono">
                                                RM {{ number_format($claim->amount, 2) }}
                                            </td>
                                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                                <span
                                                    class="px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase tracking-wide
                                                        {{ $claim->status === 'Reimbursed' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : '' }}
                                                        {{ $claim->status === 'Approved' ? 'bg-teal-50 text-teal-700 border border-teal-200' : '' }}
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
                                                <i class="fa-solid fa-folder-open block text-xl mb-1.5 text-slate-300"></i>
                                                No historical entries found.
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const realDashboardLineData = @json($dashboardLineData ?? array_fill(0, 12, 0));

            const ctxDashboardLine = document.getElementById('dashboardQuickLineChart').getContext('2d');
            new Chart(ctxDashboardLine, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Approved & Disbursed Expenses (RM)',
                        data: realDashboardLineData,
                        borderColor: '#059669',
                        backgroundColor: 'rgba(5, 150, 105, 0.06)',
                        borderWidth: 2.5,
                        tension: 0.35,
                        pointBackgroundColor: '#059669',
                        pointHoverRadius: 6,
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
