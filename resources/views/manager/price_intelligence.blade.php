<!DOCTYPE html>
<html lang="en" x-data="{ isMobileSidebarOpen: false }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Price Intelligence & Procurement Analytics</title>
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

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased">
    <div class="flex flex-col lg:flex-row min-h-screen">

        <!-- Centralized Manager Sidebar Partial -->
        @include('layouts.partials.manager-sidebar')

        <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full pb-24 overflow-y-auto space-y-6">

            <!-- Header Banner -->
            <div class="border-b border-slate-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div>
                        <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Price Intelligence &
                        Merchant Comparison</h1>
                    <p class="text-xs md:text-sm text-slate-500">Cross-merchant cost analysis to discover optimal
                        procurement sources and price anomalies.</p>
                    </div>
                    <div class="hidden lg:flex items-center gap-3">
                        <x-system-clock />
                    </div>
                </div>

                <!-- Search Filter -->
                <form method="GET" action="{{ route('manager.price_intelligence') }}" class="flex items-center gap-2">
                    <div class="relative">
                        <i
                            class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="search" value="{{ $search }}"
                            placeholder="Search item (e.g. Ayam, Kertas)..."
                            class="pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none w-64 shadow-2xs">
                    </div>
                    <button type="submit"
                        class="px-3.5 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800">Filter</button>
                </form>
            </div>

            <!-- Top Most Purchased Items Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach($topFrequentItems->take(3) as $top)
                    <div class="bg-white p-4.5 rounded-3xl border border-slate-200/60 shadow-xs space-y-1">
                        <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 block truncate">High
                            Demand Procurement Item</span>
                        <h3 class="text-base font-bold text-slate-900 truncate">{{ $top->item_name }}</h3>
                        <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs font-mono">
                            <span class="text-slate-500">Total Qty: <strong>{{ (int) $top->total_qty }}
                                    units</strong></span>
                            <span class="text-emerald-700 font-bold">RM {{ number_format($top->total_spend, 2) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Price Comparison Matrix Table -->
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                        <i class="fa-solid fa-scale-balanced text-blue-600 mr-1.5"></i> Cross-Merchant Comparative
                        Matrix
                    </span>
                    <span class="text-[11px] text-slate-400 font-medium">{{ count($comparisonData) }} item cluster(s)
                        indexed</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs min-w-[700px]">
                        <thead
                            class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="p-3.5">Purchased Item Particulars</th>
                                <th class="p-3.5">Lowest Price Source (Best Deal)</th>
                                <th class="p-3.5">Highest Recorded Source</th>
                                <th class="p-3.5 text-center">Variance / Potential Savings</th>
                                <th class="p-3.5">All Recorded Vendor Prices</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @forelse($comparisonData as $row)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="p-3.5">
                                        <span class="font-black text-slate-900 block text-xs">{{ $row->item_name }}</span>
                                        <span class="text-[10px] text-slate-400 font-mono">{{ $row->total_purchases }} total
                                            claim log(s)</span>
                                    </td>
                                    <td class="p-3.5">
                                        <div
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800">
                                            <i class="fa-solid fa-tag text-[10px]"></i>
                                            <span class="font-bold">{{ $row->cheapest_merchant }}</span>
                                            <strong class="font-mono text-emerald-950 font-black">RM
                                                {{ number_format($row->cheapest_price, 2) }}</strong>
                                        </div>
                                    </td>
                                    <td class="p-3.5">
                                        <div
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-rose-50 border border-rose-200 rounded-xl text-rose-800">
                                            <i class="fa-solid fa-arrow-trend-up text-[10px]"></i>
                                            <span class="font-bold">{{ $row->expensive_merchant }}</span>
                                            <strong class="font-mono text-rose-950 font-black">RM
                                                {{ number_format($row->expensive_price, 2) }}</strong>
                                        </div>
                                    </td>
                                    <td class="p-3.5 text-center">
                                        @if($row->potential_savings_pct > 0)
                                            <span
                                                class="px-2 py-0.5 bg-blue-100 text-blue-800 font-black rounded-md text-[10px] font-mono">
                                                Save {{ $row->potential_savings_pct }}%
                                            </span>
                                        @else
                                            <span class="text-slate-300 font-mono text-[10px]">Single Price Log</span>
                                        @endif
                                    </td>
                                    <td class="p-3.5">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($row->merchant_breakdown as $m)
                                                <span
                                                    class="px-2 py-0.5 bg-slate-100 border border-slate-200 rounded-md text-[10px] text-slate-700">
                                                    {{ $m->merchant_name }}: <strong class="font-mono">RM
                                                        {{ number_format($m->avg_price, 2) }}</strong>
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-slate-400 font-semibold">
                                        <i class="fa-solid fa-basket-shopping block text-2xl mb-2 text-slate-300"></i>
                                        No itemized purchasing data found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</body>

</html>
