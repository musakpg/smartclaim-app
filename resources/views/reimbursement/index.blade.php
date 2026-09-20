<!DOCTYPE html>
<html lang="en" x-data="{ 
    isMobileSidebarOpen: false, 
    isDetailModalOpen: false, 
    isZoomModalOpen: false,
    zoomImageUrl: '',
    zoomImageTitle: '',
    selectedClaim: null 
}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Reimbursement Settlement Status</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased"
    :class="(isDetailModalOpen || isZoomModalOpen || isMobileSidebarOpen) ? 'overflow-hidden' : ''">

    <div class="flex min-h-screen flex-col lg:flex-row">

        <!-- Centralized Staff Sidebar Component -->
        @include('layouts.partials.staff-sidebar')

        <!-- Main Content Workspace -->
        <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full pb-24 lg:pb-8 overflow-y-auto space-y-6">

            <!-- Header Banner -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-5 border-b border-slate-200 gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Reimbursement Payouts</h1>
                    <p class="text-xs md:text-sm text-slate-500">Track approved expense vouchers, estimated payout schedules, and bank transfer reference IDs.</p>
                </div>
                <div class="hidden lg:block">
                    @include('layouts.partials.notification-bell')
                </div>
            </div>

            <!-- Metric KPI Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Approved Entitlement</span>
                    <h2 class="text-2xl font-black text-slate-900 font-mono">RM {{ number_format($approvedTotal ?? 0, 2) }}</h2>
                    <span class="text-xs text-slate-500 font-medium">{{ count($approvedClaims ?? []) }} claim record(s)</span>
                </div>

                <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 block">Successfully Disbursed</span>
                    <h2 class="text-2xl font-black text-emerald-600 font-mono">RM {{ number_format($paidTotal ?? 0, 2) }}</h2>
                    <span class="text-xs text-emerald-700/80 font-medium">Credited to staff bank account</span>
                </div>

                <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600 block">Awaiting Bank Transfer</span>
                    <h2 class="text-2xl font-black text-amber-600 font-mono">RM {{ number_format($processingTotal ?? 0, 2) }}</h2>
                    <span class="text-xs text-amber-700/80 font-medium">Scheduled for upcoming payout batch</span>
                </div>
            </div>

            <!-- Reimbursement Ledger Table -->
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                        <i class="fa-solid fa-money-bill-transfer text-emerald-600 mr-1.5"></i> Bank Settlement Ledger
                    </span>
                    <span class="text-[11px] text-slate-400 font-mono">Click row to view full forensic audit & proof</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs min-w-[700px]">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="p-3.5">Voucher Reference</th>
                                <th class="p-3.5">Expense Details</th>
                                <th class="p-3.5">Reimbursement Total</th>
                                <th class="p-3.5">Disbursement Status</th>
                                <th class="p-3.5">Bank Reference / TX ID</th>
                                <th class="p-3.5">Settlement Date</th>
                                <th class="p-3.5 text-right">Audit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @forelse($approvedClaims ?? [] as $claim)
                                <tr class="hover:bg-slate-50/60 cursor-pointer transition"
                                    @click="selectedClaim = {{ json_encode($claim->load('items')) }}; isDetailModalOpen = true;">
                                    <td class="p-3.5 whitespace-nowrap">
                                        <span class="font-black text-slate-900 block font-mono">#CLM-{{ $claim->claim_id }}</span>
                                        <span class="text-[10px] text-slate-400 font-mono">Inv: {{ $claim->receipt_invoice_no ?? 'N/A' }}</span>
                                    </td>
                                    <td class="p-3.5">
                                        <span class="font-bold text-slate-800 block">{{ $claim->merchant_name }}</span>
                                        <span class="text-[10px] text-slate-400">{{ $claim->predicted_category }}</span>
                                    </td>
                                    <td class="p-3.5 whitespace-nowrap">
                                        <span class="text-sm font-black text-slate-900 font-mono">RM {{ number_format($claim->amount, 2) }}</span>
                                    </td>
                                    <td class="p-3.5 whitespace-nowrap">
                                        @if($claim->status === 'Reimbursed')
                                            <span class="px-2.5 py-1 rounded-xl font-bold text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1">
                                                <i class="fa-solid fa-circle-check text-[9px]"></i> Disbursed / Paid
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-xl font-bold text-[10px] bg-amber-50 text-amber-700 border border-amber-200 inline-flex items-center gap-1">
                                                <i class="fa-solid fa-clock-rotate-left text-[9px]"></i> Scheduled Payout
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-3.5 whitespace-nowrap font-mono">
                                        @if($claim->payment_reference)
                                            <span class="px-2 py-0.5 bg-slate-100 border border-slate-200 rounded-md text-slate-800 font-bold text-[11px]">
                                                {{ $claim->payment_reference }}
                                            </span>
                                        @else
                                            <span class="text-slate-400 text-[11px]">Pending Bank Settlement</span>
                                        @endif
                                    </td>
                                    <td class="p-3.5 whitespace-nowrap text-slate-500 font-mono text-[11px]">
                                        @if($claim->paid_at)
                                            {{ \Carbon\Carbon::parse($claim->paid_at)->format('d/m/Y') }}
                                        @else
                                            Est: {{ $claim->estimated_payout_date ? \Carbon\Carbon::parse($claim->estimated_payout_date)->format('d/m/Y') : '5 Days' }}
                                        @endif
                                    </td>
                                    <td class="p-3.5 text-right">
                                        <button type="button" class="text-blue-600 hover:text-blue-800 font-bold text-xs">
                                            Details <i class="fa-solid fa-chevron-right text-[10px] ml-0.5"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-10 text-center text-slate-400 font-medium">
                                        <i class="fa-solid fa-receipt block text-3xl mb-2 text-slate-300"></i>
                                        No approved claims scheduled for reimbursement yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- Forensic Audit Detail Modal (Dual Resource Layout) -->
    <div x-show="isDetailModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl p-6 max-w-4xl w-full shadow-2xl border border-slate-100 space-y-6 max-h-[90vh] overflow-y-auto"
            @click.away="isDetailModalOpen = false">

            <template x-if="selectedClaim">
                <div class="space-y-6">
                    <!-- Header -->
                    <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block" x-text="'CLAIM ID: CLM-' + selectedClaim.claim_id"></span>
                            <h2 class="text-xl font-black text-slate-900" x-text="selectedClaim.merchant_name"></h2>
                        </div>
                        <span class="px-3 py-1 rounded-full font-bold text-xs uppercase tracking-wider"
                            :class="selectedClaim.status === 'Reimbursed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                            x-text="selectedClaim.status"></span>
                    </div>

                    <!-- 2-Column Split Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
                        
                        <!-- Left Column: Claim Details & Banking Reconciliation -->
                        <div class="space-y-4">
                            <!-- Basic Metadata -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Invoice No</span>
                                    <span class="font-bold text-slate-800 font-mono" x-text="selectedClaim.receipt_invoice_no"></span>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Transaction Date</span>
                                    <span class="font-bold text-slate-800 font-mono" x-text="selectedClaim.transaction_date"></span>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Category</span>
                                    <span class="font-bold text-slate-800" x-text="selectedClaim.predicted_category"></span>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Payment Method</span>
                                    <span class="font-bold text-slate-800" x-text="selectedClaim.payment_method"></span>
                                </div>
                            </div>

                            <!-- Itemized Cost Breakdown -->
                            <div class="space-y-2">
                                <span class="font-bold text-slate-700 uppercase tracking-wider text-[11px] block">Itemized Cost Breakdown</span>
                                <div class="bg-slate-50 rounded-2xl border border-slate-100 p-3 space-y-2 max-h-40 overflow-y-auto">
                                    <template x-for="item in selectedClaim.items" :key="item.item_id">
                                        <div class="flex items-center justify-between border-b border-slate-200/60 pb-1.5 last:border-0 last:pb-0">
                                            <div>
                                                <span class="font-bold text-slate-800 block" x-text="item.item_name"></span>
                                                <span class="text-[10px] text-slate-400 font-mono" x-text="item.quantity + ' x RM ' + parseFloat(item.unit_price).toFixed(2)"></span>
                                            </div>
                                            <span class="font-bold font-mono text-slate-900" x-text="'RM ' + parseFloat(item.subtotal).toFixed(2)"></span>
                                        </div>
                                    </template>
                                    <template x-if="!selectedClaim.items || selectedClaim.items.length === 0">
                                        <div class="text-center text-slate-400 py-2">No individual breakdown items recorded.</div>
                                    </template>
                                </div>
                            </div>

                            <!-- Finance Bank Disbursement Audit Card -->
                            <div class="p-4 bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-2xl space-y-2 shadow-xs">
                                <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">
                                    <i class="fa-solid fa-building-columns mr-1"></i> Finance Bank Settlement Audit
                                </span>
                                <div class="grid grid-cols-2 gap-2 text-[11px] font-mono">
                                    <div>
                                        <span class="text-slate-400 block text-[9px]">Bank Reference ID</span>
                                        <span class="font-bold text-white break-all" x-text="selectedClaim.payment_reference || 'Pending Payout'"></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[9px]">Settlement Timestamp</span>
                                        <span class="font-bold text-white" x-text="selectedClaim.paid_at ? new Date(selectedClaim.paid_at).toLocaleString('en-MY') : 'In Queue'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Dual Images / Proof Viewers -->
                        <div class="space-y-4">
                            <!-- 1. Merchant Upload Receipt -->
                            <div class="space-y-1.5">
                                <span class="font-bold text-slate-700 uppercase tracking-wider text-[11px] block">
                                    <i class="fa-solid fa-receipt text-blue-600 mr-1"></i> Original Merchant Receipt
                                </span>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-2 max-h-56 overflow-hidden flex items-center justify-center relative group">
                                    <template x-if="selectedClaim.receipt_image_path">
                                        <div class="w-full h-full flex items-center justify-center cursor-zoom-in"
                                            @click="zoomImageUrl = '/storage/' + selectedClaim.receipt_image_path; zoomImageTitle = 'Original Merchant Receipt'; isZoomModalOpen = true;">
                                            <img :src="'/storage/' + selectedClaim.receipt_image_path" alt="Merchant Receipt"
                                                class="max-h-52 object-contain rounded-xl transition group-hover:scale-[1.02]">
                                            <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 flex items-center justify-center rounded-2xl transition text-white font-bold gap-1 text-xs backdrop-blur-3xs">
                                                <i class="fa-solid fa-magnifying-glass-plus"></i> Click to Zoom
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- 2. Finance Proof of Payment Slip (Smart PDF & Image Handler) -->
                            <div class="space-y-1.5">
                                <span class="font-bold text-slate-700 uppercase tracking-wider text-[11px] block">
                                    <i class="fa-solid fa-file-invoice-dollar text-emerald-600 mr-1"></i> Official Bank Transfer Proof
                                </span>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-2 min-h-36 max-h-56 overflow-hidden flex items-center justify-center relative group">
                                    
                                    <!-- Case A: Proof is a PDF Document -->
                                    <template x-if="selectedClaim.payment_proof_path && selectedClaim.payment_proof_path.toLowerCase().endsWith('.pdf')">
                                        <div class="w-full text-center p-4 space-y-2">
                                            <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center mx-auto text-xl border border-rose-100 shadow-3xs">
                                                <i class="fa-solid fa-file-pdf"></i>
                                            </div>
                                            <div>
                                                <span class="font-bold text-slate-800 block">Bank Transfer Slip (PDF)</span>
                                                <span class="text-[10px] text-slate-400 font-mono truncate max-w-xs block mx-auto" x-text="selectedClaim.payment_proof_path.split('/').pop()"></span>
                                            </div>
                                            <a :href="'/storage/' + selectedClaim.payment_proof_path" target="_blank"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition shadow-xs">
                                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> View / Download PDF
                                            </a>
                                        </div>
                                    </template>

                                    <!-- Case B: Proof is an Image (JPG/PNG) -->
                                    <template x-if="selectedClaim.payment_proof_path && !selectedClaim.payment_proof_path.toLowerCase().endsWith('.pdf')">
                                        <div class="w-full h-full flex items-center justify-center cursor-zoom-in"
                                            @click="zoomImageUrl = '/storage/' + selectedClaim.payment_proof_path; zoomImageTitle = 'Bank Transfer Slip'; isZoomModalOpen = true;">
                                            <img :src="'/storage/' + selectedClaim.payment_proof_path" alt="Bank Transfer Slip"
                                                class="max-h-52 object-contain rounded-xl transition group-hover:scale-[1.02]">
                                            <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 flex items-center justify-center rounded-2xl transition text-white font-bold gap-1 text-xs backdrop-blur-3xs">
                                                <i class="fa-solid fa-magnifying-glass-plus"></i> Click to Zoom
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Case C: No Proof Attached Yet -->
                                    <template x-if="!selectedClaim.payment_proof_path">
                                        <div class="p-8 text-center text-slate-400 font-medium text-xs">
                                            <i class="fa-solid fa-hourglass-half block text-2xl mb-1 text-slate-300"></i>
                                            Awaiting Finance bank payment slip upload.
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Total Disbursed Cost</span>
                            <span class="text-xl font-black text-slate-900 font-mono" x-text="'RM ' + parseFloat(selectedClaim.amount).toFixed(2)"></span>
                        </div>
                        <button type="button" @click="isDetailModalOpen = false"
                            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-xs cursor-pointer">
                            Close Window
                        </button>
                    </div>
                </div>
            </template>

        </div>
    </div>

    <!-- Interactive Lightbox Image Zoom Modal -->
    <div x-show="isZoomModalOpen" x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm transition-all duration-300">
        <div class="relative bg-white rounded-3xl p-4 max-w-2xl w-full shadow-2xl flex flex-col max-h-[90vh]"
            @click.away="isZoomModalOpen = false">
            
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-3">
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="fa-solid fa-receipt text-blue-600"></i> <span x-text="zoomImageTitle"></span>
                </span>
                <button type="button" @click="isZoomModalOpen = false"
                    class="text-slate-400 hover:text-rose-600 text-lg p-1 transition cursor-pointer">
                    <i class="fa-solid fa-circle-xmark"></i>
                </button>
            </div>

            <div class="flex-1 overflow-auto flex items-center justify-center p-2 bg-slate-50 rounded-2xl min-h-0">
                <img :src="zoomImageUrl" alt="Zoomed Document" class="max-w-full max-h-[75vh] object-contain rounded-xl shadow-xs">
            </div>
        </div>
    </div>

</body>
</html>