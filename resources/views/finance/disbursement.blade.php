<!DOCTYPE html>
<html lang="en" x-data="{ 
    isMobileSidebarOpen: false,
    isModalOpen: false, 
    isBatchModalOpen: false,
    isDetailModalOpen: false, 
    isZoomModalOpen: false,
    zoomImageUrl: '',
    zoomImageTitle: '',
    selectedClaim: null, 
    paymentRef: '',
    selectedBatchIds: [],

    toggleSelectAll(event, claims) {
        if (event.target.checked) {
            this.selectedBatchIds = claims.map(c => String(c.claim_id));
        } else {
            this.selectedBatchIds = [];
        }
    },

    get totalBatchAmount() {
        const claims = {{ json_encode($pendingDisbursements) }};
        return claims
            .filter(c => this.selectedBatchIds.map(String).includes(String(c.claim_id)))
            .reduce((sum, c) => sum + parseFloat(c.amount || 0), 0)
            .toFixed(2);
    }
}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartClaim - Payment Disbursement Desk</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased"
    :class="(isModalOpen || isBatchModalOpen || isDetailModalOpen || isZoomModalOpen || isMobileSidebarOpen) ? 'overflow-hidden lg:overflow-auto' : ''">

    <div class="min-h-screen flex flex-col lg:flex-row">

        <!-- Finance Sidebar Partial -->
        @include('layouts.partials.finance-sidebar')

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">



            <!-- Main Page Content -->
            <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full pb-24 lg:pb-8 overflow-y-auto space-y-6">

                <!-- Title & Actions -->
                <div class="border-b border-slate-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div>
                        <div>
                        <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Payment Disbursement Desk</h1>
                        <p class="text-xs md:text-sm text-slate-500">Execute electronic fund reimbursements individually or in batch with automated AI bank slip audit.</p>
                    </div>
                    <div class="hidden lg:flex items-center gap-3">
                        <x-system-clock />
                    </div>
                </div>

                    <!-- Dynamic Batch Payout Trigger Button -->
                    <div x-show="selectedBatchIds.length > 0" x-cloak class="flex items-center gap-2">
                        <button type="button" @click="isBatchModalOpen = true"
                            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs shadow-md transition flex items-center gap-2 cursor-pointer animate-bounce">
                            <i class="fa-solid fa-money-bill-transfer"></i>
                            Disburse Batch (<span x-text="selectedBatchIds.length"></span>) — RM <span x-text="totalBatchAmount"></span>
                        </button>
                    </div>
                </div>

                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-2xl flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if($errors->any())
                    <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold rounded-2xl space-y-1">
                        @foreach($errors->all() as $error)
                            <p class="flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-rose-500"></i> {{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <!-- KPI Metric Counters -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600 block">Pending Bank Settlement</span>
                            <h2 class="text-2xl font-black text-slate-900 font-mono mt-1">RM {{ number_format($totalPendingAmount, 2) }}</h2>
                            <span class="text-xs text-slate-400 font-medium">{{ count($pendingDisbursements) }} approved voucher(s) awaiting payout</span>
                        </div>
                        <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-money-bill-transfer"></i>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 block">Total Successfully Reimbursed</span>
                            <h2 class="text-2xl font-black text-slate-900 font-mono mt-1">RM {{ number_format($totalSettledAmount, 2) }}</h2>
                            <span class="text-xs text-slate-400 font-medium">{{ count($settledDisbursements) }} paid transaction logs</span>
                        </div>
                        <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                    </div>
                </div>

                <!-- Tab Switcher -->
                <div class="flex items-center gap-2 border-b border-slate-200 text-xs font-bold overflow-x-auto">
                    <a href="{{ route('finance.disbursement', ['tab' => 'pending']) }}" 
                        class="pb-3 px-3 border-b-2 whitespace-nowrap transition {{ $tab === 'pending' ? 'border-blue-600 text-blue-600 font-black' : 'border-transparent text-slate-400 hover:text-slate-700' }}">
                        <i class="fa-solid fa-clock-rotate-left mr-1"></i> Awaiting Payout ({{ count($pendingDisbursements) }})
                    </a>
                    <a href="{{ route('finance.disbursement', ['tab' => 'settled']) }}" 
                        class="pb-3 px-3 border-b-2 whitespace-nowrap transition {{ $tab === 'settled' ? 'border-emerald-600 text-emerald-600 font-black' : 'border-transparent text-slate-400 hover:text-slate-700' }}">
                        <i class="fa-solid fa-receipt mr-1"></i> Settled / Reimbursed History ({{ count($settledDisbursements) }})
                    </a>
                </div>

                <!-- Table 1: Pending Disbursement Payout List (With Checkbox for Batch) -->
                @if($tab === 'pending')
                    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-xs overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs min-w-[750px]">
                                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                                    <tr>
                                        <th class="p-3.5 w-10 text-center">
                                            <input type="checkbox" @change="toggleSelectAll($event, {{ json_encode($pendingDisbursements) }})" :checked="selectedBatchIds.length > 0 && selectedBatchIds.length === {{ count($pendingDisbursements) }}"
                                                class="rounded border-slate-300 text-blue-600 focus:ring-0 cursor-pointer">
                                        </th>
                                        <th class="p-3.5">Voucher ID & Merchant</th>
                                        <th class="p-3.5">Claimant Staff & Banking</th>
                                        <th class="p-3.5">Authorized Total</th>
                                        <th class="p-3.5">Approval Date</th>
                                        <th class="p-3.5 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-medium">
                                    @forelse($pendingDisbursements as $claim)
                                        <tr class="hover:bg-slate-50/50 cursor-pointer transition"
                                            @click="selectedClaim = {{ json_encode($claim->load(['items', 'user'])) }}; isDetailModalOpen = true;">
                                            <td class="p-3.5 text-center" @click.stop>
                                                <input type="checkbox" value="{{ $claim->claim_id }}" x-model="selectedBatchIds"
                                                    class="rounded border-slate-300 text-blue-600 focus:ring-0 cursor-pointer">
                                            </td>
                                            <td class="p-3.5">
                                                <span class="font-black text-slate-900 block font-mono">#CLM-{{ $claim->claim_id }}</span>
                                                <span class="text-[11px] text-slate-500 block truncate max-w-[150px] sm:max-w-xs">{{ $claim->claim_type === 'Mileage' ? ($claim->title ?? 'Mileage Allowance') : $claim->merchant_name }}</span>
                                            </td>
                                            <td class="p-3.5">
                                                <span class="font-bold text-slate-800 block truncate max-w-[150px] sm:max-w-xs">{{ $claim->user->name ?? 'Staff' }}</span>
                                                <span class="text-[10px] text-slate-400 font-mono truncate block max-w-[150px] sm:max-w-xs">
                                                    {{ $claim->user->bank_name ?? 'No Bank' }} — {{ $claim->user->bank_account_no ?? 'Missing' }}
                                                </span>
                                            </td>
                                            <td class="p-3.5">
                                                <span class="text-sm font-black text-slate-900 font-mono">RM {{ number_format($claim->amount, 2) }}</span>
                                            </td>
                                            <td class="p-3.5 text-slate-500 font-mono text-[11px]">
                                                {{ $claim->updated_at->format('d M Y, H:i') }}
                                            </td>
                                            <td class="p-3.5 text-right" @click.stop>
                                                <button type="button" @click="selectedClaim = {{ json_encode($claim->load('user')) }}; isModalOpen = true;"
                                                    class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-xs shadow-xs transition cursor-pointer flex items-center gap-1.5 ml-auto">
                                                    <i class="fa-solid fa-paper-plane text-[10px]"></i> Process Payout
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="p-8 text-center text-slate-400 font-medium">
                                                <i class="fa-solid fa-circle-check block text-2xl mb-2 text-emerald-400"></i>
                                                All approved claims have been completely settled. No pending payouts.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <!-- Table 2: Settled / Paid Out History -->
                    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-xs overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs min-w-[750px]">
                                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                                    <tr>
                                        <th class="p-3.5">Voucher ID & Merchant</th>
                                        <th class="p-3.5">Staff Recipient</th>
                                        <th class="p-3.5">Amount Disbursed</th>
                                        <th class="p-3.5">Bank Reference / Tx ID</th>
                                        <th class="p-3.5">Settlement Timestamp</th>
                                        <th class="p-3.5 text-right">Audit</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-medium">
                                    @forelse($settledDisbursements as $paid)
                                        <tr class="hover:bg-slate-50/50 cursor-pointer transition"
                                            @click="selectedClaim = {{ json_encode($paid->load(['items', 'user'])) }}; isDetailModalOpen = true;">
                                            <td class="p-3.5">
                                                <span class="font-black text-slate-900 block font-mono">#CLM-{{ $paid->claim_id }}</span>
                                                <span class="text-[11px] text-slate-500 block truncate max-w-[150px] sm:max-w-xs">{{ $paid->claim_type === 'Mileage' ? ($paid->title ?? 'Mileage Allowance') : $paid->merchant_name }}</span>
                                            </td>
                                            <td class="p-3.5">
                                                <span class="font-bold text-slate-800 block truncate max-w-[150px] sm:max-w-xs">{{ $paid->user->name ?? 'Staff' }}</span>
                                                <span class="text-[10px] text-slate-400 font-mono block truncate max-w-[150px] sm:max-w-xs">{{ $paid->user->email ?? 'N/A' }}</span>
                                            </td>
                                            <td class="p-3.5">
                                                <span class="text-sm font-black text-emerald-700 font-mono">RM {{ number_format($paid->amount, 2) }}</span>
                                            </td>
                                            <td class="p-3.5">
                                                <span class="px-2.5 py-1 bg-slate-100 border border-slate-200 rounded-lg text-slate-800 font-mono text-[11px] font-bold inline-block">
                                                    {{ $paid->payment_reference ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td class="p-3.5 text-slate-500 font-mono text-[11px]">
                                                {{ $paid->paid_at ? \Carbon\Carbon::parse($paid->paid_at)->format('d M Y, H:i') : $paid->updated_at->format('d M Y, H:i') }}
                                            </td>
                                            <td class="p-3.5 text-right">
                                                <button type="button" class="text-blue-600 hover:text-blue-800 font-bold text-xs inline-flex items-center">
                                                    Details <i class="fa-solid fa-chevron-right text-[10px] ml-1"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="p-8 text-center text-slate-400 font-medium">
                                                <i class="fa-solid fa-folder-open block text-2xl mb-2 text-slate-300"></i>
                                                No settled reimbursement records found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

            </main>
        </div>
    </div>

    <!-- 1. Forensic Audit Detail Modal (Dual Resource Layout) -->
    <div x-show="isDetailModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl p-5 sm:p-6 max-w-4xl w-full shadow-2xl border border-slate-100 space-y-6 max-h-[90vh] overflow-y-auto"
            @click.away="isDetailModalOpen = false">

            <template x-if="selectedClaim">
                <div class="space-y-6">
                    <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block" x-text="'CLAIM ID: CLM-' + selectedClaim.claim_id"></span>
                            <h2 class="text-lg sm:text-xl font-black text-slate-900" x-text="selectedClaim.claim_type === 'Mileage' ? (selectedClaim.title || 'Mileage Allowance') : selectedClaim.merchant_name"></h2>
                        </div>
                        <span class="px-3 py-1 rounded-full font-bold text-xs uppercase tracking-wider"
                            :class="selectedClaim.status === 'Reimbursed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                            x-text="selectedClaim.status"></span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Invoice / Type</span>
                                    <span class="font-bold text-slate-800 font-mono" x-text="selectedClaim.receipt_invoice_no || selectedClaim.claim_type"></span>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Transaction Date</span>
                                    <span class="font-bold text-slate-800 font-mono" x-text="selectedClaim.transaction_date || 'N/A'"></span>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Claimant Staff</span>
                                    <span class="font-bold text-slate-800" x-text="selectedClaim.user ? selectedClaim.user.name : 'Staff'"></span>
                                </div>
                                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase block">Staff Bank Details</span>
                                    <span class="font-bold text-slate-800 font-mono text-[11px]" x-text="(selectedClaim.user?.bank_name || 'N/A') + ' ' + (selectedClaim.user?.bank_account_no || '')"></span>
                                </div>
                            </div>

                            <!-- Cost Breakdown -->
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
                                        <div class="text-center text-slate-400 py-2">No individual items (Single/Mileage Voucher).</div>
                                    </template>
                                </div>
                            </div>

                            <!-- Bank Audit Card -->
                            <div class="p-4 bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-2xl space-y-2 shadow-xs">
                                <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">
                                    <i class="fa-solid fa-building-columns mr-1"></i> Official Bank Settlement Audit
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
                            <!-- 1. Merchant Receipt -->
                            <div class="space-y-1.5">
                                <span class="font-bold text-slate-700 uppercase tracking-wider text-[11px] block">
                                    <i class="fa-solid fa-receipt text-blue-600 mr-1"></i> Original Merchant Receipt
                                </span>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-2 max-h-56 overflow-hidden flex items-center justify-center relative group">
                                    <template x-if="selectedClaim.receipt_image_path">
                                        <div class="w-full h-full flex items-center justify-center cursor-zoom-in"
                                            @click="zoomImageUrl = '/files/' + selectedClaim.receipt_image_path; zoomImageTitle = 'Original Merchant Receipt'; isZoomModalOpen = true;">
                                            <img :src="'/files/' + selectedClaim.receipt_image_path" alt="Merchant Receipt"
                                                class="max-h-52 object-contain rounded-xl transition group-hover:scale-[1.02]">
                                            <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 flex items-center justify-center rounded-2xl transition text-white font-bold gap-1 text-xs backdrop-blur-3xs">
                                                <i class="fa-solid fa-magnifying-glass-plus"></i> Click to Zoom
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="!selectedClaim.receipt_image_path">
                                        <div class="p-8 text-center text-slate-400 font-medium text-xs">
                                            <i class="fa-solid fa-image-slash block text-2xl mb-1 text-slate-300"></i> No merchant receipt attached.
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- 2. Finance Proof of Payment Slip -->
                            <div class="space-y-1.5">
                                <span class="font-bold text-slate-700 uppercase tracking-wider text-[11px] block">
                                    <i class="fa-solid fa-file-invoice-dollar text-emerald-600 mr-1"></i> Official Bank Transfer Proof
                                </span>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-2 min-h-36 max-h-56 overflow-hidden flex items-center justify-center relative group">
                                    <template x-if="selectedClaim.payment_proof_path && selectedClaim.payment_proof_path.toLowerCase().endsWith('.pdf')">
                                        <div class="w-full text-center p-4 space-y-2">
                                            <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center mx-auto text-xl border border-rose-100 shadow-3xs">
                                                <i class="fa-solid fa-file-pdf"></i>
                                            </div>
                                            <div>
                                                <span class="font-bold text-slate-800 block">Bank Transfer Slip (PDF)</span>
                                                <span class="text-[10px] text-slate-400 font-mono truncate max-w-xs block mx-auto" x-text="selectedClaim.payment_proof_path.split('/').pop()"></span>
                                            </div>
                                            <a :href="'/files/' + selectedClaim.payment_proof_path" target="_blank"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition shadow-xs">
                                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> View / Download PDF
                                            </a>
                                        </div>
                                    </template>

                                    <template x-if="selectedClaim.payment_proof_path && !selectedClaim.payment_proof_path.toLowerCase().endsWith('.pdf')">
                                        <div class="w-full h-full flex items-center justify-center cursor-zoom-in"
                                            @click="zoomImageUrl = '/files/' + selectedClaim.payment_proof_path; zoomImageTitle = 'Bank Transfer Slip'; isZoomModalOpen = true;">
                                            <img :src="'/files/' + selectedClaim.payment_proof_path" alt="Bank Transfer Slip"
                                                class="max-h-52 object-contain rounded-xl transition group-hover:scale-[1.02]">
                                            <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 flex items-center justify-center rounded-2xl transition text-white font-bold gap-1 text-xs backdrop-blur-3xs">
                                                <i class="fa-solid fa-magnifying-glass-plus"></i> Click to Zoom
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="!selectedClaim.payment_proof_path">
                                        <div class="p-8 text-center text-slate-400 font-medium text-xs">
                                            <i class="fa-solid fa-hourglass-half block text-2xl mb-1 text-slate-300"></i> Awaiting Finance payment slip upload.
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

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

    <!-- 2. Lightbox Zoom Modal -->
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

    <!-- 3. Smart AI Bank Settlement Modal (Individual Single Payout) -->
    <div x-show="isModalOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl p-5 sm:p-6 max-w-lg w-full shadow-2xl border border-slate-100 space-y-4 max-h-[90vh] overflow-y-auto"
            x-data="{ isScanningSlip: false, slipPreview: '', slipRef: '', slipTime: '' }"
            @click.away="isModalOpen = false">

            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-black text-slate-900 uppercase">Single Voucher Reimbursement</h3>
                <button type="button" @click="isModalOpen = false" class="text-slate-400 hover:text-rose-600 cursor-pointer">
                    <i class="fa-solid fa-circle-xmark text-lg"></i>
                </button>
            </div>

            <template x-if="selectedClaim">
                <form :action="'/finance/disbursement/' + selectedClaim.claim_id + '/settle'" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs" x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100 space-y-1">
                        <span class="text-[10px] text-slate-400 font-bold uppercase block">Payout Beneficiary & Amount</span>
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-bold text-slate-800 block" x-text="selectedClaim.user ? selectedClaim.user.name : 'Staff'"></span>
                                <span class="text-[10px] text-slate-500 font-mono" x-text="(selectedClaim.user?.bank_name || 'N/A') + ' - ' + (selectedClaim.user?.bank_account_no || 'Missing')"></span>
                            </div>
                            <span class="text-base font-black text-emerald-600 font-mono"
                                x-text="'RM ' + parseFloat(selectedClaim.amount).toFixed(2)"></span>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block font-bold text-slate-700">
                            <i class="fa-solid fa-receipt text-emerald-600 mr-1"></i> Upload Bank Transfer Slip (Mandatory) *
                        </label>
                        <input type="file" name="payment_proof" required accept="image/*,.pdf" @change="
                                const f = $event.target.files[0];
                                if(f) {
                                    slipPreview = URL.createObjectURL(f);
                                    isScanningSlip = true;
                                    let fd = new FormData();
                                    fd.append('payment_proof', f);
                                    fd.append('_token', '{{ csrf_token() }}');
                                    fetch('{{ route('finance.disbursement.scan_slip') }}', {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json'
                                        },
                                        body: fd
                                    })
                                        .then(r => r.json())
                                        .then(res => {
                                            isScanningSlip = false;
                                            if(res.success) {
                                                slipRef = res.payment_reference;
                                                slipTime = res.transfer_time;
                                            }
                                        })
                                        .catch(() => { isScanningSlip = false; });
                                }
                            "
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs text-slate-600 file:mr-3 file:py-1 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-600 file:text-white cursor-pointer">
                    </div>

                    <div x-show="isScanningSlip"
                        class="p-2.5 bg-emerald-50 text-emerald-700 rounded-xl flex items-center gap-2 font-bold text-xs animate-pulse">
                        <i class="fa-solid fa-brain"></i> AI Scanning bank slip for Reference ID & Timestamp...
                    </div>

                    <div class="space-y-1.5">
                        <label class="block font-bold text-slate-700">Bank Reference ID *</label>
                        <input type="text" name="payment_reference" x-model="slipRef" required
                            placeholder="e.g. MBB-TXN-984210 / DuitNow Ref"
                            class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl outline-none font-mono font-bold text-slate-800 text-xs">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button type="button" @click="isModalOpen = false"
                            class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-xl text-xs hover:bg-slate-200 transition cursor-pointer">Cancel</button>
                        <button type="submit" :disabled="loading" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-xs cursor-pointer transition disabled:opacity-50"><span x-show="!loading"><i class="fa-solid fa-check mr-1"></i> Settle & Disburse</span><span x-show="loading"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Processing...</span>
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

    <!-- 4. Batch Disbursement Settlement Modal (Multiple Vouchers, 1 Proof Slip) -->
    <div x-show="isBatchModalOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl p-5 sm:p-6 max-w-lg w-full shadow-2xl border border-slate-100 space-y-4 max-h-[90vh] overflow-y-auto"
            x-data="{ isScanningSlip: false, slipPreview: '', slipRef: '', slipTime: '' }"
            @click.away="isBatchModalOpen = false">

            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-sm font-black text-slate-900 uppercase">Execute Batch Reimbursement</h3>
                    <p class="text-[11px] text-slate-400">Upload 1 official corporate bank transfer slip for all selected vouchers.</p>
                </div>
                <button type="button" @click="isBatchModalOpen = false" class="text-slate-400 hover:text-rose-600 cursor-pointer">
                    <i class="fa-solid fa-circle-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('finance.disbursement.batch_settle') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs" x-data="{ loading: false }" @submit="loading = true">
                @csrf
                <div class="p-3.5 bg-blue-50/60 rounded-2xl border border-blue-100 space-y-1.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] text-blue-600 font-bold uppercase">Selected Batch Queue</span>
                        <span class="px-2 py-0.5 rounded-full bg-blue-600 text-white font-mono font-bold text-[10px]" x-text="selectedBatchIds.length + ' Vouchers'"></span>
                    </div>
                    <div class="flex items-center justify-between pt-1 border-t border-blue-200/60">
                        <span class="text-slate-700 font-bold">Total Batch Disbursement:</span>
                        <span class="text-base font-black text-blue-900 font-mono">RM <span x-text="totalBatchAmount"></span></span>
                    </div>
                </div>

                <!-- Hidden Input Arrays for Selected IDs -->
                <template x-for="id in selectedBatchIds" :key="id">
                    <input type="hidden" name="claim_ids[]" :value="id">
                </template>

                <!-- Mandatory Batch Transfer Slip Upload -->
                <div class="space-y-1.5">
                    <label class="block font-bold text-slate-700">
                        <i class="fa-solid fa-receipt text-emerald-600 mr-1"></i> Upload Corporate Batch Transfer Slip (PDF / Image) *
                    </label>
                    <input type="file" name="payment_proof" required accept="image/*,.pdf" @change="
                            const f = $event.target.files[0];
                            if(f) {
                                isScanningSlip = true;
                                let fd = new FormData();
                                fd.append('payment_proof', f);
                                fd.append('_token', '{{ csrf_token() }}');
                                fetch('{{ route('finance.disbursement.scan_slip') }}', {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    },
                                    body: fd
                                })
                                    .then(r => r.json())
                                    .then(res => {
                                        isScanningSlip = false;
                                        if(res.success) {
                                            slipRef = res.payment_reference;
                                            slipTime = res.transfer_time;
                                        }
                                    })
                                    .catch(() => { isScanningSlip = false; });
                            }
                        "
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs text-slate-600 file:mr-3 file:py-1 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-600 file:text-white cursor-pointer">
                </div>

                <!-- AI OCR Status Pill -->
                <div x-show="isScanningSlip"
                    class="p-2.5 bg-emerald-50 text-emerald-700 rounded-xl flex items-center gap-2 font-bold text-xs animate-pulse">
                    <i class="fa-solid fa-brain"></i> AI Extracting Corporate Giro / Batch Reference...
                </div>

                <!-- Batch Reference ID -->
                <div class="space-y-1.5">
                    <label class="block font-bold text-slate-700">Corporate Batch Reference / Giro ID *</label>
                    <input type="text" name="payment_reference" x-model="slipRef" required
                        placeholder="e.g. BATCH-PAY-20260825 / GIRO-MBB-98123"
                        class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl outline-none font-mono font-bold text-slate-800 text-xs">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="isBatchModalOpen = false"
                        class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-xl text-xs hover:bg-slate-200 transition cursor-pointer">Cancel</button>
                    <button type="submit" :disabled="loading" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-xs cursor-pointer transition disabled:opacity-50"><span x-show="!loading"><i class="fa-solid fa-check mr-1"></i> Disburse All (<span x-text="selectedBatchIds.length"></span>) Vouchers</span><span x-show="loading"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Processing...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>

</html>


