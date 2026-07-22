<!DOCTYPE html>
<html lang="en" x-data="{ isMobileSidebarOpen: false }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased">

    <div class="flex min-h-screen flex-col lg:flex-row">
        
        <header class="lg:hidden bg-white border-b border-[#e2e8f0] px-4 py-4 flex items-center justify-between sticky top-0 z-40 shadow-2xs">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-wallet text-slate-800 text-xl"></i>
                <span class="font-bold text-lg tracking-tight text-slate-900">SmartClaim</span>
            </div>
            <button type="button" @click="isMobileSidebarOpen = true" class="w-9 h-9 flex items-center justify-center bg-slate-100 rounded-xl text-slate-700 cursor-pointer transition-all">
                <i class="fa-solid fa-bars text-base"></i>
            </button>
        </header>

        <div x-show="isMobileSidebarOpen" x-cloak class="lg:hidden fixed inset-0 z-50 flex" role="dialog" aria-modal="true">
            <div x-show="isMobileSidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="isMobileSidebarOpen = false"></div>

            <div x-show="isMobileSidebarOpen" x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="relative flex w-full max-w-xs flex-1 flex-col bg-white pt-5 pb-4 border-r border-[#e2e8f0]">
                <div class="absolute top-4 right-4">
                    <button type="button" @click="isMobileSidebarOpen = false" class="w-8 h-8 flex items-center justify-center bg-slate-100 rounded-lg text-slate-500 hover:text-slate-800 cursor-pointer">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="px-6 pb-4 border-b border-[#f1f5f9] flex items-center gap-2">
                    <i class="fa-solid fa-wallet text-slate-800 text-xl"></i>
                    <span class="font-bold text-lg tracking-tight text-slate-900">SmartClaim</span>
                </div>
                <nav class="mt-4 flex-1 px-4 space-y-1 overflow-y-auto" x-data="{ isClaimsOpenMobile: false }">
                    <a href="{{ route('dashboard') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold bg-[#f1f5f9] text-[#1e293b]">
                        <i class="fa-solid fa-house"></i> Dashboard
                    </a>
                    <div>
                        <button type="button" @click.prevent="isClaimsOpenMobile = !isClaimsOpenMobile" class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50 hover:text-slate-900 cursor-pointer">
                            <span class="flex items-center gap-3"><i class="fa-solid fa-file-pen"></i> Claims</span>
                            <i class="fa-solid text-[10px] transition-transform duration-200" :class="isClaimsOpenMobile ? 'fa-chevron-down rotate-180' : 'fa-chevron-right'"></i>
                        </button>
                        <div x-show="isClaimsOpenMobile" class="pl-6 mt-1 space-y-1 py-1 bg-slate-50 rounded-xl border border-slate-100">
                            <a href="{{ route('claims.create') }}?type=Receipt" class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-slate-500 flex items-center gap-2"><i class="fa-solid fa-file-invoice text-[11px]"></i> Based on Receipt (OCR)</a>
                            <a href="{{ route('claims.create') }}?type=Mileage" class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-slate-500 flex items-center gap-2"><i class="fa-solid fa-motorcycle text-[11px]"></i> Mileage Allowance</a>
                            <a href="{{ route('claims.history') }}" class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-slate-500 flex items-center gap-2"><i class="fa-solid fa-clipboard-list text-[11px]"></i> My Claims</a>
                        </div>
                    </div>
                    <a href="{{ route('reimbursement.index') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50">
                        <i class="fa-solid fa-hand-holding-dollar"></i> Reimbursement Status
                    </a>
                    <a href="{{ route('profile.index') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50">
                        <i class="fa-solid fa-user"></i> My Profile
                    </a>
                    <a href="{{ route('policy.index') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50">
                        <i class="fa-solid fa-file-shield"></i> Company Policy
                    </a>
                    <a href="{{ route('logout') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-rose-50 hover:text-rose-600">
                        <i class="fa-solid fa-door-open"></i> Sign Out
                    </a>
                </nav>
            </div>
        </div>

        <aside class="hidden lg:flex fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-[#e2e8f0] flex-col h-screen sticky top-0" 
               x-data="{ isClaimsOpen: true }"> 
            <div class="px-6 py-5 border-b border-[#f1f5f9] flex items-center gap-2">
                <i class="fa-solid fa-wallet text-slate-800 text-2xl"></i>
                <span class="font-bold text-xl tracking-tight text-slate-900">SmartClaim</span>
            </div>
            
            <nav class="flex-1 px-4 py-4 space-y-1">
                <a href="{{ route('dashboard') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold bg-[#f1f5f9] text-[#1e293b] transition-all">
                    <i class="fa-solid fa-house text-base"></i> Dashboard
                </a>
                
                <div>
                    <button type="button" 
                            @click.prevent="isClaimsOpen = !isClaimsOpen" 
                            class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50 hover:text-slate-900 transition-all cursor-pointer">
                        <span class="flex items-center gap-3 pointer-events-none">
                            <i class="fa-solid fa-file-pen text-base"></i> Claims
                        </span>
                        <i class="fa-solid text-[10px] transition-transform duration-200 pointer-events-none" 
                           :class="isClaimsOpen ? 'fa-chevron-down rotate-180' : 'fa-chevron-right'"></i>
                    </button>

                    <div x-show="isClaimsOpen" x-cloak x-transition class="pl-6 mt-1 space-y-1 py-1 bg-slate-50 rounded-xl border border-slate-100">
                        <a href="{{ route('claims.create') }}?type=Receipt" class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-slate-500 hover:text-slate-900 transition-all flex items-center gap-2">
                            <i class="fa-solid fa-file-invoice text-[11px]"></i> Based on Receipt (OCR)
                        </a>
                        <a href="{{ route('claims.create') }}?type=Mileage" class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-slate-500 hover:text-slate-900 transition-all flex items-center gap-2">
                            <i class="fa-solid fa-motorcycle text-[11px]"></i> Mileage Allowance
                        </a>
                        <a href="{{ route('claims.history') }}" class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-slate-500 hover:text-slate-900 transition-all flex items-center gap-2">
                            <i class="fa-solid fa-clipboard-list text-[11px]"></i> My Claims
                        </a>
                    </div>
                </div>

                <a href="{{ route('reimbursement.index') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50 transition-all">
                    <i class="fa-solid fa-hand-holding-dollar text-base"></i> Reimbursement Status
                </a>
                <a href="{{ route('profile.index') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50 transition-all">
                    <i class="fa-solid fa-user text-base"></i> My Profile
                </a>
                <a href="{{ route('policy.index') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50 transition-all">
                    <i class="fa-solid fa-file-shield text-base"></i> Company Policy
                </a>
                <a href="{{ route('logout') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-rose-50 hover:text-rose-600 transition-all">
                    <i class="fa-solid fa-door-open text-base"></i> Sign Out
                </a>
            </nav>
        </aside>

        <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full pb-24 lg:pb-8 overflow-hidden">
            <div class="space-y-6 md:space-y-8">
                
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="space-y-0.5">
                        <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Analytics Dashboard</h1>
                        <p class="text-xs md:text-sm text-slate-500">Real-time financial summaries and AI-parsed automated expense trends.</p>
                    </div>
                    <div class="w-full sm:w-auto">
                        <a href="{{ route('claims.create') }}?type=Receipt" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-[#00d1b2] hover:bg-[#00bfa5] text-white font-bold py-3 px-5 rounded-xl text-xs tracking-wider uppercase transition-all shadow-xs cursor-pointer">
                            <i class="fa-solid fa-plus text-xs"></i> New Expense Claim
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-semibold flex items-center gap-2.5 shadow-3xs">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between text-xs text-slate-500 pb-2 font-medium border-b border-slate-100">
                    <div>
                        Welcome back, <span class="font-bold text-slate-800">{{ auth()->user()->name ?? 'Staff User' }}</span> (ID: {{ auth()->user()->user_id ?? 'N/A' }})
                    </div>
                    <div class="text-slate-400">
                        Total Claims Submitted: <span class="font-mono font-bold text-slate-800 bg-slate-200/60 px-1.5 py-0.5 rounded-md">{{ $approvedCount + $preApprovedCount + $pendingCount + $rejectedCount }} Logs</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
                    
                    <div class="bg-white p-4 md:p-5 rounded-2xl md:rounded-3xl border border-slate-200/60 shadow-2xs flex items-center justify-between">
                        <div class="space-y-0.5 md:space-y-1 truncate">
                            <span class="text-[9px] md:text-[11px] font-bold uppercase tracking-wider text-slate-400 block truncate">Approved</span>
                            <h3 class="text-base md:text-xl font-bold text-slate-900 tracking-tight truncate">
                                {{ $approvedCount }} {{ $approvedCount <= 1 ? 'Claim' : 'Claims' }}
                            </h3>
                        </div>
                        <div class="w-9 h-9 md:w-11 md:h-11 bg-emerald-50 rounded-xl flex items-center justify-center border border-emerald-100/80 text-emerald-600 shrink-0 ml-2">
                            <i class="fa-solid fa-circle-check text-sm md:text-base"></i>
                        </div>
                    </div>

                    <div class="bg-white p-4 md:p-5 rounded-2xl md:rounded-3xl border border-slate-200/60 shadow-2xs flex items-center justify-between">
                        <div class="space-y-0.5 md:space-y-1 truncate">
                            <span class="text-[9px] md:text-[11px] font-bold uppercase tracking-wider text-slate-400 block truncate">Pre-Approved</span>
                            <h3 class="text-base md:text-xl font-bold text-slate-900 tracking-tight truncate">
                                {{ $preApprovedCount }} {{ $preApprovedCount <= 1 ? 'Claim' : 'Claims' }}
                            </h3>
                        </div>
                        <div class="w-9 h-9 md:w-11 md:h-11 bg-indigo-50 rounded-xl flex items-center justify-center border border-indigo-100/80 text-indigo-600 shrink-0 ml-2">
                            <i class="fa-solid fa-hourglass-half text-sm md:text-base"></i>
                        </div>
                    </div>

                    <div class="bg-white p-4 md:p-5 rounded-2xl md:rounded-3xl border border-slate-200/60 shadow-2xs flex items-center justify-between">
                        <div class="space-y-0.5 md:space-y-1 truncate">
                            <span class="text-[9px] md:text-[11px] font-bold uppercase tracking-wider text-slate-400 block truncate">Pending</span>
                            <h3 class="text-base md:text-xl font-bold text-slate-900 tracking-tight truncate">
                                {{ $pendingCount }} {{ $pendingCount <= 1 ? 'Claim' : 'Claims' }}
                            </h3>
                        </div>
                        <div class="w-9 h-9 md:w-11 md:h-11 bg-amber-50 rounded-xl flex items-center justify-center border border-amber-100/80 text-amber-600 shrink-0 ml-2">
                            <i class="fa-solid fa-file-shield text-sm md:text-base"></i>
                        </div>
                    </div>

                    <div class="bg-white p-4 md:p-5 rounded-2xl md:rounded-3xl border border-slate-200/60 shadow-2xs flex items-center justify-between">
                        <div class="space-y-0.5 md:space-y-1 truncate">
                            <span class="text-[9px] md:text-[11px] font-bold uppercase tracking-wider text-slate-400 block truncate">Rejected</span>
                            <h3 class="text-base md:text-xl font-bold text-slate-900 tracking-tight truncate">
                                {{ $rejectedCount }} {{ $rejectedCount <= 1 ? 'Claim' : 'Claims' }}
                            </h3>
                        </div>
                        <div class="w-9 h-9 md:w-11 md:h-11 bg-rose-50 rounded-xl flex items-center justify-center border border-rose-100/80 text-rose-600 shrink-0 ml-2">
                            <i class="fa-solid fa-circle-xmark text-sm md:text-base"></i>
                        </div>
                    </div>

                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="lg:col-span-2 bg-white p-4 md:p-6 rounded-2xl md:rounded-3xl border border-slate-200/60 shadow-2xs">
                        <h3 class="text-xs md:text-sm font-bold text-slate-800 tracking-tight mb-4">Monthly Claimed (2026)</h3>
                        <div class="relative w-full h-[220px] md:h-[280px]">
                            <canvas id="monthlySpendingChart"></canvas>
                        </div>
                    </div>

                    <div class="lg:col-span-1 bg-white p-4 md:p-6 rounded-2xl md:rounded-3xl border border-slate-200/60 shadow-2xs">
                        <h3 class="text-xs md:text-sm font-bold text-slate-800 tracking-tight mb-4">By Category</h3>
                        <div class="relative w-full h-[220px] md:h-[280px]">
                            <canvas id="categoryDistributionChart"></canvas>
                        </div>
                    </div>

                </div>

                <div class="bg-white p-4 md:p-6 rounded-2xl md:rounded-3xl border border-slate-200/60 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div class="space-y-0.5">
                            <h3 class="text-xs md:text-sm font-bold text-slate-800 tracking-tight">Recent Claims</h3>
                            <p class="text-[11px] md:text-xs text-slate-400">Latest expense claims submitted for Aero Art review.</p>
                        </div>
                        <a href="{{ route('claims.history') }}" class="text-[11px] md:text-xs font-bold text-blue-600 hover:underline">View History →</a>
                    </div>

                    <div class="overflow-x-auto -mx-4 px-4 md:mx-0 md:px-0">
                        <table class="w-full text-left border-collapse text-xs min-w-[600px] sm:min-w-full">
                            <thead>
                                <tr class="border-b border-slate-100 text-slate-400 font-bold tracking-wide uppercase bg-slate-50/50 rounded-xl">
                                    <th class="py-3 px-3 md:px-4">Merchant</th>
                                    <th class="py-3 px-3 md:px-4">Invoice No</th>
                                    <th class="py-3 px-3 md:px-4">Date</th>
                                    <th class="py-3 px-3 md:px-4">Category</th>
                                    <th class="py-3 px-3 md:px-4 text-right">Amount</th>
                                    <th class="py-3 px-3 md:px-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 text-slate-700 font-medium">
                                @php
                                    $recentClaims = DB::table('claims')->where('user_id', auth()->id() ?? 1)->orderBy('created_at', 'desc')->take(5)->get();
                                @endphp
                                
                                @forelse($recentClaims as $claim)
                                    <tr class="hover:bg-slate-50/60 transition-all">
                                        <td class="py-3.5 px-3 md:px-4 font-bold text-slate-950 max-w-[140px] truncate" title="{{ $claim->merchant_name }}">{{ $claim->merchant_name }}</td>
                                        <td class="py-3.5 px-3 md:px-4 font-mono text-slate-500 truncate max-w-[100px]">{{ $claim->receipt_invoice_no }}</td>
                                        <td class="py-3.5 px-3 md:px-4 text-slate-500 whitespace-nowrap">{{ date('d M Y', strtotime($claim->transaction_date)) }}</td>
                                        <td class="py-3.5 px-3 md:px-4 whitespace-nowrap">
                                            <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md font-semibold text-[10px]">
                                                {{ $claim->predicted_category }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-3 md:px-4 text-right font-bold text-slate-900 whitespace-nowrap">RM {{ number_format($claim->amount, 2) }}</td>
                                        <td class="py-3.5 px-3 md:px-4 text-center whitespace-nowrap">
                                            <span class="px-2 py-0.5 rounded-full font-bold text-[9px] uppercase tracking-wide
                                                {{ $claim->status === 'Approved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '' }}
                                                {{ $claim->status === 'Pre-Approved' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : '' }}
                                                {{ $claim->status === 'Pending' ? 'bg-amber-50 text-amber-700 border border-amber-200' : '' }}
                                                {{ $claim->status === 'Rejected' ? 'bg-rose-50 text-rose-700 border border-rose-200' : '' }}">
                                                {{ $claim->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-slate-400 font-semibold">
                                            <i class="fa-solid fa-folder-open block text-xl mb-1.5 text-slate-300"></i> No claims records found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>

        <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-white border-t border-[#e2e8f0] h-16 flex items-center justify-around z-40 px-2 shadow-md">
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center flex-1 h-full py-2 {{ request()->routeIs('dashboard') ? 'text-blue-600' : 'text-slate-400' }}">
                <i class="fa-solid fa-chart-pie text-xl block mb-0.5"></i><span class="text-[10px] font-bold">Dashboard</span>
            </a>
            <a href="{{ route('claims.create') }}" class="flex flex-col items-center justify-center flex-1 h-full py-2 {{ request()->routeIs('claims.create') ? 'text-blue-600' : 'text-slate-400' }}">
                <i class="fa-solid fa-file-circle-plus text-xl block mb-0.5"></i><span class="text-[10px] font-bold">New Claim</span>
            </a>
            <a href="{{ route('claims.history') }}" class="flex flex-col items-center justify-center flex-1 h-full py-2 {{ request()->routeIs('claims.history') ? 'text-blue-600' : 'text-slate-400' }}">
                <i class="fa-solid fa-clock-rotate-left text-xl block mb-0.5"></i><span class="text-[10px] font-bold">History</span>
            </a>
        </nav>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            
            const ctxLine = document.getElementById('monthlySpendingChart').getContext('2d');
            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Approved Expenses (RM)', 
                        data: @json($lineChartData ?? []),     
                        borderColor: '#334155',
                        backgroundColor: 'rgba(51, 65, 85, 0.04)',
                        borderWidth: 2,
                        tension: 0.35,                    
                        pointBackgroundColor: '#334155',
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

            const ctxBar = document.getElementById('categoryDistributionChart').getContext('2d');
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: @json($barLabels ?? []), 
                    datasets: [{
                        label: 'Spent Amount (RM)', 
                        data: @json($barValues ?? []),    
                        backgroundColor: '#1d4ed8', 
                        borderRadius: 6,            
                        borderWidth: 0,
                        maxBarThickness: 32
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

        });
    </script>
</body>
</html>