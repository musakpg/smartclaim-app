<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Vehicle Usage & Trip Audit</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" x-data="{ isMobileSidebarOpen: false }">

    <div class="flex min-h-screen">
        <!-- Reusable Manager Navigation Sidebar -->
        @include('layouts.partials.manager-sidebar')

        <!-- Main Workspace -->
        <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full pb-24 lg:pb-8 overflow-hidden space-y-6">

            <!-- Header Title & Export Action -->
            <div class="border-b border-slate-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Vehicle Usage & Trip
                        History</h1>
                    <p class="text-xs md:text-sm text-slate-500">Cross-audit logistical asset trips, personal mileage
                        logs, and fuel expenditures.</p>
                </div>
                <a href="{{ route('manager.vehicle_history.export', request()->query()) }}"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition shadow-xs">
                    <i class="fa-solid fa-file-csv text-sm"></i> Export Audit (CSV)
                </a>
            </div>

            <!-- Analytical Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Mileage
                        Distance</span>
                    <h3 class="text-2xl font-black text-slate-900 font-mono">{{ number_format($totalKm, 2) }} <span
                            class="text-xs font-sans text-slate-500 font-bold">KM</span></h3>
                </div>
                <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Mileage Reimbursement
                        Total</span>
                    <h3 class="text-2xl font-black text-blue-600 font-mono">RM
                        {{ number_format($totalMileagePayout, 2) }}
                    </h3>
                </div>
                <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Corporate Fleet Fuel
                        Incurred</span>
                    <h3 class="text-2xl font-black text-emerald-600 font-mono">RM
                        {{ number_format($totalFuelExpense, 2) }}
                    </h3>
                </div>
            </div>

            <!-- Filter Controls Bar -->
            <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs">
                <form action="{{ route('manager.vehicle_history') }}" method="GET"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Ownership Scope</label>
                        <select name="ownership_type"
                            class="w-full p-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                            <option value="">All Ownerships</option>
                            <option value="personal" {{ request('ownership_type') === 'personal' ? 'selected' : '' }}>
                                Personal Vehicles</option>
                            <option value="company" {{ request('ownership_type') === 'company' ? 'selected' : '' }}>
                                Company Fleet</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Vehicle Plate</label>
                        <select name="plate_number"
                            class="w-full p-2 bg-slate-50 border border-slate-200 rounded-xl outline-none font-mono">
                            <option value="">All Registered Plates</option>
                            @foreach($allRegisteredPlates as $plate)
                                <option value="{{ $plate }}" {{ request('plate_number') === $plate ? 'selected' : '' }}>
                                    {{ $plate }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Date From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                            class="w-full p-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Date To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                            class="w-full p-2 bg-slate-50 border border-slate-200 rounded-xl outline-none">
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit"
                            class="flex-1 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition">
                            <i class="fa-solid fa-filter mr-1"></i> Apply
                        </button>
                        <a href="{{ route('manager.vehicle_history') }}"
                            class="py-2.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-xl transition">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Audit Table Ledger -->
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs min-w-[850px]">
                        <thead
                            class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="p-4">Voucher & Date</th>
                                <th class="p-4">Driver (Staff)</th>
                                <th class="p-4">Plate / Asset</th>
                                <th class="p-4">Claim Scope</th>
                                <th class="p-4">Destination / Merchant</th>
                                <th class="p-4 text-right">Distance / Amount</th>
                                <th class="p-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @forelse($logs as $log)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="p-4">
                                        <span
                                            class="font-mono font-bold text-slate-900 block">CLM-{{ str_pad($log->claim_id, 4, '0', STR_PAD_LEFT) }}</span>
                                        <span
                                            class="text-[11px] text-slate-400">{{ \Carbon\Carbon::parse($log->transaction_date)->format('d M Y') }}</span>
                                    </td>
                                    <td class="p-4">
                                        <p class="font-bold text-slate-900">{{ $log->user->name ?? 'Staff' }}</p>
                                    </td>
                                    <td class="p-4">
                                        <span
                                            class="font-mono font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded">{{ $log->vehicle_plate_number ?? ($log->vehicle->plate_number ?? 'N/A') }}</span>
                                        <span
                                            class="block text-[10px] text-slate-400 uppercase mt-0.5">{{ $log->vehicle->ownership_type ?? 'Personal' }}</span>
                                    </td>
                                    <td class="p-4">
                                        @if($log->claim_type === 'Mileage')
                                            <span
                                                class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100">
                                                Mileage (Personal)
                                            </span>
                                        @else
                                            <span
                                                class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                Fuel (Fleet)
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-4 max-w-xs truncate text-slate-700">
                                        @if($log->claim_type === 'Mileage')
                                            <span title="{{ $log->start_location }} -> {{ $log->destination_location }}">
                                                {{ $log->start_location }} <i
                                                    class="fa-solid fa-arrow-right text-[10px] text-slate-400"></i>
                                                {{ $log->destination_location }}
                                            </span>
                                        @else
                                            {{ $log->merchant_name }}
                                        @endif
                                    </td>
                                    <td class="p-4 text-right font-mono font-bold">
                                        @if($log->claim_type === 'Mileage')
                                            <span
                                                class="text-slate-500 text-[11px] block">{{ number_format($log->mileage_km, 2) }}
                                                KM</span>
                                        @endif
                                        <span class="text-slate-900">RM {{ number_format($log->amount, 2) }}</span>
                                    </td>
                                    <td class="p-4 text-center">
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                                                    {{ $log->status === 'Approved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($log->status === 'Rejected' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                            {{ $log->status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-8 text-center text-slate-400">
                                        <i class="fa-solid fa-route block text-2xl mb-2 text-slate-300"></i>
                                        No vehicle trip or usage records matched the specified criteria.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                @if($logs->hasPages())
                    <div class="p-4 border-t border-slate-100">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>

        </main>
    </div>

</body>

</html>