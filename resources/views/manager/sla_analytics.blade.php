<!DOCTYPE html>
<html lang="en" x-data="{ isMobileSidebarOpen: false }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - SLA Analytics</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" :class="isMobileSidebarOpen ? 'overflow-hidden' : ''">

    <div class="flex flex-col lg:flex-row min-h-screen">

        @include('layouts.partials.manager-sidebar')

        <main class="flex-1 w-full px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-6 pb-24 md:pb-8">
            <div class="space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                        <i class="fa-solid fa-stopwatch text-blue-600"></i> SLA & Approval Velocity
                    </h1>
                    <p class="text-xs md:text-sm text-slate-500 font-medium">
                        Monitor bottleneck metrics and expedite delayed claims across the pipeline.
                    </p>
                </div>
            </div>

            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-3xl p-5 border border-slate-200/60 shadow-2xs relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-50 rounded-full blur-2xl group-hover:bg-blue-100 transition-colors"></div>
                    <div class="flex items-center gap-4 relative z-10">
                        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-xl font-bold shadow-inner">
                            <i class="fa-solid fa-user-clock"></i>
                        </div>
                        <div>
                            <p class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider">Avg Manager TAT</p>
                            <div class="flex items-baseline gap-2">
                                <h3 class="text-xl md:text-2xl font-black text-slate-900 font-mono">{{ $avgManagerTat }} hrs</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-3xl p-5 border border-slate-200/60 shadow-2xs relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-50 rounded-full blur-2xl group-hover:bg-emerald-100 transition-colors"></div>
                    <div class="flex items-center gap-4 relative z-10">
                        <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl font-bold shadow-inner">
                            <i class="fa-solid fa-money-bill-transfer"></i>
                        </div>
                        <div>
                            <p class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider">Avg Finance Settlement</p>
                            <div class="flex items-baseline gap-2">
                                <h3 class="text-xl md:text-2xl font-black text-slate-900 font-mono">{{ $avgFinanceTat }} hrs</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-3xl p-5 border border-slate-200/60 shadow-2xs relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-50 rounded-full blur-2xl group-hover:bg-amber-100 transition-colors"></div>
                    <div class="flex items-center gap-4 relative z-10">
                        <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center text-xl font-bold shadow-inner">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div>
                            <p class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider">Overdue Bottlenecks</p>
                            <div class="flex items-baseline gap-2">
                                <h3 class="text-xl md:text-2xl font-black text-slate-900 font-mono">{{ $bottlenecks->count() }} claims</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottlenecks Table -->
            <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-2xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="space-y-0.5">
                        <h3 class="text-xs md:text-sm font-bold text-slate-800 tracking-tight flex items-center gap-2">
                            <i class="fa-solid fa-hourglass-end text-amber-500"></i> Action Required: SLA Breaches
                        </h3>
                    </div>
                </div>

                <div class="overflow-x-auto -mx-4 px-4 md:mx-0 md:px-0">
                    <table class="w-full text-left border-collapse text-xs min-w-[700px] sm:min-w-full">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold tracking-wide uppercase bg-slate-50/50 rounded-xl">
                                <th class="py-3 px-3 md:px-4">Claim ID</th>
                                <th class="py-3 px-3 md:px-4">Claimant Name</th>
                                <th class="py-3 px-3 md:px-4 text-center">Days in Queue</th>
                                <th class="py-3 px-3 md:px-4 text-center">Overdue</th>
                                <th class="py-3 px-3 md:px-4 text-right">Amount</th>
                                <th class="py-3 px-3 md:px-4 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-slate-700 font-medium">
                            @forelse($bottlenecks as $claim)
                                <tr class="hover:bg-slate-50/60 transition-all">
                                    <td class="py-3.5 px-3 md:px-4 font-mono font-bold text-slate-900">
                                        CLM-{{ $claim->claim_id }}
                                    </td>
                                    <td class="py-3.5 px-3 md:px-4">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-[10px]">
                                                {{ substr($claim->user->name ?? 'U', 0, 1) }}
                                            </div>
                                            <span class="font-bold text-slate-800">{{ $claim->user->name ?? 'Unknown' }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-3 md:px-4 text-center">
                                        <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md font-bold text-[10px]">
                                            {{ $claim->days_in_queue }} Days
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-3 md:px-4 text-center">
                                        <span class="px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-md font-bold text-[10px] inline-flex items-center gap-1">
                                            <i class="fa-solid fa-clock"></i> {{ $claim->hours_overdue }} hrs
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-3 md:px-4 text-right font-bold text-slate-900 font-mono">
                                        RM {{ number_format($claim->amount, 2) }}
                                    </td>
                                    <td class="py-3.5 px-3 md:px-4 text-center">
                                        <a href="{{ route('manager.verification') }}?claim_id={{ $claim->claim_id }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[10px] font-bold transition shadow-3xs">
                                            Review Now <i class="fa-solid fa-arrow-right"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-slate-400 font-sans">
                                        <div class="flex flex-col items-center justify-center space-y-2">
                                            <i class="fa-solid fa-circle-check text-4xl text-emerald-300 mb-2"></i>
                                            <span class="text-sm text-slate-500 font-bold">No Bottlenecks!</span>
                                            <span class="text-xs text-slate-400">All claims are processing within SLA parameters.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </main>
        @include('layouts.partials.bottom-nav')
    </div>

</body>
</html>
