@extends('layouts.manager')

@section('title', 'SmartClaim - Manager Analytics Command')

@section('content')
<div class="space-y-6">

    <div class="border-b border-slate-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Manager BI Dashboard</h1>
            <p class="text-xs md:text-sm text-slate-500">Real-time organizational expenditure metrics,
                operational data charts, and institutional sign-off summaries.</p>
        </div>
        <div class="hidden lg:flex items-center gap-3">
            <x-system-clock />
        </div>
    </div>

    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between text-xs text-slate-500 pb-2 font-medium border-b border-slate-100">
        <div>Welcome back, <span class="font-bold text-slate-800">Executive Manager</span></div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
        <div class="bg-white p-4 md:p-5 rounded-2xl md:rounded-3xl border border-slate-200/60 shadow-2xs flex items-center justify-between">
            <div class="space-y-0.5 truncate">
                <span class="text-[9px] md:text-[11px] font-bold uppercase tracking-wider text-slate-400 block truncate">Pending Sign-off</span>
                <h3 class="text-base md:text-xl font-bold text-amber-600 tracking-tight truncate">
                    {{ $preApprovedCount ?? 0 }} Claims
                </h3>
            </div>
            <div class="w-9 h-9 md:w-11 md:h-11 bg-amber-50 rounded-xl flex items-center justify-center border border-amber-100 text-amber-600 shrink-0 ml-2">
                <i class="fa-solid fa-hourglass-half text-xs md:text-base"></i>
            </div>
        </div>

        <div class="bg-white p-4 md:p-5 rounded-2xl md:rounded-3xl border border-slate-200/60 shadow-2xs flex items-center justify-between">
            <div class="space-y-0.5 truncate">
                <span class="text-[9px] md:text-[11px] font-bold uppercase tracking-wider text-slate-400 block truncate">Accepted Review</span>
                <h3 class="text-base md:text-xl font-bold text-emerald-600 tracking-tight truncate">
                    {{ $approvedCount ?? 0 }} Claims
                </h3>
            </div>
            <div class="w-9 h-9 md:w-11 md:h-11 bg-emerald-50 rounded-xl flex items-center justify-center border border-emerald-100 text-emerald-600 shrink-0 ml-2">
                <i class="fa-solid fa-circle-check text-xs md:text-base"></i>
            </div>
        </div>

        <div class="bg-white p-4 md:p-5 rounded-2xl md:rounded-3xl border border-slate-200/60 shadow-2xs flex items-center justify-between">
            <div class="space-y-0.5 truncate">
                <span class="text-[9px] md:text-[11px] font-bold uppercase tracking-wider text-slate-400 block truncate">Rejected Review</span>
                <h3 class="text-base md:text-xl font-bold text-rose-600 tracking-tight truncate">
                    {{ $rejectedCount ?? 0 }} Claims
                </h3>
            </div>
            <div class="w-9 h-9 md:w-11 md:h-11 bg-rose-50 rounded-xl flex items-center justify-center border border-rose-100 text-rose-600 shrink-0 ml-2">
                <i class="fa-solid fa-circle-xmark text-xs md:text-base"></i>
            </div>
        </div>

        <div class="bg-white p-4 md:p-5 rounded-2xl md:rounded-3xl border border-slate-200/60 shadow-2xs flex items-center justify-between">
            <div class="space-y-0.5 truncate">
                <span class="text-[9px] md:text-[11px] font-bold uppercase tracking-wider text-slate-400 block truncate">Total System Logs</span>
                <h3 class="text-base md:text-xl font-bold text-indigo-600 tracking-tight truncate">
                    {{ $totalReviewCount ?? 0 }} Records
                </h3>
            </div>
            <div class="w-9 h-9 md:w-11 md:h-11 bg-indigo-50 rounded-xl flex items-center justify-center border border-indigo-100 text-indigo-600 shrink-0 ml-2">
                <i class="fa-solid fa-database text-xs md:text-base"></i>
            </div>
        </div>
    </div>

    <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-2xs">
        <h3 class="text-xs md:text-sm font-bold text-slate-800 tracking-tight mb-4">Monthly Approved Expense Trends (2026)</h3>
        <div class="relative w-full h-[240px] md:h-[300px]">
            <canvas id="managerBIChart"></canvas>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
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
                            label: function (context) {
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
                            callback: function (value) { return 'RM ' + value; }
                        }
                    },
                    x: { grid: { display: false }, ticks: { font: { size: 9 } } }
                }
            }
        });
    });
</script>
@endpush
@endsection
