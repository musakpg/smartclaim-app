<!DOCTYPE html>
<html lang="en" x-data="financeWorkspace()">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Claims Auditing Workspace</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">
    <meta name="google-maps-api-key" content="{{ config('services.google.maps_api_key') }}">
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



            <!-- Main Content Area -->
            <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full pb-24 lg:pb-8 overflow-y-auto">
                <div class="space-y-6">

                    <!-- Workspace Title Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-200 pb-5">
                                            <div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight"
                                x-text="'Auditing Workspace — ' + statusTab">Claims Verification</h1>
                            <p class="text-xs md:text-sm text-slate-500">Cross-examine AI-extracted metadata fields against raw image invoices below.</p>
                    </div>
                    <div class="hidden lg:flex items-center gap-3">
                        <x-system-clock />
                    </div>
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
                        allClaims: [],
                        currentPage: 1,
                        perPage: 5,
                        isLoading: false,
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
                        resetPage() { this.currentPage = 1; },
                        async fetchClaims() {
                            this.isLoading = true;
                            try {
                                const response = await fetch(window.location.href, {
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                });
                                this.allClaims = await response.json();
                            } catch (error) {
                                console.error('Failed to fetch claims', error);
                            } finally {
                                this.isLoading = false;
                            }
                        }
                    }" x-init="fetchClaims(); $watch('activeTab', () => resetPage()); $watch('statusTab', () => resetPage())">

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
                                                x-text="'RM ' + (parseFloat(claim.amount) > 0 ? parseFloat(claim.amount) : parseFloat(claim.calculated_amount || 0)).toFixed(2)"></td>

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
                                    <template x-if="isLoading">
                                        <tr>
                                            <td colspan="8" class="py-12 text-center text-slate-400 font-medium">
                                                <div class="flex flex-col items-center justify-center space-y-2">
                                                    <i class="fa-solid fa-spinner fa-spin text-4xl text-slate-300 mb-2"></i>
                                                    <span class="text-sm text-slate-500 font-bold">Loading Audit Records...</span>
                                                    <span class="text-xs text-slate-400">Fetching forensic ledger from server.</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- Empty State -->
                                    <template x-if="totalRecords === 0 && !isLoading">
                                        <tr>
                                            <td colspan="8" class="py-12 text-center text-slate-400 font-medium">
                                                <div class="flex flex-col items-center justify-center space-y-2">
                                                    <i class="fa-solid fa-folder-open text-4xl text-slate-200 mb-2"></i>
                                                    <span class="text-sm text-slate-500 font-bold">No claims found</span>
                                                    <span class="text-xs text-slate-400">Try adjusting your filters or search query.</span>
                                                </div>
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
            @click.away="if (!isMapModalOpen && !isHistoryModalOpen) isModalOpen = false">

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
                            x-text="'RM ' + (parseFloat(activeClaim.amount) > 0 ? parseFloat(activeClaim.amount) : parseFloat(activeClaim.calculated_amount || 0)).toFixed(2)"></div>
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

                            <!-- GOOGLE MAPS INTEGRATION -->
                            <div class="mt-3 space-y-3">
                                <!-- 3-Metric Banner -->
                                <div class="grid grid-cols-3 gap-2 text-center" x-show="googleDistanceKm !== null">
                                    <div class="p-2 bg-white rounded-lg border border-slate-200 shadow-3xs">
                                        <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1">Claimed Distance</span>
                                        <span class="text-sm font-black text-slate-700" x-text="parseFloat(activeClaim.mileage_km).toFixed(1) + ' KM'"></span>
                                    </div>
                                    <div class="p-2 bg-white rounded-lg border border-slate-200 shadow-3xs">
                                        <span class="block text-[9px] font-bold text-blue-400 uppercase tracking-wider mb-1">Google Route</span>
                                        <span class="text-sm font-black text-blue-700" x-text="googleDistanceKm ? googleDistanceKm.toFixed(1) + ' KM' : '---'"></span>
                                    </div>
                                    <div class="p-2 bg-white rounded-lg border shadow-3xs" 
                                        :class="googleVariancePct > 15 ? 'border-rose-200 bg-rose-50' : 'border-emerald-200 bg-emerald-50'">
                                        <span class="block text-[9px] font-bold uppercase tracking-wider mb-1"
                                            :class="googleVariancePct > 15 ? 'text-rose-500' : 'text-emerald-600'">Variance Tag</span>
                                        <span class="text-[11px] font-black leading-tight block mt-0.5"
                                            :class="googleVariancePct > 15 ? 'text-rose-700' : 'text-emerald-700'"
                                            x-text="googleVariancePct > 15 ? '+' + googleVariancePct.toFixed(1) + '% Discrepancy' : 'Within Range'"></span>
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

                <!-- XAI Risk & Integrity Audit Box -->
                <div x-show="activeClaim.risk_score !== null" x-cloak
                    class="p-3.5 bg-slate-900 text-white rounded-2xl space-y-3 text-xs shadow-md border border-slate-800">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-300 flex items-center gap-1.5">
                            <i class="fa-solid fa-fingerprint text-blue-400 text-sm"></i> XAI Risk & Integrity Audit
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-black uppercase tracking-wider"
                            :class="activeClaim.risk_score === 0 ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : (activeClaim.risk_score >= 50 ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30')"
                            x-text="'RISK: ' + (activeClaim.risk_score || 0) + '%'">
                        </span>
                    </div>

                    <div class="space-y-2">
                        <template x-if="!activeClaim.fraud_flags || activeClaim.fraud_flags.length === 0">
                            <div class="flex items-center gap-2 p-2 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                                <i class="fa-solid fa-shield-check text-emerald-400 text-base"></i>
                                <span class="text-[11px] font-bold text-emerald-400 uppercase tracking-wide">Zero Anomaly Detected</span>
                            </div>
                        </template>

                        <template x-if="activeClaim.fraud_flags && activeClaim.fraud_flags.length > 0">
                            <div class="space-y-2">
                                <template x-for="(flag, fidx) in activeClaim.fraud_flags" :key="fidx">
                                    <div class="p-2.5 rounded-xl border flex flex-col gap-1.5"
                                        :class="flag.severity === 'critical' ? 'bg-rose-500/10 border-rose-500/20' : (flag.severity === 'warning' ? 'bg-amber-500/10 border-amber-500/20' : 'bg-blue-500/10 border-blue-500/20')">
                                        <div class="flex items-center gap-2">
                                            <span class="px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-wider"
                                                :class="flag.severity === 'critical' ? 'bg-rose-500 text-white' : (flag.severity === 'warning' ? 'bg-amber-500 text-slate-900' : 'bg-blue-500 text-white')"
                                                x-text="flag.flag_type"></span>
                                            <span class="font-bold text-[11px]"
                                                :class="flag.severity === 'critical' ? 'text-rose-400' : (flag.severity === 'warning' ? 'text-amber-400' : 'text-blue-400')"
                                                x-text="flag.title"></span>
                                        </div>
                                        <p class="text-[10px] text-slate-300 font-medium leading-relaxed" x-text="flag.description"></p>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <template x-if="activeClaim.exif_date_taken">
                        <div class="pt-2 text-[10px] text-slate-400 font-mono flex items-center gap-1.5 border-t border-slate-800">
                            <i class="fa-solid fa-camera text-slate-500"></i>
                            <span>EXIF Capture Timestamp: <strong class="text-slate-200" x-text="activeClaim.exif_date_taken"></strong></span>
                        </div>
                    </template>
                </div>

                <x-audit-timeline />
                <!-- Modal Actions -->
                <div class="border-t border-slate-100 pt-3 mt-auto flex flex-col gap-3 bg-white sticky bottom-0">
                    <template x-if="activeClaim.status === 'Pending'">
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" @click="isRevisionModalOpen = true" class="w-full py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-black text-xs uppercase tracking-wider rounded-xl cursor-pointer transition-all active:scale-[0.98] flex items-center justify-center gap-1.5 shadow-sm">
                                <i class="fa-solid fa-rotate-left text-xs"></i> Request Revision
                            </button>
                            <form :action="'/finance/claims/' + activeClaim.claim_id + '/status'" method="POST" class="w-full" x-data="{ loading: false }" @submit="loading = true">
                                @csrf 
                                <input type="hidden" name="status" value="Approved">
                                <button type="submit" :disabled="loading"
                                    class="w-full py-2.5 bg-[#00e1b1] hover:bg-[#00cda1] text-slate-950 font-black text-xs uppercase tracking-wider rounded-xl cursor-pointer transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-1.5 shadow-sm">
                                    <template x-if="loading"><i class="fa-solid fa-spinner fa-spin"></i></template>
                                    <span x-text="loading ? '...' : 'Pre-Approve'"></span>
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
                        :src="activeClaim.receipt_image_path ? '/files/' + activeClaim.receipt_image_path : ''"
                        @click="modalPreviewSrc = '/files/' + activeClaim.receipt_image_path; isHistoryModalOpen = true"
                        class="max-w-full max-h-full object-contain rounded-lg shadow-xs cursor-zoom-in">

                    <button type="button" x-show="activeClaim && activeClaim.receipt_image_path"
                        @click="modalPreviewSrc = '/files/' + activeClaim.receipt_image_path; isHistoryModalOpen = true"
                        class="absolute inset-0 bg-slate-900/40 opacity-0 hover:opacity-100 flex items-center justify-center transition-all duration-200 text-white font-bold text-xs gap-1.5 backdrop-blur-xs cursor-zoom-in">
                        <i class="fa-solid fa-magnifying-glass-plus"></i> View Raw Asset Image
                    </button>

                    <div x-show="!activeClaim || !activeClaim.receipt_image_path"
                        class="text-slate-400 text-xs font-semibold italic p-4">
                        <i class="fa-solid fa-ban block text-center text-lg mb-1 text-slate-300"></i> No Image Asset Attached
                    </div>
                </div>
                <div class="pt-2" x-show="activeClaim.claim_type === 'Mileage'">
                    <button type="button" @click="isMapModalOpen = true"
                        class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-center flex items-center justify-center gap-1.5 transition-all text-[11px] uppercase tracking-wider cursor-pointer">
                        <i class="fa-solid fa-map-location-dot"></i> Cross-Verify Route
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- Route Preview Lightbox Modal -->
    <div x-show="isMapModalOpen" x-cloak
        class="fixed inset-0 z-[250] flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs transition-all duration-300">
        <div class="relative bg-white rounded-3xl p-3 max-w-4xl w-full shadow-2xl overflow-hidden flex flex-col h-[80vh]"
            @click.away="isMapModalOpen = false" x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

            <div class="flex items-center justify-between px-4 py-2 border-b border-slate-100">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">
                    <i class="fa-solid fa-map-location-dot mr-1 text-emerald-600"></i> Interactive Route Verification
                </span>
                <button type="button" @click="isMapModalOpen = false"
                    class="text-slate-400 hover:text-rose-600 transition-all text-lg cursor-pointer p-1">
                    <i class="fa-solid fa-circle-xmark"></i>
                </button>
            </div>

            <div class="p-0 bg-slate-50 rounded-2xl overflow-hidden flex-1 flex justify-center items-center min-h-0 mt-2">
                <template x-if="isMapModalOpen && (activeClaim.start_location || activeClaim.starting_point || activeClaim.origin || activeClaim.origin_address) && (activeClaim.destination_location || activeClaim.destination_point || activeClaim.destination || activeClaim.end_location || activeClaim.destination_address || activeClaim.ending_point)">
                    <iframe
                        class="w-full h-full border-0"
                        :src="'https://www.google.com/maps/embed/v1/directions?key={{ config('services.google.maps_api_key') }}&origin=' + encodeURIComponent(activeClaim.start_location || activeClaim.starting_point || activeClaim.origin || activeClaim.origin_address) + '&destination=' + encodeURIComponent(activeClaim.destination_location || activeClaim.destination_point || activeClaim.destination || activeClaim.end_location || activeClaim.destination_address || activeClaim.ending_point) + '&mode=driving'"
                        allowfullscreen>
                    </iframe>
                </template>
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
    <!-- Request Revision Modal -->
    <div x-show="isRevisionModalOpen" x-cloak
        class="fixed inset-0 z-[300] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm transition-all duration-300">
        <div class="relative bg-white rounded-3xl p-5 max-w-md w-full shadow-2xl overflow-hidden flex flex-col"
            @click.away="isRevisionModalOpen = false" 
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4" 
            x-transition:enter-end="opacity-100 scale-100 translate-y-0">

            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <span class="text-sm font-black text-slate-800 uppercase tracking-wide flex items-center gap-2">
                    <i class="fa-solid fa-rotate-left text-amber-500"></i> Request Revision
                </span>
                <button type="button" @click="isRevisionModalOpen = false"
                    class="text-slate-400 hover:text-rose-600 transition-all text-lg cursor-pointer p-1">
                    <i class="fa-solid fa-circle-xmark"></i>
                </button>
            </div>

            <form :action="'/finance/claims/' + activeClaim.claim_id + '/status'" method="POST" class="space-y-4"
                x-data="{ loading: false, selectedReason: '', requiresRemarks: false, remarks: '' }"
                @submit="loading = true">
                @csrf
                <input type="hidden" name="status" value="REVISION_REQUIRED">
                
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider">
                        Audit Clarification Reason <span class="text-amber-500">*</span>
                    </label>
                    <select name="revision_reason" x-model="selectedReason" required
                        @change="const opt = $event.target.selectedOptions[0]; requiresRemarks = opt.dataset.requiresRemarks === '1' || opt.value.includes('Other');"
                        class="w-full text-xs bg-slate-50 border border-slate-200 text-slate-700 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-amber-500/50 outline-none transition-all font-medium">
                        <option value="">-- Select Audit Exception Code --</option>
                        @foreach($revisionReasons ?? [] as $reason)
                            <option value="{{ $reason->title }}" data-requires-remarks="{{ $reason->requires_remarks ? '1' : '0' }}">
                                {{ $reason->title }} {{ $reason->requires_remarks ? '(Remarks Required)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider">
                        Specific Feedback / Instructions <span x-show="requiresRemarks" class="text-amber-500">*</span>
                    </label>
                    <textarea name="remarks" x-model="remarks" :required="requiresRemarks" rows="3"
                        :placeholder="requiresRemarks ? 'Detail required corrections or missing documents (Mandatory)...' : 'Optional instructions for employee...'"
                        class="w-full text-xs bg-slate-50 border border-slate-200 text-slate-700 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-amber-500/50 outline-none transition-all font-medium"></textarea>
                    <p class="text-[10px] text-slate-400">
                        <span x-show="requiresRemarks" class="text-amber-600 font-semibold">Remarks are required for this audit code.</span>
                        <span x-show="!requiresRemarks">Feedback will be recorded and notified to employee.</span>
                    </p>
                </div>

                <div class="pt-2">
                    <button type="submit" :disabled="loading || !selectedReason || (requiresRemarks && !remarks.trim())"
                        class="w-full py-3 bg-amber-500 hover:bg-amber-600 text-white font-black text-xs uppercase tracking-wider rounded-xl cursor-pointer transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2 shadow-lg shadow-amber-500/20">
                        <template x-if="loading"><i class="fa-solid fa-spinner fa-spin"></i></template>
                        <span x-text="loading ? 'Sending Request...' : 'Send Revision Request'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Claim Modal -->
    <div x-show="isRejectModalOpen" x-cloak
        class="fixed inset-0 z-[300] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm transition-all duration-300">
        <div class="relative bg-white rounded-3xl p-5 max-w-md w-full shadow-2xl overflow-hidden flex flex-col"
            @click.away="isRejectModalOpen = false" 
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4" 
            x-transition:enter-end="opacity-100 scale-100 translate-y-0">

            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <span class="text-sm font-black text-slate-800 uppercase tracking-wide flex items-center gap-2">
                    <i class="fa-solid fa-ban text-rose-500"></i> Reject Claim Voucher
                </span>
                <button type="button" @click="isRejectModalOpen = false"
                    class="text-slate-400 hover:text-rose-600 transition-all text-lg cursor-pointer p-1">
                    <i class="fa-solid fa-circle-xmark"></i>
                </button>
            </div>

            <form :action="'/finance/claims/' + activeClaim.claim_id + '/status'" method="POST" class="space-y-4"
                x-data="{ loading: false, selectedReason: '', requiresRemarks: false, remarks: '' }"
                @submit="loading = true">
                @csrf
                <input type="hidden" name="status" value="Rejected">
                
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider">
                        Audit Rejection Reason <span class="text-rose-500">*</span>
                    </label>
                    <select name="rejection_reason" x-model="selectedReason" required
                        @change="const opt = $event.target.selectedOptions[0]; requiresRemarks = opt.dataset.requiresRemarks === '1' || opt.value.includes('Other');"
                        class="w-full text-xs bg-slate-50 border border-slate-200 text-slate-700 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-rose-500/50 outline-none transition-all font-medium">
                        <option value="">-- Select Audit Exception Code --</option>
                        @foreach($rejectionReasons ?? [] as $reason)
                            <option value="{{ $reason->title }}" data-requires-remarks="{{ $reason->requires_remarks ? '1' : '0' }}">
                                {{ $reason->title }} {{ $reason->requires_remarks ? '(Remarks Required)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider">
                        Auditor Notes / Reason Explanation <span x-show="requiresRemarks" class="text-rose-500">*</span>
                    </label>
                    <textarea name="remarks" x-model="remarks" :required="requiresRemarks" rows="3"
                        :placeholder="requiresRemarks ? 'Explicit detailed justification is required for this exception code...' : 'Optional clarifying notes for audit log...'"
                        class="w-full text-xs bg-slate-50 border border-slate-200 text-slate-700 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-rose-500/50 outline-none transition-all font-medium"></textarea>
                    <p class="text-[10px] text-slate-400">
                        <span x-show="requiresRemarks" class="text-rose-600 font-semibold">Remarks are required for this audit code.</span>
                        <span x-show="!requiresRemarks">This explanation will be permanently recorded in the Audit Log.</span>
                    </p>
                </div>

                <div class="pt-2">
                    <button type="submit" :disabled="loading || !selectedReason || (requiresRemarks && !remarks.trim())"
                        class="w-full py-3 bg-rose-600 hover:bg-rose-700 text-white font-black text-xs uppercase tracking-wider rounded-xl cursor-pointer transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2 shadow-lg shadow-rose-500/20">
                        <template x-if="loading"><i class="fa-solid fa-spinner fa-spin"></i></template>
                        <span x-text="loading ? 'Rejecting Claim...' : 'Confirm Claim Rejection'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <x-route-modal />

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('financeWorkspace', () => ({
                isMobileSidebarOpen: false, 
                activeTab: 'Receipt', 
                statusTab: (new URLSearchParams(window.location.search)).get('status') || 'Pending', 
                isModalOpen: false, 
                isMapModalOpen: false,
                activeClaim: {}, 
                activeUser: '', 
                formattedSubmissionTime: '',
                isHistoryModalOpen: false,
                isRevisionModalOpen: false,
                isRejectModalOpen: false,
                modalPreviewSrc: '',
                googleDistanceKm: null,
                googleVariancePct: null,
                
                init() {
                    this.loadGoogleMapsScript();
                },
                
                loadGoogleMapsScript() {
                    if (document.getElementById('google-maps-script')) return;
                    const apiKey = document.querySelector('meta[name="google-maps-api-key"]')?.getAttribute('content');
                    if (!apiKey) return;
                    const script = document.createElement('script');
                    script.id = 'google-maps-script';
                    script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places,geometry,marker&loading=async`;
                    script.async = true;
                    script.defer = true;
                    document.head.appendChild(script);
                },
                
                openReviewModal(claim, username, rawFormattedTime) {
                    this.activeClaim = claim; 
                    this.activeUser = username; 
                    this.formattedSubmissionTime = rawFormattedTime; 
                    this.isModalOpen = true;
                    this.googleDistanceKm = null;
                    this.googleVariancePct = null;
                    
                    if (claim.claim_type === 'Mileage') {
                        setTimeout(() => this.renderGoogleRoute(), 350);
                    }
                },
                
                renderGoogleRoute() {
                    const startLocation = this.activeClaim?.starting_point 
                        || this.activeClaim?.origin 
                        || this.activeClaim?.start_location 
                        || this.activeClaim?.origin_address;

                    const endLocation = this.activeClaim?.destination_point 
                        || this.activeClaim?.destination 
                        || this.activeClaim?.end_location 
                        || this.activeClaim?.destination_address
                        || this.activeClaim?.ending_point
                        || this.activeClaim?.destination_location;

                    if (!startLocation || !endLocation) {
                        return;
                    }

                    const requestBody = {
                        origin: { address: startLocation },
                        destination: { address: endLocation },
                        travelMode: 'DRIVE'
                    };

                    fetch('https://routes.googleapis.com/directions/v2:computeRoutes', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Goog-Api-Key': document.querySelector('meta[name="google-maps-api-key"]')?.getAttribute('content'),
                            'X-Goog-FieldMask': 'routes.distanceMeters'
                        },
                        body: JSON.stringify(requestBody)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error('[Mileage Map] Routes API Error:', data.error.message);
                            return;
                        }

                        if (data.routes && data.routes.length > 0) {
                            const route = data.routes[0];
                            this.googleDistanceKm = route.distanceMeters / 1000;
                            const claimed = parseFloat(this.activeClaim.mileage_km || 0);
                            this.googleVariancePct = ((claimed - this.googleDistanceKm) / this.googleDistanceKm) * 100;
                        }
                    })
                    .catch(err => {
                        console.error('[Mileage Map] Fetch to Routes API failed:', err);
                    });
                }
            }));
        });
    </script>
</body>

</html>
