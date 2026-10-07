@extends('layouts.staff')

@section('title', 'SmartClaim - Analytics Dashboard')

@section('content')
            <div class="space-y-6 md:space-y-8">

                <!-- Header Actions -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="space-y-0.5">
                        <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Analytics Dashboard</h1>
                        <p class="text-xs md:text-sm text-slate-500">Real-time financial summaries and AI-parsed
                            automated expense trends.</p>
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <div class="hidden lg:flex items-center gap-3">
                            <x-system-clock />
                            @include('layouts.partials.notification-bell')
                        </div>

                        <a href="{{ route('claims.create') }}?type=Receipt"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-[#00d1b2] hover:bg-[#00bfa5] text-white font-bold py-3 px-5 rounded-xl text-xs tracking-wider uppercase transition-all shadow-xs cursor-pointer">
                            <i class="fa-solid fa-plus text-xs"></i> New Expense Claim
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div
                        class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-semibold flex items-center gap-2.5 shadow-3xs">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <div
                    class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between text-xs text-slate-500 pb-2 font-medium border-b border-slate-100">
                    <div>
                        Welcome back, <span
                            class="font-bold text-slate-800">{{ auth()->user()->name ?? 'Staff User' }}</span> (ID:
                        {{ auth()->user()->user_id ?? auth()->id() ?? '1' }})
                    </div>
                    <div class="text-slate-400">
                        Total Claims Submitted: <span
                            class="font-mono font-bold text-slate-800 bg-slate-200/60 px-1.5 py-0.5 rounded-md">{{ ($reimbursedCount ?? 0) + ($approvedCount ?? 0) + ($preApprovedCount ?? 0) + ($pendingCount ?? 0) + ($rejectedCount ?? 0) }}
                            Logs</span>
                    </div>
                </div>

                <!-- Responsive 3-Card Summary Deck -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 md:gap-4">

                    <!-- 1. Total Disbursed / Paid -->
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 sm:p-5 w-full flex items-center justify-between gap-3">
                        <div class="space-y-1 min-w-0 flex-1 truncate">
                            <span class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider text-emerald-700 block truncate">
                                <i class="fa-solid fa-circle-check mr-1 text-emerald-600"></i> Disbursed to Bank
                            </span>
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight font-mono truncate">
                                RM {{ number_format($totalDisbursedAmount ?? 0, 2) }}
                            </h3>
                            <span class="text-[11px] text-emerald-700/80 font-bold block truncate">{{ $reimbursedCount ?? 0 }} paid claim(s)</span>
                        </div>
                        <div class="w-11 h-11 bg-emerald-500 text-white rounded-2xl flex items-center justify-center text-lg shadow-sm shrink-0">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                    </div>

                    <!-- 2. Processing Pipeline -->
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 sm:p-5 w-full space-y-3 flex flex-col justify-between">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider text-slate-500 block truncate">
                                <i class="fa-solid fa-arrows-rotate mr-1 text-blue-500"></i> Processing Pipeline
                            </span>
                            <span class="px-2 py-0.5 bg-blue-50 text-blue-700 font-mono font-black text-[10px] rounded-lg border border-blue-100 shrink-0">
                                {{ ($approvedCount ?? 0) + ($preApprovedCount ?? 0) + ($pendingCount ?? 0) }} Active
                            </span>
                        </div>

                        <div class="grid grid-cols-3 gap-2 sm:gap-3 font-mono">
                            <div class="p-2 sm:p-3 bg-slate-50/80 rounded-xl text-center border border-slate-100/80">
                                <span class="text-[9px] text-slate-400 block font-sans truncate">Pending</span>
                                <strong class="text-xs sm:text-sm font-bold text-amber-600 block mt-0.5">{{ $pendingCount ?? 0 }}</strong>
                            </div>
                            <div class="p-2 sm:p-3 bg-slate-50/80 rounded-xl text-center border border-slate-100/80">
                                <span class="text-[9px] text-slate-400 block font-sans truncate">Pre-Appr</span>
                                <strong class="text-xs sm:text-sm font-bold text-indigo-600 block mt-0.5">{{ $preApprovedCount ?? 0 }}</strong>
                            </div>
                            <div class="p-2 sm:p-3 bg-slate-50/80 rounded-xl text-center border border-slate-100/80">
                                <span class="text-[9px] text-slate-400 block font-sans truncate">Approved</span>
                                <strong class="text-xs sm:text-sm font-bold text-teal-600 block mt-0.5">{{ $approvedCount ?? 0 }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Rejected Claims -->
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 sm:p-5 w-full flex items-center justify-between gap-3">
                        <div class="space-y-1 min-w-0 flex-1 truncate">
                            <span class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider text-rose-600 block truncate">
                                <i class="fa-solid fa-triangle-exclamation mr-1"></i> Rejected Claims
                            </span>
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight font-mono truncate">
                                {{ $rejectedCount ?? 0 }} <span class="text-xs font-normal text-slate-400 font-sans">Voucher(s)</span>
                            </h3>
                            <span class="text-[11px] text-slate-400 font-medium block truncate">Policy breaches / flagged</span>
                        </div>
                        <div class="w-11 h-11 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center text-lg border border-rose-100 shrink-0">
                            <i class="fa-solid fa-circle-xmark"></i>
                        </div>
                    </div>

                </div>

                <!-- Monthly Entitlement Tracker -->
                @if(isset($expensePolicies) && count($expensePolicies) > 0)
                <div class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-100 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="space-y-0.5">
                            <h3 class="text-xs md:text-sm font-bold text-slate-800 tracking-tight"><i class="fa-solid fa-chart-pie mr-1 text-slate-400"></i> Monthly Entitlement Tracker</h3>
                            <p class="text-[11px] md:text-xs text-slate-400">Track your current month claims against company budget quotas.</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($expensePolicies as $policy)
                            @php
                                $catName = $policy->category_name ?? $policy->name ?? $policy->policy_name ?? $policy->category ?? 'General';
                                $spend = $monthlyCategorySpend[$catName] ?? 0;
                                $budget = $policy->monthly_budget_cap ?? $policy->monthly_limit ?? $policy->budget_limit ?? 0;
                                $percentage = $budget > 0 ? min(100, ($spend / $budget) * 100) : 0;
                                $colorClass = 'bg-emerald-500';
                                if($percentage >= 90) $colorClass = 'bg-rose-500';
                                elseif($percentage >= 75) $colorClass = 'bg-amber-500';
                            @endphp
                            <div class="space-y-1.5 p-3 rounded-2xl border {{ $percentage >= 100 ? 'border-rose-100 bg-rose-50/20' : 'border-slate-100 bg-slate-50/50' }}">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-[11px] font-bold">
                                    <span class="text-slate-700 truncate sm:max-w-[55%]">{{ $catName }}</span>
                                    <span class="text-slate-900 font-mono shrink-0">RM {{ number_format($spend, 2) }} <span class="text-slate-400 font-normal">/ RM {{ number_format($budget, 2) }}</span></span>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden flex">
                                    <div class="{{ $colorClass }} h-2 rounded-full transition-all duration-500" style="width: {{ $percentage }}%"></div>
                                </div>
                                @if($percentage >= 100)
                                    <p class="text-[9px] text-rose-600 font-bold"><i class="fa-solid fa-triangle-exclamation"></i> Budget limit reached for this month</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-white p-4 sm:p-6 rounded-2xl border border-slate-100 shadow-sm">
                        <h3 class="text-xs md:text-sm font-bold text-slate-800 tracking-tight mb-4">Monthly Claimed
                            (2026)</h3>
                        <div class="relative w-full h-[220px] md:h-[280px]">
                            <canvas id="monthlySpendingChart"></canvas>
                        </div>
                    </div>

                    <div class="lg:col-span-1 bg-white p-4 sm:p-6 rounded-2xl border border-slate-100 shadow-sm">
                        <h3 class="text-xs md:text-sm font-bold text-slate-800 tracking-tight mb-4">By Category</h3>
                        <div class="relative w-full h-[220px] md:h-[280px]">
                            <canvas id="categoryDistributionChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Claims Table -->
                <div class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-100 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div class="space-y-0.5">
                            <h3 class="text-xs md:text-sm font-bold text-slate-800 tracking-tight">Recent Claims</h3>
                            <p class="text-[11px] md:text-xs text-slate-400">Latest expense claims submitted for Aero
                                Art review.</p>
                        </div>
                        <a href="{{ route('claims.history') }}"
                            class="text-[11px] md:text-xs font-bold text-blue-600 hover:underline">View History →</a>
                    </div>

                    <div class="overflow-x-auto -mx-4 px-4 md:mx-0 md:px-0">
                        <table class="w-full text-left border-collapse text-xs min-w-[600px] sm:min-w-full">
                            <thead>
                                <tr
                                    class="border-b border-slate-100 text-slate-400 font-bold tracking-wide uppercase bg-slate-50/50 rounded-xl">
                                    <th class="py-3 px-3 md:px-4">Merchant</th>
                                    <th class="py-3 px-3 md:px-4">Invoice No</th>
                                    <th class="py-3 px-3 md:px-4">Date</th>
                                    <th class="py-3 px-3 md:px-4">Category</th>
                                    <th class="py-3 px-3 md:px-4 text-right">Amount</th>
                                    <th class="py-3 px-3 md:px-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 text-slate-700 font-medium">
                                @forelse($recentClaims as $claim)
                                    <tr class="hover:bg-slate-50/60 transition-all">
                                        <td class="py-3.5 px-3 md:px-4 font-bold text-slate-950 max-w-[140px] truncate"
                                            title="{{ $claim->merchant_name }}">
                                            {{ $claim->merchant_name }}
                                        </td>
                                        <td class="py-3.5 px-3 md:px-4 font-mono text-slate-500 truncate max-w-[100px]">
                                            {{ $claim->receipt_invoice_no }}
                                        </td>
                                        <td class="py-3.5 px-3 md:px-4 text-slate-500 whitespace-nowrap">
                                            {{ date('d M Y', strtotime($claim->transaction_date)) }}
                                        </td>
                                        <td class="py-3.5 px-3 md:px-4 whitespace-nowrap">
                                            <span
                                                class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md font-semibold text-[10px]">
                                                {{ $claim->predicted_category }}
                                            </span>
                                        </td>
                                        <td
                                            class="py-3.5 px-3 md:px-4 text-right font-bold text-slate-900 whitespace-nowrap font-mono">
                                            RM {{ number_format($claim->amount, 2) }}
                                        </td>
                                        <td class="py-3.5 px-3 md:px-4 text-center whitespace-nowrap">
                                            <div class="flex flex-col items-center gap-1.5 mt-1">
                                                <span
                                                    class="px-2.5 py-1 rounded-full font-bold text-[9px] uppercase tracking-wide
                                                        {{ $claim->status === 'Reimbursed' || $claim->status === 'Disbursed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '' }}
                                                        {{ $claim->status === 'Approved' ? 'bg-teal-50 text-teal-700 border border-teal-200' : '' }}
                                                        {{ $claim->status === 'Pre-Approved' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : '' }}
                                                        {{ $claim->status === 'Pending Manager' || $claim->status === 'Pending Finance' || $claim->status === 'Pending' ? 'bg-amber-50 text-amber-700 border border-amber-200' : '' }}
                                                        {{ $claim->status === 'Rejected' ? 'bg-rose-50 text-rose-700 border border-rose-200' : '' }}">
                                                    {{ $claim->status }}
                                                </span>
                                                @if($claim->sla_status)
                                                    <span class="px-2 py-0.5 rounded-full font-bold text-[8px] tracking-wide border flex items-center gap-1 justify-center whitespace-nowrap
                                                        {{ $claim->sla_status === 'breached' ? 'bg-amber-50 text-amber-700 border-amber-200' : ($claim->sla_status === 'resolved' ? 'bg-slate-50 text-slate-500 border-slate-200' : 'bg-blue-50 text-blue-700 border-blue-200') }}">
                                                        @if($claim->sla_status !== 'resolved' && $claim->sla_status !== 'breached')
                                                            <span class="relative flex h-1.5 w-1.5 shrink-0">
                                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                                                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-blue-500"></span>
                                                            </span>
                                                        @elseif($claim->sla_status === 'breached')
                                                            <i class="fa-solid fa-triangle-exclamation text-[8px]"></i>
                                                        @else
                                                            <i class="fa-solid fa-check text-[8px]"></i>
                                                        @endif
                                                        <span>
                                                            {{ $claim->sla_status === 'breached' ? 'Delayed - Expedited Review Active' : 
                                                               (in_array($claim->status, ['Pending Manager', 'Pending']) ? 'Est. Pre-Approval: ' . $claim->time_remaining_human : 
                                                               (in_array($claim->status, ['Approved', 'Pending Finance']) ? 'Est. Payout: by ' . ($claim->estimated_completion_at ? $claim->estimated_completion_at->format('M d, Y') : '') . ' (Finance Batch)' : 
                                                               $claim->time_remaining_human)) }}
                                                        </span>
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-12 text-center">
                                            <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                                <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 mb-3 border border-slate-200/60 shadow-inner">
                                                    <i class="fa-solid fa-receipt text-2xl"></i>
                                                </div>
                                                <h4 class="text-sm font-bold text-slate-800">No Expense Claims Yet</h4>
                                                <p class="text-xs text-slate-400 mt-1 mb-4 text-center">You have not submitted any reimbursement claims for this period.</p>
                                                <a href="{{ route('claims.create') }}" class="inline-flex items-center gap-2 bg-[#00d1b2] hover:bg-[#00bfa5] text-white text-xs font-bold py-2 px-4 rounded-xl shadow-xs transition">
                                                    <i class="fa-solid fa-plus text-[10px]"></i> Submit Your First Claim
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

@push('scripts')
    <!-- Chart.js Engine -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Line Chart - Monthly Spending
            const ctxLine = document.getElementById('monthlySpendingChart').getContext('2d');
            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Approved & Reimbursed (RM)',
                        data: @json($lineChartData),
                        borderColor: '#059669',
                        backgroundColor: 'rgba(5, 150, 105, 0.08)',
                        borderWidth: 2,
                        tension: 0.35,
                        pointBackgroundColor: '#059669',
                        pointHoverRadius: 6,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true, labels: { boxWidth: 10, font: { size: 10, weight: 'bold' } } }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 9 } } },
                        x: { grid: { display: false }, ticks: { font: { size: 9 } } }
                    }
                }
            });

            // Bar Chart - Category Distribution
            const ctxBar = document.getElementById('categoryDistributionChart').getContext('2d');
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: @json($barLabels),
                    datasets: [{
                        label: 'Spent Amount (RM)',
                        data: @json($barValues),
                        backgroundColor: '#0284c7',
                        borderRadius: 6,
                        borderWidth: 0,
                        maxBarThickness: 32
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 9 } } },
                        x: { grid: { display: false }, ticks: { font: { size: 9 } } }
                    }
                }
            });
        });
    </script>
@endpush
@endsection
