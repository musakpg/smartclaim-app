@extends('layouts.staff')

@section('title', 'SmartClaim - Claim History')

@section('content')
<div x-data="historyManager()" class="space-y-6">
                <div class="space-y-0.5">
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                        <span>Claim History</span>
                        <span class="text-xs font-mono font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md">
                            <span>{{ $claims->total() }}</span> Logs
                        </span>
                    </h1>
                    <p class="text-xs md:text-sm text-slate-500">Complete records of claims generated dynamically via local extraction engines.</p>
                </div>

                <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-xs min-h-[420px] flex flex-col justify-between">
                    <div class="divide-y divide-slate-100 flex-1">
                        @forelse($claims as $claim)
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between py-4 first:pt-0 last:pb-0 gap-4">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center border border-slate-100 font-bold text-xs text-slate-700 font-mono">
                                        <span>CLM-{{ $claim->claim_id }}</span>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-bold text-slate-900 text-sm">
                                                {{ $claim->claim_type === 'Mileage' ? ($claim->title ?? 'Travel Allowance Claim') : $claim->merchant_name }}
                                            </h4>
                                            <span class="px-2 py-0.5 rounded-full font-bold text-[9px] uppercase tracking-wide border
                                                {{ in_array($claim->status, ['Approved', 'Reimbursed', 'Disbursed']) ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : '' }}
                                                {{ in_array($claim->status, ['Pending Manager', 'Pending Finance', 'Pending']) ? 'bg-amber-50 text-amber-700 border-amber-100' : '' }}
                                                {{ !in_array($claim->status, ['Approved', 'Reimbursed', 'Disbursed', 'Pending Manager', 'Pending Finance', 'Pending']) ? 'bg-slate-50 text-slate-700 border-slate-100' : '' }}">
                                                {{ $claim->status }}
                                            </span>
                                            
                                            @if($claim->sla_status)
                                                <span class="px-2 py-0.5 rounded-full font-bold text-[9px] tracking-wide border flex items-center gap-1
                                                    {{ $claim->sla_status === 'breached' ? 'bg-amber-50 text-amber-700 border-amber-200' : '' }}
                                                    {{ $claim->sla_status === 'resolved' ? 'bg-slate-50 text-slate-500 border-slate-200' : '' }}
                                                    {{ !in_array($claim->sla_status, ['breached', 'resolved']) ? 'bg-blue-50 text-blue-700 border-blue-200' : '' }}">
                                                    @if(!in_array($claim->sla_status, ['resolved', 'breached']))
                                                        <span class="relative flex h-1.5 w-1.5 shrink-0">
                                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-blue-500"></span>
                                                        </span>
                                                    @endif
                                                    @if($claim->sla_status === 'breached')
                                                        <i class="fa-solid fa-triangle-exclamation text-[9px]"></i>
                                                    @elseif($claim->sla_status === 'resolved')
                                                        <i class="fa-solid fa-check text-[9px]"></i>
                                                    @endif
                                                    <span>
                                                        @if($claim->sla_status === 'breached')
                                                            Delayed - Expedited Review Active
                                                        @elseif(in_array($claim->status, ['Pending Manager', 'Pending']))
                                                            Est. Pre-Approval: {{ $claim->time_remaining_human }}
                                                        @elseif(in_array($claim->status, ['Approved', 'Pending Finance']))
                                                            Est. Payout: by {{ $claim->estimated_completion_at ? $claim->estimated_completion_at->format('M d, Y') : 'Finance Batch' }}
                                                        @else
                                                            {{ $claim->time_remaining_human }}
                                                        @endif
                                                    </span>
                                                </span>
                                            @endif
                                        </div>

                                        <div class="text-xs text-slate-500 font-medium space-y-1">
                                            @if($claim->claim_type === 'Mileage')
                                                <div class="space-y-0.5">
                                                    <span class="block">Logistics: <span class="font-semibold text-slate-700">{{ $claim->vehicle_type ?? 'Vehicle' }} ({{ number_format((float)$claim->mileage_km, 2) }} KM)</span></span>
                                                    <span class="inline-flex items-center gap-1.5 bg-slate-50 border border-slate-100 px-2 py-0.5 rounded-md text-[11px] text-slate-600 mt-0.5">
                                                        <i class="fa-solid fa-map-location-dot text-slate-400"></i>
                                                        <span>{{ $claim->start_location ?? 'Start Node' }}</span>
                                                        <i class="fa-solid fa-arrow-right-long text-[9px] text-slate-400"></i>
                                                        <span>{{ $claim->destination_location ?? 'End Node' }}</span>
                                                    </span>
                                                </div>
                                            @else
                                                <span>Invoice No: <span class="font-mono text-slate-700">{{ $claim->receipt_invoice_no ?? 'N/A' }}</span></span>
                                            @endif
                                        </div>

                                        <p class="text-[10px] text-slate-400 font-medium">
                                            <i class="fa-solid fa-calendar-day mr-1"></i>Processed on:
                                            <span>{{ $claim->created_at->format('Y-m-d H:i') }}</span>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end gap-3 sm:gap-4">
                                    <div class="text-right">
                                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block text-[10px]">Total Amount</span>
                                        <span class="font-bold text-slate-900 text-sm">RM {{ number_format((float)$claim->amount, 2) }}</span>
                                    </div>

                                    @if($claim->status === 'REVISION_REQUIRED')
                                        <a href="/claims/{{ $claim->claim_id }}/edit"
                                            class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 px-3 rounded-xl text-xs tracking-wide transition shadow-3xs">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit & Resubmit
                                        </a>
                                    @endif

                                    @if(in_array($claim->status, ['Pending', 'Submitted', 'REVISION_REQUIRED']))
                                        <form action="/claims/{{ $claim->claim_id }}/withdraw" method="POST"
                                            onsubmit="return confirm('Are you sure you want to withdraw and cancel this claim voucher? This action cannot be undone.')">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center gap-1 bg-slate-100 hover:bg-rose-50 text-slate-500 hover:text-rose-700 font-bold py-2 px-2.5 rounded-xl text-xs tracking-wide transition shadow-3xs cursor-pointer border border-transparent hover:border-rose-200"
                                                title="Withdraw / Cancel Claim">
                                                <i class="fa-solid fa-trash-can text-rose-500"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <a href="/claims/{{ $claim->claim_id }}/voucher-pdf" target="_blank"
                                        class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2 px-3 rounded-xl text-xs tracking-wide transition shadow-3xs">
                                        <i class="fa-solid fa-file-pdf text-rose-600"></i> PDF
                                    </a>

                                    <button type="button" @click="openDetailModal({{ $claim->claim_id }})"
                                        class="inline-flex items-center gap-1.5 bg-[#1e293b] hover:bg-slate-800 text-white font-bold py-2 px-3.5 rounded-xl text-xs tracking-wide transition shadow-3xs cursor-pointer">
                                        <i class="fa-solid fa-circle-info text-xs"></i> Detail
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                    <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 mb-3 border border-slate-100 shadow-inner">
                                        <i class="fa-solid fa-receipt text-3xl"></i>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-800">No Historical Claims</h4>
                                    <p class="text-xs text-slate-400 mt-1 mb-4 text-center">No expense or mileage vouchers have been submitted to date.</p>
                                    <a href="{{ route('claims.create') }}" class="inline-flex items-center gap-2 bg-[#00d1b2] hover:bg-[#00bfa5] text-white text-xs font-bold py-2.5 px-4 rounded-xl shadow-xs transition">
                                        <i class="fa-solid fa-plus text-[10px]"></i> Create New Claim
                                    </a>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    <!-- Pagination Navigation -->
                    <div class="px-6 py-4 border-t border-slate-100 bg-white rounded-b-2xl mt-auto">
                        {{ $claims->links() }}
                </div>

    <!-- Modal Detail Claim (Teleported to root body) -->
    <template x-teleport="body">
        <div x-show="isModalOpen || isDetailOpen || isOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/60 p-4 sm:p-6"
            style="display: none;"
            @keydown.escape.window="closeModal()">
            <div class="relative bg-white rounded-3xl max-w-5xl w-full max-h-[90vh] overflow-y-auto p-6 md:p-8 shadow-2xl"
                @click.away="closeModal()">

                <!-- Close Button Top Right -->
                <button type="button" @click="closeModal()"
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
                            <div x-show="!getReceiptUrl(activeClaim) || imageFailed" class="flex flex-col items-center justify-center text-slate-400 p-6 text-center">
                                <i class="fa-solid fa-receipt text-4xl mb-3 opacity-30"></i>
                                <span class="text-xs font-bold uppercase tracking-wider block mb-1">No Physical Receipt Available</span>
                                <span class="text-[10px] text-slate-400">Preview asset not found or not required</span>
                            </div>
                            <div x-show="getReceiptUrl(activeClaim) && !imageFailed" class="w-full h-full relative flex items-center justify-center group p-2">
                                <img :src="getReceiptUrl(activeClaim)"
                                    x-on:error="imageFailed = true"
                                    @click="modalPreviewSrc = getReceiptUrl(activeClaim); isHistoryModalOpen = true"
                                    class="max-w-full max-h-[350px] object-contain rounded-lg shadow-xs cursor-zoom-in">
                                <button type="button"
                                    @click="modalPreviewSrc = getReceiptUrl(activeClaim); isHistoryModalOpen = true"
                                    class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-all duration-200 text-white font-bold text-xs gap-1.5 backdrop-blur-xs cursor-zoom-in">
                                    <i class="fa-solid fa-magnifying-glass-plus"></i> View Raw Asset Image
                                </button>
                            </div>
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
                    <button type="button" @click="closeModal()"
                        class="px-5 py-2 bg-slate-900 text-white font-bold rounded-xl text-xs tracking-wide hover:bg-slate-800 transition-all cursor-pointer">
                        Close Window
                    </button>
                </div>
            </div>

        </div>
    </div>
    </template>

    <!-- Zoom Preview Modal (Teleported to root body) -->
    <template x-teleport="body">
        <div x-show="isHistoryModalOpen" x-cloak
            class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs transition-all duration-300"
            style="display: none;">
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
    </template>
