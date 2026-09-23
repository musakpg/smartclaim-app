<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Claim History</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" x-data="historyManager()" x-init="allClaims = {{ json_encode($claims->map(fn($c) => array_merge($c->toArray(), [
    'items' => $c->items->toArray(),
    'created_at_formatted' => $c->created_at->format('Y-m-d H:i')
]))) }}" :class="isModalOpen || isMobileSidebarOpen ? 'overflow-hidden' : ''">

    <div class="flex min-h-screen flex-col lg:flex-row">

        <!-- Mobile Sidebar Drawer -->
        <div x-show="isMobileSidebarOpen" x-cloak class="lg:hidden fixed inset-0 z-50 flex" role="dialog"
            aria-modal="true">
            <div x-show="isMobileSidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"
                @click="isMobileSidebarOpen = false"></div>
            <div x-show="isMobileSidebarOpen" x-transition:enter="transition ease-in-out duration-300 transform"
                x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in-out duration-300 transform"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                class="relative flex w-full max-w-xs flex-1 flex-col bg-white pt-5 pb-4 border-r border-[#e2e8f0]">
                <div class="absolute top-4 right-4">
                    <button type="button" @click="isMobileSidebarOpen = false"
                        class="w-8 h-8 flex items-center justify-center bg-slate-100 rounded-lg text-slate-500 cursor-pointer">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="px-6 pb-4 border-b border-[#f1f5f9] flex items-center gap-2">
                    <i class="fa-solid fa-wallet text-slate-800 text-xl"></i>
                    <span class="font-bold text-lg tracking-tight text-slate-900">SmartClaim</span>
                </div>
                <nav class="mt-4 flex-1 px-4 space-y-1 overflow-y-auto" x-data="{ isClaimsOpenMobile: true }">
                    <a href="{{ route('dashboard') }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50">
                        <i class="fa-solid fa-house"></i> Dashboard
                    </a>
                    <div>
                        <button type="button" @click.prevent="isClaimsOpenMobile = !isClaimsOpenMobile"
                            class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50 hover:text-slate-900 cursor-pointer">
                            <span class="flex items-center gap-3"><i class="fa-solid fa-file-pen"></i> Claims</span>
                            <i class="fa-solid text-[10px]"
                                :class="isClaimsOpenMobile ? 'fa-chevron-down rotate-180' : 'fa-chevron-right'"></i>
                        </button>
                        <div x-show="isClaimsOpenMobile"
                            class="pl-6 mt-1 space-y-1 py-1 bg-slate-50 rounded-xl border border-slate-100">
                            <a href="{{ route('claims.create') }}?type=Receipt"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-slate-500 flex items-center gap-2"><i
                                    class="fa-solid fa-file-invoice text-[11px] text-slate-400"></i> Based on Receipt
                                (OCR)</a>
                            <a href="{{ route('claims.create') }}?type=Mileage"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-slate-500 flex items-center gap-2"><i
                                    class="fa-solid fa-motorcycle text-[11px] text-slate-400"></i> Mileage Allowance</a>
                            <a href="{{ route('claims.history') }}"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-bold text-blue-600 bg-blue-50/60 flex items-center gap-2">
                                <i class="fa-solid fa-clipboard-list text-[11px] text-blue-600"></i> My Claims
                            </a>
                        </div>
                    </div>

                    <a href="{{ route('reimbursement.index') }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50">
                        <i class="fa-solid fa-hand-holding-dollar text-slate-400"></i> Reimbursement Status
                    </a>
                    <a href="{{ route('profile.index') }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50">
                        <i class="fa-solid fa-user text-slate-400"></i> My Profile
                    </a>
                    <a href="{{ route('policy.index') }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50">
                        <i class="fa-solid fa-file-shield text-slate-400"></i> Company Policy
                    </a>
                    <a href="{{ route('logout') }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-rose-50 hover:text-rose-600">
                        <i class="fa-solid fa-door-open text-slate-400"></i> Sign Out
                    </a>
                </nav>
            </div>
        </div>

        <!-- Reusable Staff Navigation Sidebar (Desktop) -->
        @include('layouts.partials.staff-sidebar')

        <main class="flex-1 p-4 md:p-8 max-w-5xl mx-auto w-full pb-24 lg:pb-8 overflow-hidden">
            <div class="space-y-6">
                <div class="space-y-0.5">
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                        <span>Claim History</span>
                        <span class="text-xs font-mono font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md">
                            {{ count($claims) }} Logs
                        </span>
                    </h1>
                    <p class="text-xs md:text-sm text-slate-500">Complete records of claims generated dynamically via
                        local extraction engines.</p>
                </div>

                <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-xs space-y-4">
                    <div class="divide-y divide-slate-100">
                        <template x-for="claim in pagedItems" :key="claim.claim_id">
                            <div
                                class="flex flex-col sm:flex-row sm:items-center justify-between py-4 first:pt-0 last:pb-0 gap-4">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center border border-slate-100 font-bold text-xs text-slate-700 font-mono">
                                        <span x-text="'CLM-' + claim.claim_id"></span>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-bold text-slate-900 text-sm"
                                                x-text="claim.claim_type === 'Mileage' ? (claim.title ?? 'Travel Allowance Claim') : claim.merchant_name">
                                            </h4>
                                            <span
                                                class="px-2 py-0.5 rounded-full font-bold text-[9px] uppercase tracking-wide border"
                                                :class="claim.status === 'Approved' || claim.status === 'Reimbursed' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 
                                                        claim.status === 'Pending' ? 'bg-amber-50 text-amber-700 border-amber-100' : 
                                                        'bg-slate-50 text-slate-700 border-slate-100'"
                                                x-text="claim.status"></span>
                                        </div>

                                        <div class="text-xs text-slate-500 font-medium space-y-1">
                                            <template x-if="claim.claim_type === 'Mileage'">
                                                <div class="space-y-0.5">
                                                    <span class="block">Logistics: <span
                                                            class="font-semibold text-slate-700"
                                                            x-text="(claim.vehicle_type ?? 'Vehicle') + ' (' + parseFloat(claim.mileage_km).toFixed(2) + ' KM)'"></span></span>
                                                    <span
                                                        class="inline-flex items-center gap-1.5 bg-slate-50 border border-slate-100 px-2 py-0.5 rounded-md text-[11px] text-slate-600 mt-0.5">
                                                        <i class="fa-solid fa-map-location-dot text-slate-400"></i>
                                                        <span x-text="claim.start_location ?? 'Start Node'"></span>
                                                        <i
                                                            class="fa-solid fa-arrow-right-long text-[9px] text-slate-400"></i>
                                                        <span x-text="claim.destination_location ?? 'End Node'"></span>
                                                    </span>
                                                </div>
                                            </template>
                                            <template x-if="claim.claim_type !== 'Mileage'">
                                                <span>Invoice No: <span class="font-mono text-slate-700"
                                                        x-text="claim.receipt_invoice_no"></span></span>
                                            </template>
                                        </div>

                                        <p class="text-[10px] text-slate-400 font-medium">
                                            <i class="fa-solid fa-calendar-day mr-1"></i>Processed on:
                                            <span x-text="claim.created_at_formatted"></span>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end gap-3 sm:gap-4">
                                    <div class="text-right">
                                        <span
                                            class="text-xs font-bold text-slate-400 uppercase tracking-wider block text-[10px]">Total
                                            Amount</span>
                                        <span class="font-bold text-slate-900 text-sm"
                                            x-text="'RM ' + parseFloat(claim.amount).toFixed(2)"></span>
                                    </div>

                                    <!-- Butang Muat Turun PDF Baris Rekod -->
                                    <a :href="'/claims/' + claim.claim_id + '/download-pdf'" target="_blank"
                                        class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2 px-3 rounded-xl text-xs tracking-wide transition shadow-3xs">
                                        <i class="fa-solid fa-file-pdf text-rose-600"></i> PDF
                                    </a>

                                    <button type="button" @click="openDetailModal(claim)"
                                        class="inline-flex items-center gap-1.5 bg-[#1e293b] hover:bg-slate-800 text-white font-bold py-2 px-3.5 rounded-xl text-xs tracking-wide transition shadow-3xs cursor-pointer">
                                        <i class="fa-solid fa-circle-info text-xs"></i> Detail
                                    </button>
                                </div>
                            </div>
                        </template>

                        <div x-show="allClaims.length === 0" class="py-12 text-center text-slate-400 font-semibold"
                            x-cloak>
                            <i class="fa-solid fa-folder-open block text-2xl mb-2 text-slate-300"></i> No historical
                            claims loaded.
                        </div>
                    </div>

                    <!-- Pagination Navigation -->
                    <div x-show="allClaims.length > 0"
                        class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-bold"
                        x-cloak>
                        <div class="text-slate-400 font-medium">
                            Showing <span class="text-slate-700" x-text="((currentPage - 1) * perPage) + 1"></span>
                            to <span class="text-slate-700"
                                x-text="Math.min(currentPage * perPage, allClaims.length)"></span> of
                            <span class="text-slate-700" x-text="allClaims.length"></span> records
                        </div>

                        <div class="flex items-center gap-1.5">
                            <button type="button" @click="if(currentPage > 1) currentPage--"
                                :disabled="currentPage === 1"
                                class="px-3 py-2 border rounded-xl transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed border-slate-200 hover:border-slate-400">
                                <i class="fa-solid pointer-events-none fa-chevron-left text-[10px]"></i> Previous
                            </button>

                            <template x-for="page in totalPages" :key="page">
                                <button type="button" @click="currentPage = page"
                                    :class="currentPage === page ? 'bg-[#1e293b] text-white border-[#1e293b]' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-400'"
                                    class="w-8 h-8 border rounded-xl transition-all text-xs font-bold cursor-pointer"
                                    x-text="page"
                                    x-show="totalPages <= 7 || page === 1 || page === totalPages || Math.abs(page - currentPage) <= 1"></button>
                            </template>

                            <button type="button" @click="if(currentPage < totalPages) currentPage++"
                                :disabled="currentPage === totalPages"
                                class="px-3 py-2 border rounded-xl transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed border-slate-200 hover:border-slate-400">
                                Next <i class="fa-solid pointer-events-none fa-chevron-right text-[10px]"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Mobile Bottom Navigation -->
    <nav
        class="lg:hidden fixed bottom-0 inset-x-0 bg-white border-t border-[#e2e8f0] h-16 flex items-center justify-around z-40 px-2 shadow-md">
        <a href="{{ route('dashboard') }}"
            class="flex flex-col items-center justify-center flex-1 h-full py-2 text-slate-400">
            <i class="fa-solid fa-chart-pie text-xl block mb-0.5"></i><span
                class="text-[10px] font-bold">Dashboard</span>
        </a>
        <a href="{{ route('claims.create') }}?type=Receipt"
            class="flex flex-col items-center justify-center flex-1 h-full py-2 text-slate-400">
            <i class="fa-solid fa-file-circle-plus text-xl block mb-0.5"></i><span class="text-[10px] font-bold">New
                Claim</span>
        </a>
        <a href="{{ route('claims.history') }}"
            class="flex flex-col items-center justify-center flex-1 h-full py-2 text-[#3b82f6]">
            <i class="fa-solid fa-clock-rotate-left text-xl block mb-0.5"></i><span
                class="text-[10px] font-bold">History</span>
        </a>
    </nav>

    <!-- Modal Detail Claim -->
    <div x-show="isModalOpen" x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-all duration-300"
        @keydown.escape.window="isModalOpen = false">
        <div class="relative bg-white rounded-3xl p-4 max-w-5xl w-full shadow-2xl flex flex-col md:flex-row gap-5 max-h-[90vh] overflow-hidden border border-slate-100"
            @click.away="isModalOpen = false">

            <div class="flex-1 flex flex-col overflow-y-auto space-y-4 pr-1">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="space-y-0.5">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400"
                            x-text="'CLAIM ID: CLM-' + activeClaim.claim_id"></span>
                        <h3 class="text-base font-bold text-slate-900 tracking-tight"
                            x-text="activeClaim.claim_type === 'Mileage' ? (activeClaim.title ? activeClaim.title : 'Mileage Allowance Request') : activeClaim.merchant_name">
                        </h3>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase tracking-wide border"
                        :class="activeClaim.status === 'Approved' || activeClaim.status === 'Reimbursed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 
                                activeClaim.status === 'Pending' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                        x-text="activeClaim.status"></span>
                </div>

                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div class="space-y-1">
                        <span class="block font-bold text-slate-400 uppercase text-[9px] tracking-wide">Receipt Invoice
                            No</span>
                        <div class="p-2 bg-slate-50 border border-slate-100 font-mono text-slate-800 rounded-lg font-bold"
                            x-text="activeClaim.claim_type === 'Mileage' ? 'NOT APPLICABLE (MILEAGE)' : activeClaim.receipt_invoice_no">
                        </div>
                    </div>
                    <div class="space-y-1">
                        <span class="block font-bold text-slate-400 uppercase text-[9px] tracking-wide"
                            x-text="activeClaim.claim_type === 'Mileage' ? 'Travel Date' : 'Transaction Date'">
                        </span>
                        <div class="p-2 bg-slate-50 border border-slate-100 text-slate-800 rounded-lg font-bold"
                            x-text="activeClaim.transaction_date">
                        </div>
                    </div>
                    <div class="space-y-1">
                        <span class="block font-bold text-slate-400 uppercase text-[9px] tracking-wide">Expense
                            Category</span>
                        <div class="p-2 bg-slate-50 border border-slate-100 text-slate-800 rounded-lg font-bold"
                            x-text="activeClaim.claim_type === 'Mileage' ? 'Transport Travel Allowance' : activeClaim.predicted_category">
                        </div>
                    </div>
                    <div class="space-y-1">
                        <span class="block font-bold text-slate-400 uppercase text-[9px] tracking-wide"
                            x-text="activeClaim.claim_type === 'Mileage' ? 'Reimbursement Channel' : 'Payment Method'">
                        </span>
                        <div class="p-2 bg-slate-50 border border-slate-100 text-slate-800 rounded-lg font-bold"
                            x-text="activeClaim.claim_type === 'Mileage' ? 'Corporate Bank Allowance' : activeClaim.payment_method">
                        </div>
                    </div>

                    <div class="col-span-2 space-y-1" x-show="activeClaim.vehicle_plate_number">
                        <span
                            class="block font-bold text-rose-700 uppercase text-[9px] tracking-wide flex items-center gap-1"
                            x-text="activeClaim.claim_type === 'Mileage' ? 'Staff Personal Vehicle Declaration' : 'Authorized Fleet Tracking Node'">
                        </span>
                        <div
                            class="p-3 bg-rose-50/40 border border-rose-100 text-rose-950 rounded-xl font-black font-mono flex items-center justify-between shadow-3xs">
                            <div class="flex items-center gap-2">
                                <span
                                    class="px-2 py-0.5 bg-rose-600 text-white font-mono text-[9px] font-black rounded uppercase tracking-wider">
                                    Plate Index
                                </span>
                                <span class="text-sm tracking-widest" x-text="activeClaim.vehicle_plate_number"></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-2 space-y-1">
                        <span class="block font-bold text-slate-400 uppercase text-[9px] tracking-wide">Business
                            Operational Purpose</span>
                        <div class="p-2.5 bg-slate-50 border border-slate-100 text-slate-700 rounded-lg leading-relaxed whitespace-pre-line"
                            x-text="activeClaim.business_purpose"></div>
                    </div>
                </div>

                <div class="space-y-2 border-t border-slate-100 pt-3">
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        <i class="fa-solid fa-list-check"></i> Itemized Cost Breakdowns
                    </h4>
                    <div class="space-y-1.5 max-h-[160px] overflow-y-auto">
                        <template x-if="activeClaim.claim_type === 'Mileage'">
                            <div class="overflow-hidden border border-slate-200 rounded-xl bg-white space-y-3 p-3">
                                <div class="flex flex-col gap-1 border-b border-slate-100 pb-2 text-[11px]">
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wide">
                                        <i class="fa-solid fa-route"></i> Travel Route Nodes
                                    </span>
                                    <div class="flex items-center gap-2 font-semibold text-slate-800">
                                        <span x-text="activeClaim.start_location ?? 'N/A'"></span>
                                        <i class="fa-solid fa-arrow-right text-slate-400 text-[10px]"></i>
                                        <span x-text="activeClaim.destination_location ?? 'N/A'"></span>
                                    </div>
                                </div>

                                <table class="w-full text-left border-collapse text-[11px]">
                                    <thead
                                        class="bg-slate-50 text-slate-400 font-bold uppercase border-b border-slate-100 text-[9px]">
                                        <tr>
                                            <th class="p-2.5">Audit Parameter Metric</th>
                                            <th class="p-2.5 text-center">Logged Metric</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                        <tr>
                                            <td class="p-2.5 text-slate-500">Calculated Journey Distance</td>
                                            <td class="p-2.5 text-center font-mono font-bold text-slate-900"
                                                x-text="parseFloat(activeClaim.mileage_km || 0).toFixed(2) + ' KM'">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="p-2.5 text-slate-500">Allowance Rate (Per KM)</td>
                                            <td class="p-2.5 text-center font-mono font-bold text-slate-900"
                                                x-text="'RM ' + (activeClaim.vehicle_type === 'Motorcycle' ? '0.30' : '0.60')">
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>

                        <template x-if="activeClaim.claim_type !== 'Mileage'">
                            <div class="space-y-1.5">
                                <template x-for="(item, idx) in activeClaim.items" :key="idx">
                                    <div
                                        class="flex items-center justify-between p-2 bg-slate-50 rounded-xl border border-slate-100 text-xs">
                                        <div class="space-y-0.5">
                                            <p class="font-bold text-slate-900" x-text="item.item_name"></p>
                                            <p class="text-[10px] text-slate-400"
                                                x-text="item.quantity + ' x RM ' + parseFloat(item.unit_price).toFixed(2)">
                                            </p>
                                        </div>
                                        <span class="font-bold text-slate-900"
                                            x-text="'RM ' + parseFloat(item.subtotal).toFixed(2)"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Footer Modal Berserta Butang Muat Turun PDF -->
                <div
                    class="flex items-center justify-between border-t border-slate-100 pt-3 bg-white sticky bottom-0 mt-auto gap-2">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wide text-slate-400 block">Total Claim
                            Cost</span>
                        <span class="text-xl font-black text-slate-900 font-mono"
                            x-text="'RM ' + parseFloat(activeClaim.amount || 0).toFixed(2)"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <a :href="'/claims/' + activeClaim.claim_id + '/download-pdf'" target="_blank"
                            class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold rounded-xl text-xs tracking-wide transition flex items-center gap-1.5">
                            <i class="fa-solid fa-file-pdf"></i> Download PDF
                        </a>
                        <button type="button" @click="isModalOpen = false"
                            class="px-5 py-2 bg-slate-900 text-white font-bold rounded-xl text-xs tracking-wide hover:bg-slate-800 transition-all cursor-pointer">
                            Close Window
                        </button>
                    </div>
                </div>
            </div>

            <!-- Panel Paparan Imej Resit -->
            <div class="w-full md:w-[420px] bg-slate-50 rounded-2xl border border-slate-100 flex flex-col p-2 max-h-[40vh] md:max-h-full"
                x-show="activeClaim.receipt_image_path">
                <span class="text-[9px] font-bold uppercase text-slate-400 tracking-wider px-2 mb-1.5">
                    <i class="fa-solid fa-image mr-1"></i> Imbasan Resit Fizikal
                </span>
                <div
                    class="flex-1 bg-slate-900/5 rounded-xl overflow-hidden relative group flex items-center justify-center min-h-0">
                    <img :src="'/storage/' + activeClaim.receipt_image_path"
                        @click="modalPreviewSrc = '/storage/' + activeClaim.receipt_image_path; isHistoryModalOpen = true"
                        class="max-w-full max-h-full object-contain rounded-lg shadow-xs cursor-zoom-in">
                    <button type="button"
                        @click="modalPreviewSrc = '/storage/' + activeClaim.receipt_image_path; isHistoryModalOpen = true"
                        class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-all duration-200 text-white font-bold text-xs gap-1.5 backdrop-blur-xs cursor-zoom-in">
                        <i class="fa-solid fa-magnifying-glass-plus"></i> View Raw Asset Image
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- Zoom Preview Modal -->
    <div x-show="isHistoryModalOpen" x-cloak
        class="fixed inset-0 z-[250] flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs transition-all duration-300">
        <div class="relative bg-white rounded-3xl p-3 max-w-2xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
            @click.away="isHistoryModalOpen = false">
            <div class="flex items-center justify-between px-4 py-2 border-b border-slate-100">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">
                    <i class="fa-solid fa-receipt mr-1 text-blue-600"></i> Full View Receipt Asset
                </span>
                <button type="button" @click="isHistoryModalOpen = false"
                    class="text-slate-400 hover:text-rose-600 transition-all text-lg cursor-pointer p-1">
                    <i class="fa-solid fa-circle-xmark"></i>
                </button>
            </div>
            <div class="p-2 bg-slate-50 rounded-2xl overflow-y-auto flex-1 flex justify-center items-center min-h-0">
                <img :src="modalPreviewSrc" alt="Receipt Full Modal View"
                    class="max-w-full max-h-[75vh] object-contain rounded-xl shadow-2xs">
            </div>
        </div>
    </div>

    <script>
        function historyManager() {
            return {
                isMobileSidebarOpen: false,
                isModalOpen: false,
                activeClaim: {},
                isHistoryModalOpen: false,
                modalPreviewSrc: '',

                allClaims: [],
                currentPage: 1,
                perPage: 5,

                get totalPages() {
                    return Math.max(1, Math.ceil(this.allClaims.length / this.perPage));
                },
                get pagedItems() {
                    let start = (this.currentPage - 1) * this.perPage;
                    return this.allClaims.slice(start, start + this.perPage);
                },
                openDetailModal(claimData) {
                    this.activeClaim = claimData;
                    this.isModalOpen = true;
                }
            }
        }
    </script>
</body>

</html>