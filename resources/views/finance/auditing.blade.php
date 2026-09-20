<!DOCTYPE html>
<html lang="en" x-data="{ 
    isMobileSidebarOpen: false, 
    activeTab: 'Receipt', 
    statusTab: (new URLSearchParams(window.location.search)).get('status') || 'Pending', 
    isModalOpen: false, 
    activeClaim: {}, 
    activeUser: '', 
    formattedSubmissionTime: '',
    isHistoryModalOpen: false,
    modalPreviewSrc: '',
    openReviewModal(claim, username, rawFormattedTime) {
        this.activeClaim = claim; 
        this.activeUser = username; 
        this.formattedSubmissionTime = rawFormattedTime; 
        this.isModalOpen = true;
    }
}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Claims Auditing Workspace</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased"
    :class="isModalOpen || isMobileSidebarOpen || isHistoryModalOpen ? 'overflow-hidden lg:overflow-auto' : ''">

    <div class="min-h-screen flex flex-col lg:flex-row">

        <!-- Finance Sidebar Partial -->
        @include('layouts.partials.finance-sidebar')

        <!-- Main Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Mobile Sticky Top Header -->
            <header class="lg:hidden flex items-center justify-between bg-[#0d1527] border-b border-slate-800 px-4 py-3 sticky top-0 z-30 shadow-md">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-[#00d1b2]/10 border border-[#00d1b2]/20 flex items-center justify-center text-[#00d1b2]">
                        <i class="fa-solid fa-shield text-sm"></i>
                    </div>
                    <div>
                        <span class="text-sm font-black text-white tracking-tight leading-none block">SmartClaim</span>
                        <span class="text-[9px] font-bold text-[#00d1b2] tracking-wider uppercase block">Finance Portal</span>
                    </div>
                </div>

                <button type="button" @click="isMobileSidebarOpen = true"
                    class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-800 text-slate-300 hover:text-white transition cursor-pointer">
                    <i class="fa-solid fa-bars text-sm"></i>
                </button>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full pb-24 lg:pb-8 overflow-y-auto">
                <div class="space-y-6">

                    <!-- Workspace Title Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-200 pb-5">
                        <div>
                            <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight"
                                x-text="'Auditing Workspace — ' + statusTab">Claims Verification</h1>
                            <p class="text-xs md:text-sm text-slate-500">Cross-examine AI-extracted metadata fields against raw image invoices below.</p>
                        </div>
                    </div>

                    <!-- Category Tab Filter -->
                    <div class="flex items-center p-1 bg-slate-200/60 rounded-xl max-w-md shadow-3xs text-xs mb-4">
                        <button type="button" @click="activeTab = 'Receipt'"
                            :class="activeTab === 'Receipt' ? 'bg-white text-slate-900 font-bold shadow-xs border border-slate-300' : 'text-slate-500 hover:text-slate-900 font-medium'"
                            class="flex-1 py-2 rounded-lg transition-all cursor-pointer flex items-center justify-center gap-2">
                            <i class="fa-solid fa-file-invoice-dollar"></i> Based on Receipt
                        </button>
                        <button type="button" @click="activeTab = 'Mileage'"
                            :class="activeTab === 'Mileage' ? 'bg-white text-blue-600 font-bold shadow-xs border border-slate-300' : 'text-slate-500 hover:text-slate-900 font-medium'"
                            class="flex-1 py-2 rounded-lg transition-all cursor-pointer flex items-center justify-center gap-2">
                            <i class="fa-solid fa-route"></i> Mileage Allowance
                        </button>
                    </div>

                    <!-- Claims Table Container -->
                    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-2xs overflow-hidden" x-data="{
                        allClaims: {{ json_encode($claims->load('items')->map(fn($c) => array_merge($c->toArray(), ['user_name' => $c->user->name ?? 'Unknown Staff', 'formatted_time' => $c->created_at->format('Y-m-d H:i')]))) }},
                        currentPage: 1,
                        perPage: 5,
                        get filteredClaims() {
                            return this.allClaims.filter(c => {
                                const isReceipt = this.activeTab === 'Receipt'
                                    ? (c.claim_type !== 'Mileage' && !(c.merchant_name || '').includes('Aero Art Transport'))
                                    : (c.claim_type === 'Mileage' || (c.merchant_name || '').includes('Aero Art Transport'));
                                const matchStatus = this.statusTab === 'All' || this.statusTab === c.status;
                                return isReceipt && matchStatus;
                            });
                        },
                        get totalRecords() { return this.filteredClaims.length },
                        get totalPages() { return Math.max(1, Math.ceil(this.totalRecords / this.perPage)) },
                        get pagedItems() {
                            let start = (this.currentPage - 1) * this.perPage;
                            return this.filteredClaims.slice(start, start + this.perPage);
                        },
                        resetPage() { this.currentPage = 1; }
                    }" x-init="$watch('activeTab', () => resetPage()); $watch('statusTab', () => resetPage())">

                        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wide">
                                <i class="fa-solid fa-list-check mr-1.5 text-slate-400"></i> Claim Records
                            </span>
                            <span class="text-[11px] text-slate-400 font-medium"
                                x-text="totalRecords + ' record(s) found'"></span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs min-w-[700px]">
                                <thead>
                                    <tr class="border-b border-slate-100 text-slate-400 font-bold tracking-wide uppercase bg-slate-50/50">
                                        <th class="py-3 px-5">Employee Name</th>
                                        <th class="py-3 px-5">Claim ID</th>
                                        <th class="py-3 px-5">Merchant / Details</th>
                                        <th class="py-3 px-5">Invoice No / Route</th>
                                        <th class="py-3 px-5">Date Submitted</th>
                                        <th class="py-3 px-5 text-right">Amount</th>
                                        <th class="py-3 px-5 text-center">Status</th>
                                        <th class="py-3 px-5 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 text-slate-700 font-medium">

                                    <template x-for="(claim, index) in pagedItems" :key="claim.claim_id">
                                        <tr class="hover:bg-slate-50/60 transition-all">
                                            <td class="py-3.5 px-5 font-bold text-slate-900 whitespace-nowrap"
                                                x-text="claim.user_name"></td>

                                            <td class="py-3.5 px-5 font-mono text-slate-400 whitespace-nowrap"
                                                x-text="'CLM-' + claim.claim_id"></td>

                                            <td class="py-3.5 px-5 font-bold text-slate-950 truncate max-w-[150px]"
                                                x-text="claim.claim_type === 'Mileage' ? (claim.title || 'Travel Allowance Claim') : claim.merchant_name">
                                            </td>

                                            <td class="py-3.5 px-5 font-mono text-slate-500">
                                                <template
                                                    x-if="claim.claim_type === 'Mileage' || (claim.merchant_name || '').includes('Aero Art Transport')">
                                                    <div>
                                                        <span class="text-[10px] text-slate-600 block truncate max-w-[180px]"
                                                            x-text="'From: ' + (claim.start_location || '-')"></span>
                                                        <span class="text-[10px] text-slate-400 block truncate max-w-[180px] mt-0.5"
                                                            x-text="'To: ' + (claim.destination_location || '-')"></span>
                                                    </div>
                                                </template>
                                                <template
                                                    x-if="claim.claim_type !== 'Mileage' && !(claim.merchant_name || '').includes('Aero Art Transport')">
                                                    <span x-text="claim.receipt_invoice_no || 'NOT FOUND'"></span>
                                                </template>
                                            </td>

                                            <td class="py-3.5 px-5 text-slate-600 font-semibold whitespace-nowrap"
                                                x-text="claim.formatted_time"></td>

                                            <td class="py-3.5 px-5 text-right font-black text-slate-900 whitespace-nowrap"
                                                x-text="'RM ' + parseFloat(claim.amount || 0).toFixed(2)"></td>

                                            <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                                <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase tracking-wide"
                                                    :class="{
                                                        'bg-emerald-50 text-emerald-700 border border-emerald-200': claim.status === 'Approved',
                                                        'bg-amber-50 text-amber-700 border border-amber-200': claim.status === 'Pending',
                                                        'bg-blue-50 text-blue-700 border border-blue-200': claim.status === 'Pre-Approved',
                                                        'bg-rose-50 text-rose-700 border border-rose-200': claim.status === 'Rejected',
                                                        'bg-emerald-100 text-emerald-800 border border-emerald-300': claim.status === 'Reimbursed'
                                                    }" x-text="claim.status">
                                                </span>
                                            </td>

                                            <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                                <button type="button"
                                                    @click="openReviewModal(claim, claim.user_name, claim.formatted_time)"
                                                    class="px-3 py-1.5 bg-[#0f172a] hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1 mx-auto cursor-pointer uppercase tracking-wider">
                                                    <i class="fa-solid fa-magnifying-glass text-[10px]"></i> Review
                                                </button>
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- Empty State -->
                                    <template x-if="totalRecords === 0">
                                        <tr>
                                            <td colspan="8" class="py-12 text-center text-slate-400 font-semibold">
                                                <i class="fa-solid fa-folder-open block text-2xl mb-2 text-slate-300"></i>
                                                No claims found for this filter.
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="px-5 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-bold">
                            <div class="text-slate-400 font-medium">
                                Showing
                                <span class="text-slate-700"
                                    x-text="totalRecords === 0 ? 0 : ((currentPage - 1) * perPage) + 1"></span>
                                to
                                <span class="text-slate-700" x-text="Math.min(currentPage * perPage, totalRecords)"></span>
                                of
                                <span class="text-slate-700" x-text="totalRecords"></span>
                                records
                            </div>

                            <div class="flex items-center gap-1.5">
                                <button type="button" @click="if(currentPage > 1) currentPage--"
                                    :disabled="currentPage === 1"
                                    :class="currentPage === 1 ? 'text-slate-300 cursor-not-allowed bg-slate-50 border-slate-100' : 'text-slate-700 hover:border-slate-400 bg-white border-slate-200 cursor-pointer'"
                                    class="px-3 py-2 border rounded-xl transition-all flex items-center gap-1.5">
                                    <i class="fa-solid fa-chevron-left text-[10px]"></i> Previous
                                </button>

                                <template x-for="page in totalPages" :key="page">
                                    <button type="button" @click="currentPage = page"
                                        :class="currentPage === page ? 'bg-[#0f172a] text-white border-[#0f172a]' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-400 cursor-pointer'"
                                        class="w-8 h-8 border rounded-xl transition-all text-xs font-bold" x-text="page"
                                        x-show="totalPages <= 7 || page === 1 || page === totalPages || Math.abs(page - currentPage) <= 1">
                                    </button>
                                </template>

                                <button type="button" @click="if(currentPage < totalPages) currentPage++"
                                    :disabled="currentPage === totalPages"
                                    :class="currentPage === totalPages ? 'text-slate-300 cursor-not-allowed bg-slate-50 border-slate-100' : 'text-slate-700 hover:border-slate-400 bg-white border-slate-200 cursor-pointer'"
                                    class="px-3 py-2 border rounded-xl transition-all flex items-center gap-1.5">
                                    Next <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                </button>
                            </div>
                        </div>

                    </div>

                </div>
            </main>
        </div>
    </div>

    <!-- Review Modal -->
    <div x-show="isModalOpen" x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-all duration-300">
        <div class="relative bg-white rounded-3xl p-4 md:p-6 max-w-5xl w-full shadow-2xl flex flex-col md:flex-row gap-5 max-h-[90vh] overflow-hidden border border-slate-100"
            @click.away="isModalOpen = false">

            <div class="flex-1 flex flex-col overflow-y-auto space-y-4 pr-1 min-h-0">
                <div class="border-b border-slate-100 pb-3 flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400"
                            x-text="'FINANCE DESK AUDITING — SUBMISSION BY: ' + activeUser"></span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900"
                        x-text="'CLM-' + activeClaim.claim_id + ' | ' + (activeClaim.claim_type === 'Mileage' ? (activeClaim.title ? activeClaim.title : 'Mileage Request Packet') : activeClaim.merchant_name)">
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-slate-50 p-3 rounded-2xl border border-slate-100 text-xs">
                    <div>
                        <span class="block text-[9px] uppercase font-bold text-slate-400">Date & Time Submitted</span>
                        <div class="font-semibold text-slate-800 mt-0.5">
                            <i class="fa-regular fa-clock mr-1 text-slate-500"></i>
                            <span x-text="activeClaim.created_at ? new Date(activeClaim.created_at).toLocaleString('ms-MY', { dateStyle: 'medium', timeStyle: 'short' }) : 'N/A'"></span>
                        </div>
                    </div>
                    <div>
                        <span class="block text-[9px] uppercase font-bold text-slate-400"
                            x-text="activeClaim.claim_type === 'Mileage' ? 'Journey Log Date' : 'Receipt Invoice Date'"></span>
                        <div class="font-semibold text-slate-800 mt-0.5">
                            <i class="fa-regular fa-calendar-days mr-1 text-slate-500"></i>
                            <span x-text="activeClaim.transaction_date ? new Date(activeClaim.transaction_date).toLocaleDateString('ms-MY', { dateStyle: 'medium' }) : 'N/A'"></span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div class="space-y-1">
                        <span class="block font-bold text-slate-400 uppercase text-[9px]">Receipt Invoice No</span>
                        <div class="p-2.5 bg-slate-50 border border-slate-200/60 font-semibold font-mono rounded-xl text-slate-800"
                            x-text="activeClaim.claim_type === 'Mileage' ? 'NOT APPLICABLE (MILEAGE)' : (activeClaim.receipt_invoice_no || 'N/A')">
                        </div>
                    </div>
                    <div class="space-y-1">
                        <span class="block font-bold text-slate-400 uppercase text-[9px]">Expense Category</span>
                        <div class="p-2.5 bg-slate-50 border border-slate-200/60 font-bold rounded-xl text-indigo-600"
                            x-text="activeClaim.claim_type === 'Mileage' ? 'Transport / Logistics' : activeClaim.predicted_category">
                        </div>
                    </div>
                    <div class="space-y-1">
                        <span class="block font-bold text-slate-400 uppercase text-[9px]">Payment Method</span>
                        <div class="p-2.5 bg-slate-50 border border-slate-200/60 font-semibold rounded-xl text-slate-800"
                            x-text="activeClaim.claim_type === 'Mileage' ? 'Allowance Disbursement' : (activeClaim.payment_method || 'Cash')">
                        </div>
                    </div>
                    <div class="space-y-1">
                        <span class="block font-bold text-slate-400 uppercase text-[9px]">Audit Total Amount</span>
                        <div class="p-2.5 bg-slate-50 border border-slate-200/60 font-black font-mono rounded-xl text-slate-900"
                            x-text="'RM ' + parseFloat(activeClaim.amount || 0).toFixed(2)"></div>
                    </div>

                    <div class="col-span-1 sm:col-span-2 space-y-1" x-show="activeClaim.vehicle_plate_number">
                        <span class="block font-bold text-rose-700 uppercase text-[9px] tracking-wide flex items-center gap-1">
                            <i class="fa-solid fa-car-side"></i>
                            <span x-text="activeClaim.claim_type === 'Mileage' ? 'Staff Personal Vehicle Declaration' : 'Authorized Fleet Tracking Node'"></span>
                        </span>
                        <div class="p-2.5 bg-rose-50/40 border border-rose-100 text-rose-950 rounded-xl font-black font-mono flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 bg-rose-600 text-white font-mono text-[9px] font-black rounded uppercase tracking-wider">Plate Index</span>
                                <span class="text-sm tracking-widest" x-text="activeClaim.vehicle_plate_number"></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-1 sm:col-span-2 space-y-1.5">
                        <span class="block font-bold text-slate-400 uppercase text-[9px] tracking-wide">
                            <i class="fa-solid" :class="activeClaim.claim_type === 'Mileage' ? 'fa-route text-emerald-600' : 'fa-map-location-dot'"></i>
                            <span x-text="activeClaim.claim_type === 'Mileage' ? 'Authorized Travel Logistics Route' : 'Location Branch Address'"></span>
                        </span>

                        <div x-show="activeClaim.claim_type === 'Mileage'" class="space-y-2" x-cloak>
                            <div class="p-3 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                                <div class="flex items-start gap-2 text-xs">
                                    <i class="fa-solid fa-circle-dot text-emerald-500 mt-1 text-[10px]"></i>
                                    <div>
                                        <span class="text-[9px] text-slate-400 block uppercase font-black tracking-wider">Starting Point</span>
                                        <span class="text-slate-700 font-bold" x-text="activeClaim.start_location ? activeClaim.start_location : 'Unknown Address Node'"></span>
                                    </div>
                                </div>
                                <div class="w-px h-3 bg-slate-300 ml-1.5 border-dashed"></div>
                                <div class="flex items-start gap-2 text-xs">
                                    <i class="fa-solid fa-location-dot text-rose-500 mt-1 text-[10px]"></i>
                                    <div>
                                        <span class="text-[9px] text-slate-400 block uppercase font-black tracking-wider">Destination Point</span>
                                        <span class="text-slate-700 font-bold" x-text="activeClaim.destination_location ? activeClaim.destination_location : 'Unknown Destination Node'"></span>
                                    </div>
                                </div>
                            </div>

                            <p class="text-[10px] text-slate-400 italic font-sans leading-relaxed flex items-start gap-1.5 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                <i class="fa-solid fa-circle-info text-blue-500 mt-0.5 shrink-0 text-[11px]"></i>
                                <span>
                                    <strong>Audit Note:</strong> Jarak variasi dijana automatik oleh Google Distance Matrix API berdasarkan rute logistik jalan raya paling optimum. Perbezaan kecil dengan odometer mekanikal kenderaan adalah sah di bawah pematuhan had toleransi audit Aero Art Sdn Bhd.
                                </span>
                            </p>
                        </div>

                        <div x-show="activeClaim.claim_type !== 'Mileage'"
                            class="p-2.5 bg-slate-50 border border-slate-100 text-slate-700 rounded-lg leading-relaxed font-medium"
                            x-text="activeClaim.location_address || 'No location address parsed.'" x-cloak></div>
                    </div>

                    <div class="col-span-1 sm:col-span-2 space-y-1">
                        <span class="block font-bold text-slate-400 uppercase text-[9px]">Staff Justification Statement (Business Purpose)</span>
                        <div class="p-2.5 bg-slate-50 border border-slate-200/60 text-slate-700 font-medium italic rounded-xl min-h-[50px]"
                            x-text="activeClaim.business_purpose || 'No justification logged.'"></div>
                    </div>
                </div>

                <div class="space-y-2 border-t border-slate-100 pt-3">
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        <i class="fa-solid fa-list-check"></i> Itemized Audit Parameters
                    </h4>

                    <div class="space-y-1.5 max-h-[160px] overflow-y-auto">
                        <template x-if="activeClaim.claim_type === 'Mileage'">
                            <div class="overflow-hidden border border-slate-200 rounded-xl bg-white shadow-3xs">
                                <table class="w-full text-left border-collapse text-[11px]">
                                    <thead class="bg-slate-50 text-slate-400 font-bold uppercase border-b border-slate-100 text-[9px]">
                                        <tr>
                                            <th class="p-2.5">Audit Parameter Metric</th>
                                            <th class="p-2.5 text-center">Logged Metric</th>
                                            <th class="p-2.5 text-right" x-show="activeClaim.claim_type !== 'Mileage'">Computed Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                        <tr>
                                            <td class="p-2.5 text-slate-500">Calculated Journey Distance</td>
                                            <td class="p-2.5 text-center font-mono font-bold text-slate-900"
                                                x-text="parseFloat(activeClaim.mileage_km).toFixed(2) + ' KM'"></td>
                                            <td class="p-2.5 text-right font-mono" x-show="activeClaim.claim_type !== 'Mileage'">-</td>
                                        </tr>
                                        <tr>
                                            <td class="p-2.5 text-slate-500"
                                                x-text="'Applied Transport Rate (' + (activeClaim.vehicle_type ? activeClaim.vehicle_type : 'Car') + ')'">
                                            </td>
                                            <td class="p-2.5 text-center font-mono text-slate-600"
                                                x-text="activeClaim.vehicle_type === 'Motorcycle' ? 'RM 0.30 / KM' : 'RM 0.60 / KM'">
                                            </td>
                                            <td class="p-2.5 text-right font-mono" x-show="activeClaim.claim_type !== 'Mileage'">-</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>

                        <template x-if="activeClaim.claim_type !== 'Mileage'">
                            <div class="space-y-1.5">
                                <template x-for="(item, idx) in activeClaim.items" :key="idx">
                                    <div class="flex items-center justify-between p-2 bg-slate-50 rounded-xl border border-slate-100 text-xs">
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

                <!-- Modal Actions -->
                <div class="border-t border-slate-100 pt-3 mt-auto flex flex-col gap-3 bg-white sticky bottom-0">
                    <template x-if="activeClaim.status === 'Pending'">
                        <div class="grid grid-cols-2 gap-3">
                            <form :action="'/finance/claims/' + activeClaim.claim_id + '/status'" method="POST" class="w-full">
                                @csrf 
                                <input type="hidden" name="status" value="Rejected">
                                <button type="submit"
                                    class="w-full py-2.5 bg-rose-500 hover:bg-rose-600 text-white font-black text-xs uppercase tracking-wider rounded-xl cursor-pointer transition-all active:scale-[0.98]">
                                    Reject
                                </button>
                            </form>
                            <form :action="'/finance/claims/' + activeClaim.claim_id + '/status'" method="POST" class="w-full">
                                @csrf 
                                <input type="hidden" name="status" value="Approved">
                                <button type="submit"
                                    class="w-full py-2.5 bg-[#00e1b1] hover:bg-[#00cda1] text-slate-950 font-black text-xs uppercase tracking-wider rounded-xl cursor-pointer transition-all active:scale-[0.98]">
                                    Pre-Approve
                                </button>
                            </form>
                        </div>
                    </template>
                    <button type="button" @click="isModalOpen = false"
                        class="text-xs text-slate-400 font-bold hover:underline text-center cursor-pointer">
                        Close Screen
                    </button>
                </div>
            </div>

            <!-- Attached Image/Asset Panel -->
            <div class="w-full md:w-[380px] lg:w-[420px] bg-slate-50 rounded-2xl border border-slate-100 flex flex-col p-2 shrink-0 max-h-[40vh] md:max-h-full"
                x-show="activeClaim.receipt_image_path">
                <span class="text-[9px] font-bold uppercase text-slate-400 tracking-wider px-2 mb-1.5">
                    <i class="fa-solid fa-image mr-1"></i>
                    <span x-text="activeClaim.claim_type === 'Mileage' ? 'Attached Proof of Travel Asset' : 'Attached Audit Receipt Resource'"></span>
                </span>
                <div class="flex-1 bg-slate-900/5 rounded-xl overflow-hidden relative flex items-center justify-center min-h-[220px] md:min-h-0">
                    <img x-show="activeClaim && activeClaim.receipt_image_path"
                        :src="activeClaim.receipt_image_path ? '/storage/' + activeClaim.receipt_image_path : ''"
                        @click="modalPreviewSrc = '/storage/' + activeClaim.receipt_image_path; isHistoryModalOpen = true"
                        class="max-w-full max-h-full object-contain rounded-lg shadow-xs cursor-zoom-in">

                    <button type="button" x-show="activeClaim && activeClaim.receipt_image_path"
                        @click="modalPreviewSrc = '/storage/' + activeClaim.receipt_image_path; isHistoryModalOpen = true"
                        class="absolute inset-0 bg-slate-900/40 opacity-0 hover:opacity-100 flex items-center justify-center transition-all duration-200 text-white font-bold text-xs gap-1.5 backdrop-blur-xs cursor-zoom-in">
                        <i class="fa-solid fa-magnifying-glass-plus"></i> View Raw Asset Image
                    </button>

                    <div x-show="!activeClaim || !activeClaim.receipt_image_path"
                        class="text-slate-400 text-xs font-semibold italic p-4">
                        <i class="fa-solid fa-ban block text-center text-lg mb-1 text-slate-300"></i> No Image Asset Attached
                    </div>
                </div>
                <div class="pt-2" x-show="activeClaim.claim_type === 'Mileage'">
                    <a :href="'https://www.google.com/maps/dir/?api=1&origin=' + encodeURIComponent(activeClaim.start_location || '') + '&destination=' + encodeURIComponent(activeClaim.destination_location || '') + '&travelmode=driving'"
                        target="_blank"
                        class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-center flex items-center justify-center gap-1.5 transition-all text-[11px] uppercase tracking-wider">
                        <i class="fa-solid fa-map-location-dot"></i> Cross-Verify Route External Map
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- Lightbox Fullscreen Modal -->
    <div x-show="isHistoryModalOpen" x-cloak
        class="fixed inset-0 z-[250] flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs transition-all duration-300">
        <div class="relative bg-white rounded-3xl p-3 max-w-2xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
            @click.away="isHistoryModalOpen = false" 
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-95" 
            x-transition:enter-end="opacity-100 scale-100">

            <div class="flex items-center justify-between px-4 py-2 border-b border-slate-100">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">
                    <i class="fa-solid fa-receipt mr-1 text-blue-600"></i> Full View Review Asset
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
</body>

</html>