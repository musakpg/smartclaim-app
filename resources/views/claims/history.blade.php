<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Claim History</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" x-data="historyManager()" x-init="init()" :class="isModalOpen || isMobileSidebarOpen ? 'overflow-hidden' : ''">

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
                            <span x-text="allClaims.length"></span> Logs
                        </span>
                    </h1>
                    <p class="text-xs md:text-sm text-slate-500">Complete records of claims generated dynamically via
                        local extraction engines.</p>
                </div>

                <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-xs space-y-4">
                    <div x-show="isLoading" class="flex flex-col items-center justify-center py-12">
                        <i class="fa-solid fa-circle-notch fa-spin text-slate-300 text-3xl mb-3"></i>
                        <p class="text-sm font-medium text-slate-500">Loading history records...</p>
                    </div>
                    <div x-show="!isLoading" style="display: none;" class="divide-y divide-slate-100">
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
                                                :class="claim.status === 'Approved' || claim.status === 'Reimbursed' || claim.status === 'Disbursed' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 
                                                        claim.status === 'Pending Manager' || claim.status === 'Pending Finance' || claim.status === 'Pending' ? 'bg-amber-50 text-amber-700 border-amber-100' : 
                                                        'bg-slate-50 text-slate-700 border-slate-100'"
                                                x-text="claim.status"></span>
                                            
                                            <template x-if="claim.sla_status">
                                                <span class="px-2 py-0.5 rounded-full font-bold text-[9px] tracking-wide border flex items-center gap-1"
                                                    :class="claim.sla_status === 'breached' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                                            (claim.sla_status === 'resolved' ? 'bg-slate-50 text-slate-500 border-slate-200' : 'bg-blue-50 text-blue-700 border-blue-200')">
                                                    
                                                    <template x-if="claim.sla_status !== 'resolved' && claim.sla_status !== 'breached'">
                                                        <span class="relative flex h-1.5 w-1.5 shrink-0">
                                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-blue-500"></span>
                                                        </span>
                                                    </template>
                                                    <i x-show="claim.sla_status === 'breached'" class="fa-solid fa-triangle-exclamation text-[9px]"></i>
                                                    <i x-show="claim.sla_status === 'resolved'" class="fa-solid fa-check text-[9px]"></i>
                                                    
                                                    <span x-text="
                                                        claim.sla_status === 'breached' ? 'Delayed - Expedited Review Active' :
                                                        (claim.status === 'Pending Manager' || claim.status === 'Pending' ? 'Est. Pre-Approval: ' + claim.time_remaining_human :
                                                        (claim.status === 'Approved' || claim.status === 'Pending Finance' ? 'Est. Payout: by ' + claim.estimated_completion_at + ' (Finance Batch)' :
                                                        claim.time_remaining_human))
                                                    "></span>
                                                </span>
                                            </template>
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

                                    <!-- Staff Lifecycle Actions: Edit & Resubmit for REVISION_REQUIRED -->
                                    <template x-if="claim.status === 'REVISION_REQUIRED'">
                                        <a :href="'/claims/' + claim.claim_id + '/edit'"
                                            class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 px-3 rounded-xl text-xs tracking-wide transition shadow-3xs">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit & Resubmit
                                        </a>
                                    </template>

                                    <!-- Staff Lifecycle Actions: Withdraw Claim for Pending & REVISION_REQUIRED -->
                                    <template x-if="['Pending', 'Submitted', 'REVISION_REQUIRED'].includes(claim.status)">
                                        <form :action="'/claims/' + claim.claim_id + '/withdraw'" method="POST"
                                            @submit.prevent="if(confirm('Are you sure you want to withdraw and cancel this claim voucher? This action cannot be undone.')) $el.submit()">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center gap-1 bg-slate-100 hover:bg-rose-50 text-slate-500 hover:text-rose-700 font-bold py-2 px-2.5 rounded-xl text-xs tracking-wide transition shadow-3xs cursor-pointer border border-transparent hover:border-rose-200"
                                                title="Withdraw / Cancel Claim">
                                                <i class="fa-solid fa-trash-can text-rose-500"></i>
                                            </button>
                                        </form>
                                    </template>

                                    <!-- Butang Muat Turun PDF Baris Rekod -->
                                    <a :href="'/claims/' + claim.claim_id + '/voucher-pdf'" target="_blank"
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
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @keydown.escape.window="isModalOpen = false">
        <div class="bg-white rounded-3xl max-w-5xl w-full max-h-[90vh] overflow-y-auto p-6 md:p-8 shadow-2xl relative"
            @click.away="isModalOpen = false">

            <!-- Close Button Top Right -->
            <button type="button" @click="isModalOpen = false"
                class="absolute top-5 right-5 md:top-6 md:right-6 text-slate-400 hover:text-slate-600 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition cursor-pointer z-20">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <!-- Modal Header -->
            <div class="border-b border-slate-100 pb-5 space-y-4">
                <div class="flex items-start justify-between pr-10">
                    <div class="space-y-0.5">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400"
                            x-text="'CLAIM ID: CLM-' + activeClaim.claim_id"></span>
                        <h3 class="text-lg md:text-xl font-bold text-slate-900 tracking-tight"
                            x-text="activeClaim.claim_type === 'Mileage' ? (activeClaim.title ? activeClaim.title : 'Mileage Allowance Request') : activeClaim.merchant_name">
                        </h3>
                    </div>
                    <span class="px-2.5 py-1 rounded-full font-bold text-[10px] uppercase tracking-wide border shrink-0"
                        :class="activeClaim.status === 'Approved' || activeClaim.status === 'Reimbursed' || activeClaim.status === 'Disbursed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 
                                ['Pending', 'Pending Manager', 'Pending Finance', 'Pre-Approved', 'REVISION_REQUIRED'].includes(activeClaim.status) ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                        x-text="activeClaim.status === 'REVISION_REQUIRED' ? 'REVISION REQUESTED' : activeClaim.status"></span>
                </div>

                <!-- Revision Feedback Alert -->
                <template x-if="activeClaim.status === 'REVISION_REQUIRED' && activeClaim.audit_logs && activeClaim.audit_logs.length > 0">
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 flex flex-col gap-2 shadow-sm relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-2 opacity-10">
                            <i class="fa-solid fa-triangle-exclamation text-4xl text-amber-900"></i>
                        </div>
                        <div class="flex items-center gap-2 text-amber-700 font-bold text-[11px] uppercase tracking-wider relative z-10">
                            <i class="fa-solid fa-file-pen"></i> Action Required: Clarification / Revision
                        </div>
                        <div class="text-xs text-amber-900 leading-relaxed font-medium relative z-10" x-text="activeClaim.audit_logs.filter(l => l.action === 'CLAIM_REVISION_REQUIRED').pop()?.new_values?.remarks || 'The reviewer has requested changes to your claim.'"></div>
                    </div>
                </template>

                <!-- Clean 4-step governance stepper cleanly nested inside modal header -->
                <div class="pt-2">
                    <div class="flex items-center justify-between relative px-2 sm:px-6">
                        <!-- Progress Bar Track -->
                        <div class="absolute left-6 right-6 top-1/2 -translate-y-1/2 h-1 bg-slate-100 rounded-full z-0"></div>
                        <!-- Progress Bar Fill -->
                        <div class="absolute left-6 top-1/2 -translate-y-1/2 h-1 rounded-full z-0 transition-all duration-500"
                             :style="`width: ${
                                ['Pending', 'Pending Manager'].includes(activeClaim.status) ? '15%' :
                                ['Pre-Approved', 'Pending Finance', 'REVISION_REQUIRED'].includes(activeClaim.status) ? '50%' :
                                ['Approved'].includes(activeClaim.status) ? '85%' :
                                ['Reimbursed', 'Disbursed'].includes(activeClaim.status) ? '100%' :
                                (activeClaim.status === 'Rejected' ? '100%' : '0%')
                             }; max-width: calc(100% - 3rem);`"
                             :class="activeClaim.status === 'Rejected' ? 'bg-rose-500' : (activeClaim.status === 'REVISION_REQUIRED' ? 'bg-amber-500' : 'bg-emerald-500')"></div>
                        
                        <!-- Step 1: Submitted (Staff) -->
                        <div class="relative z-10 flex flex-col items-center gap-1.5 bg-white px-2">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold shadow-sm transition-colors"
                                 :class="activeClaim.status === 'Rejected' ? 'bg-rose-500 text-white' : 'bg-emerald-500 text-white'">
                                <i class="fa-solid fa-file-invoice"></i>
                            </div>
                            <span class="text-[9px] font-bold uppercase tracking-wider text-center"
                                 :class="activeClaim.status === 'Rejected' ? 'text-rose-600' : 'text-emerald-600'">Submitted<br class="hidden sm:inline"/><span class="text-[8px] text-slate-400 font-normal"> (Staff)</span></span>
                        </div>

                        <!-- Step 2: Finance Audit (Pre-Approval) -->
                        <div class="relative z-10 flex flex-col items-center gap-1.5 bg-white px-2">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold shadow-sm transition-colors"
                                 :class="['Pre-Approved', 'Approved', 'Pending Finance', 'Reimbursed', 'Disbursed', 'REVISION_REQUIRED'].includes(activeClaim.status) ? 'bg-emerald-500 text-white' : (activeClaim.status === 'Rejected' ? 'bg-rose-500 text-white' : 'bg-slate-200 text-slate-400')">
                                <i class="fa-solid fa-building-columns"></i>
                            </div>
                            <span class="text-[9px] font-bold uppercase tracking-wider text-center"
                                 :class="['Pre-Approved', 'Approved', 'Pending Finance', 'Reimbursed', 'Disbursed', 'REVISION_REQUIRED'].includes(activeClaim.status) ? 'text-emerald-600' : (activeClaim.status === 'Rejected' ? 'text-rose-600' : 'text-slate-400')">Finance Audit<br class="hidden sm:inline"/><span class="text-[8px] text-slate-400 font-normal"> (Pre-Approval)</span></span>
                        </div>

                        <!-- Step 3: Manager Approval (Final Sign-off) -->
                        <div class="relative z-10 flex flex-col items-center gap-1.5 bg-white px-2">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold shadow-sm transition-colors"
                                 :class="['Approved', 'Reimbursed', 'Disbursed'].includes(activeClaim.status) ? 'bg-emerald-500 text-white' : (activeClaim.status === 'Rejected' ? 'bg-rose-500 text-white' : (activeClaim.status === 'REVISION_REQUIRED' ? 'bg-amber-500 text-white' : 'bg-slate-200 text-slate-400'))">
                                <i class="fa-solid fa-user-tie"></i>
                            </div>
                            <span class="text-[9px] font-bold uppercase tracking-wider text-center"
                                 :class="['Approved', 'Reimbursed', 'Disbursed'].includes(activeClaim.status) ? 'text-emerald-600' : (activeClaim.status === 'Rejected' ? 'text-rose-600' : (activeClaim.status === 'REVISION_REQUIRED' ? 'text-amber-600' : 'text-slate-400'))">Manager Approval<br class="hidden sm:inline"/><span class="text-[8px] text-slate-400 font-normal"> (Final Sign-off)</span></span>
                        </div>

                        <!-- Step 4: Disbursed (Completed) -->
                        <div class="relative z-10 flex flex-col items-center gap-1.5 bg-white px-2">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold shadow-sm transition-colors"
                                 :class="['Reimbursed', 'Disbursed'].includes(activeClaim.status) ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-400'">
                                <i class="fa-solid fa-money-bill-wave"></i>
                            </div>
                            <span class="text-[9px] font-bold uppercase tracking-wider text-center"
                                 :class="['Reimbursed', 'Disbursed'].includes(activeClaim.status) ? 'text-emerald-600' : 'text-slate-400'">Disbursed<br class="hidden sm:inline"/><span class="text-[8px] text-slate-400 font-normal"> (Completed)</span></span>
                        </div>
                    </div>

                    <!-- SLA Tracking Info -->
                    <div class="mt-4 px-2 sm:px-6" x-show="['Pending', 'Pending Manager', 'Pre-Approved', 'Approved', 'Pending Finance'].includes(activeClaim.status)">
                        <div class="flex items-center gap-2 p-2.5 rounded-lg text-xs font-bold shadow-sm transition-all"
                             :class="activeClaim.sla_status === 'breached' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-blue-50 text-blue-700 border border-blue-200'">
                            
                            <span x-show="activeClaim.sla_status !== 'breached'" class="flex items-center gap-1.5">
                                <i class="fa-solid fa-clock opacity-70"></i>
                                Estimated review: Within <span x-text="['Pending', 'Pending Manager'].includes(activeClaim.status) ? avgManagerTat : avgFinanceTat"></span> hrs
                            </span>
                            
                            <span x-show="activeClaim.sla_status === 'breached'" class="flex items-center gap-1.5">
                                ⚠️ Queue delayed beyond standard SLA
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Prominent Audit Feedback Banner for REVISION_REQUIRED or REJECTED -->
            <template x-if="activeClaim.status === 'REVISION_REQUIRED'">
                <div class="mx-6 mt-4 p-4 bg-gradient-to-r from-amber-50 to-amber-100/60 border border-amber-300 rounded-2xl shadow-xs space-y-2.5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                                <i class="fa-solid fa-triangle-exclamation text-sm"></i>
                            </div>
                            <div>
                                <span class="text-3xs uppercase font-extrabold tracking-wider px-2 py-0.5 rounded-full bg-amber-200/80 text-amber-900 border border-amber-300">
                                    Action Required: Clarification / Revision
                                </span>
                                <h4 class="text-xs font-bold text-amber-950 mt-1">
                                    Reason: <span class="text-slate-900 underline decoration-amber-400 font-semibold" x-text="activeClaim.revision_reason || 'Supporting Documentation Clarification'"></span>
                                </h4>
                            </div>
                        </div>
                        <a :href="'/claims/' + activeClaim.claim_id + '/edit'"
                            class="shrink-0 px-3.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5">
                            <i class="fa-solid fa-pen-to-square"></i> Edit & Resubmit
                        </a>
                    </div>
                    <template x-if="activeClaim.remarks">
                        <div class="p-2.5 bg-white/90 rounded-xl border border-amber-200/80 text-xs text-slate-800 space-y-0.5">
                            <span class="text-3xs uppercase font-bold text-amber-900 block">
                                <i class="fa-solid fa-comment-dots mr-1"></i> Auditor Instructions / Notes:
                            </span>
                            <p class="leading-relaxed whitespace-pre-line text-slate-700" x-text="activeClaim.remarks"></p>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="activeClaim.status === 'Rejected'">
                <div class="mx-6 mt-4 p-4 bg-gradient-to-r from-rose-50 to-rose-100/60 border border-rose-300 rounded-2xl shadow-xs space-y-2.5">
                    <div class="flex items-start gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-rose-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                            <i class="fa-solid fa-ban text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-3xs uppercase font-extrabold tracking-wider px-2 py-0.5 rounded-full bg-rose-200/80 text-rose-900 border border-rose-300">
                                Voucher Claim Rejected
                            </span>
                            <h4 class="text-xs font-bold text-rose-950 mt-1">
                                Rejection Reason: <span class="text-slate-900 underline decoration-rose-400 font-semibold" x-text="activeClaim.rejection_reason || 'Policy Non-Compliance'"></span>
                            </h4>
                        </div>
                    </div>
                    <template x-if="activeClaim.remarks">
                        <div class="p-2.5 bg-white/90 rounded-xl border border-rose-200/80 text-xs text-slate-800 space-y-0.5">
                            <span class="text-3xs uppercase font-bold text-rose-900 block">
                                <i class="fa-solid fa-file-circle-xmark mr-1"></i> Auditor Evaluation Notes:
                            </span>
                            <p class="leading-relaxed whitespace-pre-line text-slate-700" x-text="activeClaim.remarks"></p>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Modal Content (Details + Physical Receipt) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 pt-5">
                <div class="lg:col-span-7 space-y-5">
                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div class="space-y-1">
                            <span class="block font-bold text-slate-400 uppercase text-[9px] tracking-wide">Receipt Invoice No</span>
                            <div class="p-2 bg-slate-50 border border-slate-100 font-mono text-slate-800 rounded-lg font-bold"
                                x-text="activeClaim.claim_type === 'Mileage' ? 'NOT APPLICABLE (MILEAGE)' : activeClaim.receipt_invoice_no">
                            </div>
                        </div>
                        <div class="space-y-1">
                            <span class="block font-bold text-slate-400 uppercase text-[9px] tracking-wide"
                                x-text="activeClaim.claim_type === 'Mileage' ? 'Travel Date' : 'Transaction Date'">
                            </span>
                            <div class="p-2 bg-slate-50 border border-slate-100 text-slate-800 rounded-lg font-bold"
                                x-text="activeClaim.transaction_date ? new Date(activeClaim.transaction_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : 'N/A'">
                            </div>
                        </div>
                        <div class="space-y-1">
                            <span class="block font-bold text-slate-400 uppercase text-[9px] tracking-wide">Expense Category</span>
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
                            <span class="block font-bold text-slate-400 uppercase text-[9px] tracking-wide">Business Operational Purpose</span>
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

                    <x-audit-timeline />
                </div>

                <!-- Panel Paparan Imej Resit -->
                <div class="lg:col-span-5">
                    <div class="bg-slate-50 rounded-2xl border border-slate-100 flex flex-col p-3 h-full min-h-[300px]">
                        <span class="text-[9px] font-bold uppercase text-slate-400 tracking-wider px-1 mb-2">
                            <i class="fa-solid fa-image mr-1"></i> IMBASAN RESIT FIZIKAL
                        </span>
                        <div class="flex-1 bg-slate-900/5 rounded-xl overflow-hidden relative group flex items-center justify-center min-h-[220px]">
                            <template x-if="!activeClaim.receipt_image_path">
                                <div class="flex flex-col items-center justify-center text-slate-400 p-6 text-center">
                                    <i class="fa-solid fa-receipt text-4xl mb-3 opacity-30"></i>
                                    <span class="text-xs font-bold uppercase tracking-wider block mb-1">No Physical Receipt Uploaded</span>
                                    <span class="text-[10px]">(Demo Record)</span>
                                </div>
                            </template>
                            <template x-if="activeClaim.receipt_image_path">
                                <div class="w-full h-full relative flex items-center justify-center group p-2">
                                    <img :src="'/files/' + activeClaim.receipt_image_path"
                                        @click="modalPreviewSrc = '/files/' + activeClaim.receipt_image_path; isHistoryModalOpen = true"
                                        class="max-w-full max-h-[350px] object-contain rounded-lg shadow-xs cursor-zoom-in">
                                    <button type="button"
                                        @click="modalPreviewSrc = '/files/' + activeClaim.receipt_image_path; isHistoryModalOpen = true"
                                        class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-all duration-200 text-white font-bold text-xs gap-1.5 backdrop-blur-xs cursor-zoom-in">
                                        <i class="fa-solid fa-magnifying-glass-plus"></i> View Raw Asset Image
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Modal Berserta Butang Muat Turun PDF -->
            <div class="flex items-center justify-between border-t border-slate-100 pt-5 mt-6 gap-2">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wide text-slate-400 block">Total Claim Cost</span>
                    <span class="text-xl md:text-2xl font-black text-slate-900 font-mono"
                        x-text="'RM ' + parseFloat(activeClaim.amount || 0).toFixed(2)"></span>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Edit & Resubmit Button inside Modal -->
                    <template x-if="activeClaim.status === 'REVISION_REQUIRED'">
                        <a :href="'/claims/' + activeClaim.claim_id + '/edit'"
                            class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl text-xs tracking-wide transition flex items-center gap-1.5 shadow-xs">
                            <i class="fa-solid fa-pen-to-square"></i> Edit & Resubmit
                        </a>
                    </template>

                    <!-- Withdraw Button inside Modal -->
                    <template x-if="['Pending', 'Submitted', 'REVISION_REQUIRED'].includes(activeClaim.status)">
                        <form :action="'/claims/' + activeClaim.claim_id + '/withdraw'" method="POST"
                            @submit.prevent="if(confirm('Are you sure you want to withdraw and cancel this claim voucher?')) $el.submit()">
                            @csrf
                            <button type="submit"
                                class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold rounded-xl text-xs tracking-wide transition flex items-center gap-1.5 cursor-pointer">
                                <i class="fa-solid fa-trash-can"></i> Withdraw
                            </button>
                        </form>
                    </template>

                    <a :href="'/claims/' + activeClaim.claim_id + '/voucher-pdf'" target="_blank"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 font-bold rounded-xl text-xs tracking-wide transition flex items-center gap-1.5">
                        <i class="fa-solid fa-file-pdf text-rose-600"></i> Download PDF
                    </a>
                    <button type="button" @click="isModalOpen = false"
                        class="px-5 py-2 bg-slate-900 text-white font-bold rounded-xl text-xs tracking-wide hover:bg-slate-800 transition-all cursor-pointer">
                        Close Window
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
                avgManagerTat: {{ $avgManagerTat ?? 48 }},
                avgFinanceTat: {{ $avgFinanceTat ?? 72 }},
                isMobileSidebarOpen: false,
                isModalOpen: false,
                activeClaim: {},
                isHistoryModalOpen: false,
                modalPreviewSrc: '',
                isLoading: true,

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
                init() {
                    fetch('/claims/history', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        this.allClaims = data;
                        this.isLoading = false;
                    });
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
