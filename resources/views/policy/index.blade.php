<!-- Comment: Dynamic Company Policy & Expense Eligibility Workspace -->
<!DOCTYPE html>
<html lang="en" x-data="{ isMobileSidebarOpen: false }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Company Policy & Guidelines</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased"
    :class="isMobileSidebarOpen ? 'overflow-hidden lg:overflow-auto' : ''">

    <div class="min-h-screen flex flex-col lg:flex-row">

        <!-- Staff Sidebar Partial -->
        @include('layouts.partials.staff-sidebar')

        <!-- Main Workspace Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">



            <main class="flex-1 p-4 md:p-8 max-w-4xl mx-auto w-full pb-24 lg:pb-8 overflow-y-auto space-y-6">

                <!-- Header Section -->
                <div class="border-b border-slate-200 pb-5">
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Company Policy & Guidelines
                    </h1>
                    <p class="text-xs md:text-sm text-slate-500">Aero Art Sdn Bhd Corporate Expense Eligibility Rates &
                        Maximum Budget Ceilings.</p>
                </div>

                <!-- 1. DYNAMIC MILEAGE CLAIM RATES (EXACT 2-CARD GRID) -->
                <div class="bg-white p-5 md:p-6 rounded-3xl border border-slate-200/60 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-route text-blue-600"></i> Mileage Claim Rates
                        </h3>
                        <span
                            class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full uppercase tracking-wider">
                            <i class="fa-solid fa-satellite-dish mr-1"></i> Live Rates
                        </span>
                    </div>

                    <p class="text-xs text-slate-500 leading-relaxed">
                        Mileage claims are calculated dynamically based on entered destination routes. Discrepancies
                        exceeding Google Maps tolerance thresholds will be flagged for audit review.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                        @php
                            $car = $mileageRates->firstWhere(fn($r) => strtolower($r->vehicle_type ?? '') === 'car');
                            $motor = $mileageRates->firstWhere(fn($r) => strtolower($r->vehicle_type ?? '') === 'motorcycle');

                            $carRateVal = $car->rate_per_km ?? 0.60;
                            $motorRateVal = $motor->rate_per_km ?? 0.30;
                        @endphp

                        <!-- 1. Car Rate Card -->
                        <div
                            class="p-4 rounded-2xl border border-indigo-100 bg-indigo-50/50 flex items-center justify-between gap-4 text-xs text-indigo-950">
                            <div class="truncate">
                                <span class="font-bold text-indigo-900 block mb-0.5 truncate">
                                    <i class="fa-solid fa-car text-indigo-600 mr-1.5"></i> Car Rate
                                </span>
                                <span class="text-slate-500 text-[11px] block truncate">Official business travel
                                    purposes</span>
                            </div>
                            <span class="text-base md:text-lg font-black font-mono whitespace-nowrap text-indigo-600">
                                RM {{ number_format($carRateVal, 2) }} <span
                                    class="text-[10px] text-slate-400 font-bold">/ KM</span>
                            </span>
                        </div>

                        <!-- 2. Motorcycle Rate Card -->
                        <div
                            class="p-4 rounded-2xl border border-orange-100 bg-orange-50/50 flex items-center justify-between gap-4 text-xs text-orange-950">
                            <div class="truncate">
                                <span class="font-bold text-orange-900 block mb-0.5 truncate">
                                    <i class="fa-solid fa-motorcycle text-orange-600 mr-1.5"></i> Motorcycle Rate
                                </span>
                                <span class="text-slate-500 text-[11px] block truncate">On-site logistics &
                                    dispatch</span>
                            </div>
                            <span class="text-base md:text-lg font-black font-mono whitespace-nowrap text-orange-600">
                                RM {{ number_format($motorRateVal, 2) }} <span
                                    class="text-[10px] text-slate-400 font-bold">/ KM</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 2. DYNAMIC RECEIPT CATEGORIES & BUDGET CAPS (FROM EXPENSE POLICIES) -->
                <div class="bg-white p-5 md:p-6 rounded-3xl border border-slate-200/60 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-shield-halved text-emerald-600"></i> Category Spending Limits & Caps
                        </h3>
                        <span class="text-[10px] font-bold text-slate-400 font-mono uppercase">
                            AI Enforced
                        </span>
                    </div>

                    <p class="text-xs text-slate-500 leading-relaxed">
                        Every receipt claim is audited against corporate policy ceilings configured by Aero Art Sdn Bhd
                        management. Claims exceeding these limits will trigger verification warnings.
                    </p>

                    <div class="divide-y divide-slate-100 text-xs font-medium">
                        @forelse($expensePolicies as $policy)
                            <div class="py-4 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                                <div>
                                    <span class="font-bold text-slate-900 block text-sm">{{ $policy->category->name ?? 'Unknown Category' }}</span>
                                    <p class="text-[11px] text-slate-400 mt-0.5">
                                        {{ $policy->description ?? 'Standard corporate reimbursement guidelines apply.' }}
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 sm:self-center shrink-0">
                                    @if($policy->max_single_claim_limit > 0)
                                        <span
                                            class="px-3 py-1 bg-slate-100 border border-slate-200 rounded-xl text-slate-800 font-mono font-bold text-xs">
                                            Single Max: RM {{ number_format($policy->max_single_claim_limit, 2) }}
                                        </span>
                                    @endif

                                    @if($policy->monthly_budget_cap > 0)
                                        <span
                                            class="px-3 py-1 bg-blue-50 border border-blue-100 text-blue-700 font-mono font-bold text-xs">
                                            Monthly Cap: RM {{ number_format($policy->monthly_budget_cap, 2) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="py-6 text-center text-slate-400">
                                <i class="fa-solid fa-file-circle-check text-2xl mb-1 text-slate-300 block"></i>
                                Standard company expense policies apply to all claim entries.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Compliance Notice -->
                <div
                    class="bg-slate-900/5 p-4 rounded-2xl border border-slate-100 text-xs text-slate-500 space-y-1.5 leading-relaxed">
                    <p class="font-bold text-slate-800 uppercase text-[10px] tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-info text-blue-500"></i> Audit Compliance Notice
                    </p>
                    <p class="text-[11px] text-slate-500">
                        Multipliers and caps configured here are synchronized in real time with the executive portal.
                        Historical claim records logged in <strong>Approved</strong> matrices remain protected to
                        guarantee audit ledger integrity.
                    </p>
                </div>

            </main>
        </div>
    </div>
</body>

</html>
