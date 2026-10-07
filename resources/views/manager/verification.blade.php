@extends('layouts.manager')

@section('title', 'SmartClaim - Claims Verification Workspace')

@push('styles')
    <meta name="google-maps-api-key" content="{{ config('services.google.maps_api_key') }}">
@endpush

@section('content')
<div x-data="managerWorkspace()" class="space-y-6">

                <div class="border-b border-slate-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div>
                        <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight"
                        x-text="'Verification Workspace — Matrix: ' + statusTab">Claims Verification Workspace</h1>
                    <p class="text-xs md:text-sm text-slate-500">Perform forensic data sign-offs and finalize
                        institutional asset disbursements.</p>
                    </div>
                    <div class="hidden lg:flex items-center gap-3">
                        <x-system-clock />
                    </div>
                </div>

                @if(session('success'))
                    <div
                        class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-semibold flex items-center gap-2.5 shadow-3xs mb-4">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center p-1 bg-slate-200/60 rounded-xl max-w-xl shadow-3xs text-xs gap-1">
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'pending']) }}"
                        class="flex-1 py-2.5 rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer text-center {{ ($currentTab ?? 'pending') === 'pending' ? 'bg-white text-indigo-900 font-bold shadow-xs' : 'text-slate-500 hover:text-slate-700' }}">
                        Pending Sign-off ({{ $preApprovedCount ?? 0 }})
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'approved']) }}"
                        class="flex-1 py-2.5 rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer text-center {{ ($currentTab ?? 'pending') === 'approved' ? 'bg-white text-emerald-700 font-bold shadow-xs' : 'text-slate-500 hover:text-slate-700' }}">
                        Approved ({{ $approvedCount ?? 0 }})
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['tab' => 'rejected']) }}"
                        class="flex-1 py-2.5 rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer text-center {{ ($currentTab ?? 'pending') === 'rejected' ? 'bg-white text-rose-700 font-bold shadow-xs' : 'text-slate-500 hover:text-slate-700' }}">
                        Rejected ({{ $rejectedCount ?? 0 }})
                    </a>
                </div>

                <div class="bg-white rounded-3xl border border-slate-200/60 shadow-xs overflow-hidden min-h-[420px] flex flex-col justify-between">

                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wide">
                            <i class="fa-solid fa-gavel mr-1.5 text-slate-400"></i> Claims Queue
                        </span>
                        <span class="text-[11px] text-slate-400 font-medium"
                            x-text="totalRecords + ' record(s) in this matrix'"></span>
                    </div>

                    <div class="overflow-x-auto flex-1 min-h-[320px]">
                        <table class="w-full text-left border-collapse text-xs min-w-[720px]">
                            <thead>
                                <tr
                                    class="border-b border-slate-100 text-slate-400 font-bold tracking-wide uppercase bg-slate-50/50">
                                    <th class="py-3 px-4">Employee</th>
                                    <th class="py-3 px-4">Claim ID</th>
                                    <th class="py-3 px-4">Particulars</th>
                                    <th class="py-3 px-4">Type</th>
                                    <th class="py-3 px-4 text-right">Amount</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                    <th class="py-3 px-4 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 text-slate-700 font-medium">
                                @forelse($claims as $claim)
                                    <tr class="hover:bg-slate-50/60 transition-all">
                                        <td class="py-3.5 px-4 font-bold text-slate-900 whitespace-nowrap">
                                            {{ $claim->user_name }}
                                        </td>

                                        <td class="py-3.5 px-4 font-mono text-slate-400 whitespace-nowrap">
                                            CLM-{{ $claim->claim_id }}
                                        </td>

                                        <td class="py-3.5 px-4 max-w-[180px]">
                                            <span class="font-bold text-slate-950 block truncate">
                                                {{ $claim->claim_type === 'Mileage' ? ($claim->title ?: 'Travel Allowance Claim') : $claim->merchant_name }}
                                            </span>

                                            <div class="flex flex-wrap items-center gap-1 mt-0.5">
                                                @if($claim->is_policy_violation)
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-rose-100 text-rose-700 text-[9px] font-black rounded-md uppercase tracking-wider">
                                                        <i class="fa-solid fa-triangle-exclamation text-[8px]"></i> Policy Breach
                                                    </span>
                                                @endif

                                                @if($claim->risk_score && $claim->risk_score > 0)
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[9px] font-black uppercase font-mono tracking-wider {{ $claim->risk_score >= 50 ? 'bg-rose-100 text-rose-800 border border-rose-200' : 'bg-amber-100 text-amber-800 border border-amber-200' }}">
                                                        <i class="fa-solid fa-shield-halved text-[8px]"></i>
                                                        Risk: {{ $claim->risk_score }}%
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="py-3.5 px-4 whitespace-nowrap">
                                            <span class="px-2 py-0.5 rounded font-bold text-[10px] {{ $claim->claim_type === 'Mileage' ? 'bg-blue-50 text-blue-600' : 'bg-slate-100 text-slate-600' }}">
                                                {{ $claim->claim_type }}
                                            </span>
                                        </td>

                                        <td class="py-3.5 px-4 text-right font-black text-slate-900 whitespace-nowrap">
                                            RM {{ number_format((float)$claim->amount > 0 ? $claim->amount : $claim->calculated_amount, 2) }}
                                        </td>

                                        <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                            <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase tracking-wide
                                                {{ $claim->status === 'Approved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '' }}
                                                {{ in_array($claim->status, ['Pending', 'Pre-Approved', 'Pending Manager']) ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : '' }}
                                                {{ $claim->status === 'Rejected' ? 'bg-rose-50 text-rose-700 border border-rose-200' : '' }}">
                                                {{ $claim->status === 'Pending' ? 'Pre-Approved' : $claim->status }}
                                            </span>
                                        </td>

                                        <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                            <button type="button" @click="openModal({{ json_encode($claim) }}, '{{ $claim->user_name }}')" class="px-3 py-1.5 bg-[#0f172a] hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1 mx-auto cursor-pointer uppercase tracking-wider">
                                                <i class="fa-solid fa-gavel text-[10px]"></i> Sign-off
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-14 text-center">
                                            <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                                <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-300 mb-3 border border-slate-100 shadow-inner">
                                                    <i class="fa-solid fa-clipboard-check text-3xl"></i>
                                                </div>
                                                <h4 class="text-sm font-bold text-slate-800">Queue Completely Clear</h4>
                                                <p class="text-xs text-slate-400 mt-1 text-center">No expense or mileage submissions currently require executive sign-off in this view.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-5 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-bold">
                        {{ $claims->links() }}
                    </div>
                </div>
            </div>

    <!-- Sign-off Modal Screen -->
    <div x-show="isModalOpen" x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-all duration-300">
        <div class="relative bg-white rounded-3xl p-4 md:p-6 max-w-5xl w-full shadow-2xl flex flex-col md:flex-row gap-5 max-h-[90vh] overflow-hidden border border-slate-100"
            @click.away="if (!isMapModalOpen && !isHistoryModalOpen) isModalOpen = false">

            <div class="flex-1 flex flex-col overflow-y-auto space-y-4 pr-1 min-h-0">
                <div class="border-b border-slate-100 pb-3 flex flex-col gap-1">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400"
                            x-text="'FINAL EXECUTIVE SIGN-OFF — STAFF SUBMISSION: ' + activeUser"></span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900"
                        x-text="'CLM-' + activeClaim.claim_id + ' | ' + (activeClaim.claim_type === 'Mileage' ? (activeClaim.title ? activeClaim.title : 'Travel Allowance Packet') : activeClaim.merchant_name)">
                    </h3>
                </div>

                <!-- Real-time Policy Violation Alert Banner -->
                <div x-show="activeClaim.is_policy_violation" x-cloak
                    class="p-3 bg-rose-50 border border-rose-200 rounded-2xl flex items-start gap-2.5 text-rose-800 text-xs">
                    <i class="fa-solid fa-triangle-exclamation text-rose-500 text-sm mt-0.5 animate-pulse"></i>
                    <div>
                        <strong class="font-bold text-rose-900 block uppercase text-[10px] tracking-wider">Compliance
                            Warning: Policy Limit Breached</strong>
                        <span class="font-medium"
                            x-text="activeClaim.policy_violation_reason || 'This claim exceeds institutional policy thresholds.'"></span>
                    </div>
                </div>

                <!-- Forensic Integrity Score & Fraud Flags Breakdown Box -->
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

                <div
                    class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-slate-50 p-3 rounded-2xl border border-slate-100 text-xs">
                    <div>
                        <span class="block text-[9px] uppercase font-bold text-slate-400">Date & Time Submitted</span>
                        <div class="font-semibold text-slate-800 mt-0.5">
                            <i class="fa-regular fa-clock mr-1 text-slate-500"></i>
                            <span
                                x-text="activeClaim.created_at ? new Date(activeClaim.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'N/A'"></span>
                        </div>
                    </div>
                    <div>
                        <span class="block text-[9px] uppercase font-bold text-slate-400"
                            x-text="activeClaim.claim_type === 'Mileage' ? 'Journey Date' : 'Invoice Date'"></span>
                        <div class="font-semibold text-slate-800 mt-0.5">
                            <i class="fa-regular fa-calendar-days mr-1 text-slate-500"></i>
                            <span
                                x-text="activeClaim.transaction_date ? new Date(activeClaim.transaction_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : 'N/A'"></span>
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
                        <div class="p-2.5 bg-slate-50 border border-slate-200/60 font-bold rounded-xl text-emerald-700"
                            x-text="activeClaim.claim_type === 'Mileage' ? 'Transport Travel Allowance' : activeClaim.predicted_category">
                        </div>
                    </div>
                    <div class="space-y-1">
                        <span class="block font-bold text-slate-400 uppercase text-[9px]">Payment Method</span>
                        <div class="p-2.5 bg-slate-50 border border-slate-200/60 font-semibold rounded-xl text-slate-800"
                            x-text="activeClaim.claim_type === 'Mileage' ? 'Corporate Bank Allowance' : (activeClaim.payment_method || 'Cash')">
                        </div>
                    </div>
                    <div class="space-y-1">
                        <span class="block font-bold text-slate-400 uppercase text-[9px]">Voucher Grand Total</span>
                        <div class="p-2.5 bg-slate-50 border border-slate-200/60 font-black font-mono rounded-xl text-slate-900"
                            x-text="'RM ' + (parseFloat(activeClaim.amount) > 0 ? parseFloat(activeClaim.amount) : parseFloat(activeClaim.calculated_amount || 0)).toFixed(2)"></div>
                    </div>

                    <div class="col-span-1 sm:col-span-2 space-y-1" x-show="activeClaim.vehicle_plate_number">
                        <span
                            class="block font-bold text-rose-700 uppercase text-[9px] tracking-wide flex items-center gap-1">
                            <i class="fa-solid fa-car-side"></i> Authorized Fleet Tracking Node
                        </span>
                        <div
                            class="p-2.5 bg-rose-50/40 border border-rose-100 text-rose-950 rounded-xl font-black font-mono flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span
                                    class="px-2 py-0.5 bg-rose-600 text-white font-mono text-[9px] font-black rounded uppercase tracking-wider">Plate
                                    Index</span>
                                <span class="text-sm tracking-widest" x-text="activeClaim.vehicle_plate_number"></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-1 sm:col-span-2 space-y-1.5">
                        <span class="block font-bold text-slate-400 uppercase text-[9px] tracking-wide">
                            <i class="fa-solid"
                                :class="activeClaim.claim_type === 'Mileage' ? 'fa-route text-blue-600' : 'fa-map-location-dot'"></i>
                            <span
                                x-text="activeClaim.claim_type === 'Mileage' ? 'Authorized Travel Logistics Route' : 'Location Branch Address'"></span>
                        </span>

                        <template x-if="activeClaim.claim_type === 'Mileage'">
                            <div class="p-3 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                                <div class="flex items-start gap-2 text-xs">
                                    <i class="fa-solid fa-circle-dot text-blue-500 mt-1 text-[10px]"></i>
                                    <div>
                                        <span
                                            class="text-[9px] text-slate-400 block uppercase font-black tracking-wider">Starting
                                            Point</span>
                                        <span class="text-slate-700 font-bold"
                                            x-text="activeClaim.start_location ? activeClaim.start_location : 'Unknown Address Node'"></span>
                                    </div>
                                </div>
                                <div class="w-px h-3 bg-slate-300 ml-1.5 border-dashed"></div>
                                <div class="flex items-start gap-2 text-xs">
                                    <i class="fa-solid fa-location-dot text-rose-500 mt-1 text-[10px]"></i>
                                    <div>
                                        <span
                                            class="text-[9px] text-slate-400 block uppercase font-black tracking-wider">Destination
                                            Point</span>
                                        <span class="text-slate-700 font-bold"
                                            x-text="activeClaim.destination_location ? activeClaim.destination_location : 'Unknown Destination Node'"></span>
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
                            </div>
                        </template>

                        <template x-if="activeClaim.claim_type !== 'Mileage'">
                            <div class="p-2.5 bg-slate-50 border border-slate-100 text-slate-700 rounded-lg leading-relaxed font-medium"
                                x-text="activeClaim.location_address || 'No branch address logged.'"></div>
                        </template>
                    </div>

                    <div class="col-span-1 sm:col-span-2 space-y-1">
                        <span class="block font-bold text-slate-400 uppercase text-[9px]">Staff Justification Statement
                            (Business Purpose)</span>
                        <div class="p-2.5 bg-slate-50 border border-slate-200/60 text-slate-700 font-medium italic rounded-xl min-h-[50px]"
                            x-text="activeClaim.business_purpose || 'No corporate justification statement entered.'">
                        </div>
                    </div>
                </div>

                <div class="space-y-2 border-t border-slate-100 pt-3">
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        <i class="fa-solid fa-calculator"></i> Verified Cost Matrix Breakdowns
                    </h4>
                    <div class="space-y-1.5 max-h-[160px] overflow-y-auto">
                        <template x-if="activeClaim.claim_type === 'Mileage'">
                            <div class="overflow-hidden border border-slate-200 rounded-xl bg-white shadow-3xs">
                                <table class="w-full text-left border-collapse text-[11px]">
                                    <thead
                                        class="bg-slate-50 text-slate-400 font-bold uppercase border-b border-slate-100 text-[9px]">
                                        <tr>
                                            <th class="p-2.5">Audit Parameter Metric</th>
                                            <th class="p-2.5 text-center">Logged Metric</th>
                                            <th class="p-2.5 text-right">Computed Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                        <tr>
                                            <td class="p-2.5 text-slate-500">Calculated Journey Distance</td>
                                            <td class="p-2.5 text-center font-mono font-bold text-slate-900"
                                                x-text="parseFloat(activeClaim.mileage_km).toFixed(2) + ' KM'"></td>
                                            <td class="p-2.5 text-right font-mono text-slate-300">-</td>
                                        </tr>
                                        <tr>
                                            <td class="p-2.5 text-slate-500"
                                                x-text="'Applied Rate (' + (activeClaim.vehicle_type ? activeClaim.vehicle_type : 'Car') + ')'">
                                            </td>
                                            <td class="p-2.5 text-center font-mono text-slate-600"
                                                x-text="activeClaim.vehicle_type === 'Motorcycle' ? 'RM 0.30 / KM' : 'RM 0.60 / KM'">
                                            </td>
                                            <td class="p-2.5 text-right font-mono text-slate-300">-</td>
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

                <div class="border-t border-slate-100 pt-3 mt-auto flex flex-col gap-3 bg-white sticky bottom-0">
                    <template x-if="activeClaim.status === 'Pre-Approved' || activeClaim.status === 'Pending' || activeClaim.status === 'Pending Manager'">
                        <div class="grid grid-cols-3 gap-2">
                            <!-- Reject Action: Triggers Rejection Dialog with Mandatory Reason -->
                            <button type="button" @click="isRejectModalOpen = true; rejectReason = ''"
                                class="w-full py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs uppercase tracking-wider rounded-xl cursor-pointer transition-all active:scale-[0.98] flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-ban text-[11px]"></i> Reject
                            </button>

                            <!-- Revision Action: Returns Claim to Staff with Mandatory Feedback -->
                            <button type="button" @click="isRevisionModalOpen = true; revisionNotes = ''"
                                class="w-full py-2.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 font-bold text-xs uppercase tracking-wider rounded-xl cursor-pointer transition-all active:scale-[0.98] flex items-center justify-center gap-1.5">
                                <i class="fa-solid fa-arrow-rotate-left text-[11px]"></i> Revision
                            </button>

                            <!-- Final Approval Action: Directly Commits State Change & Audit Log -->
                            <form :action="'/manager/claims/' + activeClaim.claim_id + '/status'" method="POST" class="w-full" x-data="{ loading: false }" @submit="loading = true">
                                @csrf
                                <input type="hidden" name="status" value="Approved">
                                <button type="submit" :disabled="loading" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-wider rounded-xl cursor-pointer transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-1.5 shadow-sm">
                                    <template x-if="loading"><i class="fa-solid fa-spinner fa-spin text-xs"></i></template>
                                    <template x-if="!loading"><i class="fa-solid fa-stamp text-xs"></i></template>
                                    <span x-text="loading ? 'Signing...' : 'Approve'"></span>
                                </button>
                            </form>
                        </div>
                    </template>
                    <a :href="'/claims/' + activeClaim.claim_id + '/voucher-pdf'" target="_blank"
                        class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl flex items-center justify-center gap-1.5 transition">
                        <i class="fa-solid fa-file-pdf text-rose-600"></i> Download Forensic Payment Voucher (PDF)
                    </a>
                    <button type="button" @click="isModalOpen = false"
                        class="text-xs text-slate-400 font-bold hover:underline text-center">Dismiss Screen</button>
                </div>
            </div>

            <div class="w-full md:w-[380px] lg:w-[420px] bg-slate-50 rounded-2xl border border-slate-100 flex flex-col p-2 shrink-0 max-h-[40vh] md:max-h-full"
                x-show="activeClaim.receipt_image_path">
                <span class="text-[9px] font-bold uppercase text-slate-400 tracking-wider px-2 mb-1.5">
                    <i class="fa-solid fa-image mr-1"></i>
                    <span
                        x-text="activeClaim.claim_type === 'Mileage' ? 'Attached Proof of Travel Asset' : 'Attached Audit Receipt Resource'"></span>
                </span>
                <div
                    class="flex-1 bg-slate-900/5 rounded-xl overflow-hidden relative flex items-center justify-center min-h-[220px] md:min-h-0">
                    <img :src="'/files/' + activeClaim.receipt_image_path"
                        @click="modalPreviewSrc = '/files/' + activeClaim.receipt_image_path; isHistoryModalOpen = true"
                        class="max-w-full max-h-full object-contain rounded-lg shadow-xs cursor-zoom-in">

                    <button type="button"
                        @click="modalPreviewSrc = '/files/' + activeClaim.receipt_image_path; isHistoryModalOpen = true"
                        class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-all duration-200 text-white font-bold text-xs gap-1.5 backdrop-blur-xs cursor-zoom-in">
                        <i class="fa-solid fa-magnifying-glass-plus"></i> View Raw Asset Image
                    </button>
                </div>
                <div class="pt-2" x-show="activeClaim.claim_type === 'Mileage'">
                    <button type="button" @click="isMapModalOpen = true"
                        class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-center flex items-center justify-center gap-1.5 transition-all text-[11px] uppercase tracking-wider cursor-pointer">
                        <i class="fa-solid fa-map-location-dot"></i> Cross-Verify Route
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- Executive Rejection Modal Screen -->
    <div x-show="isRejectModalOpen" x-cloak
        class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs transition-all duration-300">
        <div class="relative bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl overflow-hidden border border-slate-100 space-y-4"
            @click.away="isRejectModalOpen = false" x-transition:enter="transition ease-out duration-200 transform"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2 text-rose-600">
                    <i class="fa-solid fa-circle-exclamation text-lg"></i>
                    <h4 class="font-bold text-slate-900 text-sm">Reject Claim Voucher</h4>
                </div>
                <button type="button" @click="isRejectModalOpen = false"
                    class="text-slate-400 hover:text-slate-600 transition-all text-lg cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form :action="'/manager/claims/' + activeClaim.claim_id + '/status'" method="POST" class="space-y-4"
                x-data="{ selectedReason: '', requiresRemarks: false, remarks: '' }">
                @csrf
                <input type="hidden" name="status" value="Rejected">
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Audit Rejection Reason <span class="text-rose-500">*</span>
                    </label>
                    <select name="rejection_reason" x-model="selectedReason" required
                        @change="const opt = $event.target.selectedOptions[0]; requiresRemarks = opt.dataset.requiresRemarks === '1' || opt.value.includes('Other');"
                        class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 font-medium">
                        <option value="">-- Select Audit Exception Code --</option>
                        @foreach($rejectionReasons ?? [] as $reason)
                            <option value="{{ $reason->title }}" data-requires-remarks="{{ $reason->requires_remarks ? '1' : '0' }}">
                                {{ $reason->title }} {{ $reason->requires_remarks ? '(Remarks Required)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Auditor Notes / Detailed Remarks <span x-show="requiresRemarks" class="text-rose-500">*</span>
                    </label>
                    <textarea name="remarks" x-model="remarks" :required="requiresRemarks" rows="3"
                        :placeholder="requiresRemarks ? 'Explicit detailed justification is mandatory for this exception code...' : 'Optional clarifying notes for employee and audit trail...'"
                        class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 font-medium"></textarea>
                    <p class="text-[10px] text-slate-400 mt-1">
                        <span x-show="requiresRemarks" class="text-rose-600 font-semibold">Remarks are mandatory for this exception code.</span>
                        <span x-show="!requiresRemarks">This explanation will be permanently recorded in the Audit Log.</span>
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="isRejectModalOpen = false"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl cursor-pointer transition">
                        Cancel
                    </button>
                    <button type="submit" :disabled="!selectedReason || (requiresRemarks && !remarks.trim())"
                        class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl cursor-pointer transition disabled:opacity-50 shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-ban text-[10px]"></i> Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Request Revision Modal Screen -->
    <div x-show="isRevisionModalOpen" x-cloak
        class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs transition-all duration-300">
        <div class="relative bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl overflow-hidden border border-slate-100 space-y-4"
            @click.away="isRevisionModalOpen = false" x-transition:enter="transition ease-out duration-200 transform"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2 text-amber-600">
                    <i class="fa-solid fa-arrow-rotate-left text-lg"></i>
                    <h4 class="font-bold text-slate-900 text-sm">Request Claim Revision</h4>
                </div>
                <button type="button" @click="isRevisionModalOpen = false"
                    class="text-slate-400 hover:text-slate-600 transition-all text-lg cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form :action="'/manager/claims/' + activeClaim.claim_id + '/status'" method="POST" class="space-y-4"
                x-data="{ selectedReason: '', requiresRemarks: false, remarks: '' }">
                @csrf
                <input type="hidden" name="status" value="REVISION_REQUIRED">
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Audit Clarification Reason <span class="text-amber-500">*</span>
                    </label>
                    <select name="revision_reason" x-model="selectedReason" required
                        @change="const opt = $event.target.selectedOptions[0]; requiresRemarks = opt.dataset.requiresRemarks === '1' || opt.value.includes('Other');"
                        class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 font-medium">
                        <option value="">-- Select Audit Exception Code --</option>
                        @foreach($revisionReasons ?? [] as $reason)
                            <option value="{{ $reason->title }}" data-requires-remarks="{{ $reason->requires_remarks ? '1' : '0' }}">
                                {{ $reason->title }} {{ $reason->requires_remarks ? '(Remarks Required)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Revision Instructions for Staff <span x-show="requiresRemarks" class="text-amber-500">*</span>
                    </label>
                    <textarea name="remarks" x-model="remarks" :required="requiresRemarks" rows="3"
                        :placeholder="requiresRemarks ? 'Detail specific required revisions or missing items...' : 'Optional directions (e.g. please upload clear photo of receipt)...'"
                        class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 font-medium"></textarea>
                    <p class="text-[10px] text-slate-400 mt-1">
                        <span x-show="requiresRemarks" class="text-amber-600 font-semibold">Instructions are mandatory for this exception code.</span>
                        <span x-show="!requiresRemarks">Staff will receive this actionable feedback to resubmit their voucher.</span>
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="isRevisionModalOpen = false"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl cursor-pointer transition">
                        Cancel
                    </button>
                    <button type="submit" :disabled="!selectedReason || (requiresRemarks && !remarks.trim())"
                        class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl cursor-pointer transition disabled:opacity-50 shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-paper-plane text-[10px]"></i> Send to Staff
                    </button>
                </div>
            </form>
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
                    <i class="fa-solid fa-map-location-dot mr-1 text-blue-600"></i> Interactive Route Verification
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

    <!-- Raw Asset Lightbox Modal -->
    <div x-show="isHistoryModalOpen" x-cloak
        class="fixed inset-0 z-[250] flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs transition-all duration-300">
        <div class="relative bg-white rounded-3xl p-3 max-w-2xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
            @click.away="isHistoryModalOpen = false" x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

            <div class="flex items-center justify-between px-4 py-2 border-b border-slate-100">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">
                    <i class="fa-solid fa-receipt mr-1 text-blue-600"></i> Full View Forensic Asset
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
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('managerWorkspace', () => ({
                isMobileSidebarOpen: false,
                statusTab: (new URLSearchParams(window.location.search)).get('tab') || 'pending',
                setTab(tab) {
                    this.statusTab = tab;
                    const url = new URL(window.location);
                    url.searchParams.set('tab', tab);
                    window.history.pushState({}, '', url);
                },
                isModalOpen: false,
                isRejectModalOpen: false,
                isRevisionModalOpen: false,
                rejectReason: '',
                revisionNotes: '',
                isMapModalOpen: false,
                activeClaim: {},
                activeUser: '',
                isHistoryModalOpen: false,
                modalPreviewSrc: '',
                googleDistanceKm: null,
                googleVariancePct: null,
                googleDirectionsRenderer: null,

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

                openModal(claim, username) {
                    this.activeClaim = claim;
                    this.activeUser = username;
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
    <x-route-modal />
@endpush
@endsection