</div>

@push('scripts')
    <script>
        (function() {
            const initHistoryManager = () => {
                const historyManagerData = () => ({
                    claimsById: @json($claims->keyBy('claim_id')),
                    avgManagerTat: {{ $avgManagerTat ?? 48 }},
                    avgFinanceTat: {{ $avgFinanceTat ?? 72 }},
                    isMobileSidebarOpen: false,
                    isModalOpen: false,
                    isDetailOpen: false,
                    isOpen: false,
                    imageFailed: false,
                    activeClaim: {},
                    isHistoryModalOpen: false,
                    modalPreviewSrc: '',
                    getReceiptUrl(claim) {
                        if (!claim) return '';
                        let p = claim.receipt_image_path || claim.receipt_path || '';
                        if (!p) return '';
                        if (p.startsWith('http://') || p.startsWith('https://')) return p;
                        p = p.replace(/^\/+/, '');
                        if (p.startsWith('storage/')) {
                            return '/' + p;
                        }
                        return '/files/' + p;
                    },
                    openDetailModal(claimOrId) {
                        let claim = (typeof claimOrId === 'object' && claimOrId !== null)
                            ? claimOrId
                            : (this.claimsById[claimOrId] || {});

                        if (typeof claim.fraud_flags === 'string') {
                            try {
                                claim.fraud_flags = JSON.parse(claim.fraud_flags);
                            } catch (e) {
                                claim.fraud_flags = [];
                            }
                        }
                        if (!Array.isArray(claim.fraud_flags)) {
                            claim.fraud_flags = [];
                        }

                        this.imageFailed = false;
                        this.activeClaim = claim;
                        this.isModalOpen = true;
                        this.isDetailOpen = true;
                        this.isOpen = true;
                    },
                    openModal(claimOrId) {
                        this.openDetailModal(claimOrId);
                    },
                    closeModal() {
                        this.isModalOpen = false;
                        this.isDetailOpen = false;
                        this.isOpen = false;
                    }
                });

                window.historyManager = historyManagerData;

                if (window.Alpine) {
                    Alpine.data('historyManager', historyManagerData);
                } else {
                    document.addEventListener('alpine:init', () => {
                        Alpine.data('historyManager', historyManagerData);
                    });
                }
            };

            initHistoryManager();
        })();
    </script>
@endpush
@endsection
