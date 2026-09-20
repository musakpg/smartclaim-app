<!DOCTYPE html>
<html lang="en" x-data="{ 
    isMobileSidebarOpen: false, 
    isStaffModalOpen: false, 
    isZoomModalOpen: false,
    zoomImageUrl: '',
    zoomImageTitle: '',
    selectedStaff: null,
    inspectingClaim: null 
}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Executive BI Reports & Analytics</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased"
    :class="(isStaffModalOpen || isZoomModalOpen || isMobileSidebarOpen) ? 'overflow-hidden' : ''">

    <div class="flex min-h-screen">
        @include('layouts.partials.manager-sidebar')

        <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full pb-24 overflow-y-auto space-y-6">

            <!-- Header & Action Filter -->
            <div class="border-b border-slate-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Executive BI & Analytics
                    </h1>
                    <p class="text-xs md:text-sm text-slate-500">Corporate expenditure metrics, departmental trends, and
                        budgetary reporting.</p>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('manager.export.claims_csv') }}?status=Approved"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center gap-2 transition cursor-pointer">
                        <i class="fa-solid fa-file-csv"></i> Export Approved Claims (CSV)
                    </a>

                    <form method="GET" action="{{ route('manager.reports') }}" class="flex items-center gap-2">
                        <select name="year" onchange="this.form.submit()"
                            class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none cursor-pointer">
                            @foreach(range(date('Y'), date('Y') - 3) as $y)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>Year {{ $y }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>

            <!-- KPI Metric Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Approved
                        Expenditure</span>
                    <h3 class="text-2xl font-black font-mono text-emerald-600">
                        RM {{ number_format($totalApprovedRM, 2) }}
                    </h3>
                    <p class="text-[11px] text-slate-400">Accumulated for FY{{ $year }}</p>
                </div>

                <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pending Approvals
                        Volume</span>
                    <h3 class="text-2xl font-black font-mono text-amber-500">
                        RM {{ number_format($totalPendingRM, 2) }}
                    </h3>
                    <p class="text-[11px] text-slate-400">Claims awaiting authorization</p>
                </div>

                <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Authorized Claims
                        Count</span>
                    <h3 class="text-2xl font-black font-mono text-blue-600">
                        {{ $totalApprovedCount }} <span
                            class="text-xs font-sans font-bold text-slate-500">Transactions</span>
                    </h3>
                    <p class="text-[11px] text-slate-400">Total processed volume</p>
                </div>
            </div>

            <!-- Visual Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Line Chart: Monthly Trend -->
                <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-4 lg:col-span-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-chart-area text-blue-600"></i> Monthly Expenditure Velocity (RM)
                    </h3>
                    <div class="h-64">
                        <canvas id="monthlyTrendChart"></canvas>
                    </div>
                </div>

                <!-- Doughnut Chart: Category Share -->
                <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-chart-pie text-emerald-600"></i> Expense Category Breakdown
                    </h3>
                    <div class="h-64 flex items-center justify-center">
                        <canvas id="categoryDonutChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top Spending Personnel Table with Interactive Inspection Drilldown -->
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-xs overflow-hidden">
                <div
                    class="p-4 border-b border-slate-100 font-bold text-xs text-slate-800 flex items-center justify-between">
                    <span class="uppercase tracking-wider">Top Staff Claimants (FY{{ $year }})</span>
                    <span class="text-[11px] text-slate-400 font-normal">Click any staff row to inspect claim
                        breakdown</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs min-w-[500px]">
                        <thead
                            class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="p-3.5">Staff Name</th>
                                <th class="p-3.5">Role / Designation</th>
                                <th class="p-3.5 text-center">Approved Claims</th>
                                <th class="p-3.5 text-right">Total Disbursed</th>
                                <th class="p-3.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @forelse($topStaff as $staff)
                                <tr class="hover:bg-slate-50/60 cursor-pointer transition"
                                    @click="selectedStaff = {{ json_encode($staff) }}; inspectingClaim = null; isStaffModalOpen = true;">
                                    <td class="p-3.5 font-bold text-slate-900 flex items-center gap-2">
                                        <div
                                            class="w-7 h-7 rounded-full bg-blue-100 text-blue-700 font-black text-[10px] flex items-center justify-center">
                                            {{ substr($staff->name, 0, 2) }}
                                        </div>
                                        <span>{{ $staff->name }}</span>
                                    </td>
                                    <td class="p-3.5 text-slate-500 capitalize">{{ $staff->user_role ?? 'Staff' }}</td>
                                    <td class="p-3.5 text-center font-mono font-bold text-slate-700">
                                        <span class="px-2 py-0.5 bg-slate-100 rounded-md">{{ $staff->total_claims }}</span>
                                    </td>
                                    <td class="p-3.5 text-right font-mono font-black text-emerald-600">
                                        RM {{ number_format($staff->total_spent, 2) }}
                                    </td>
                                    <td class="p-3.5 text-right">
                                        <button type="button" class="text-blue-600 hover:text-blue-800 font-bold text-xs">
                                            Inspect <i class="fa-solid fa-chevron-right text-[10px] ml-0.5"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-slate-400">
                                        No authorized claim transactions recorded for this period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- Master Inspection Modal (Level 1: Staff Claims List | Level 2: Voucher Forensic Audit) -->
    <div x-show="isStaffModalOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl p-6 max-w-4xl w-full shadow-2xl border border-slate-100 space-y-5 max-h-[90vh] overflow-y-auto"
            @click.away="isStaffModalOpen = false">

            <!-- LEVEL 1 VIEW: List of Approved Claims by Staff -->
            <template x-if="selectedStaff && !inspectingClaim">
                <div class="space-y-5">
                    <!-- Staff Header Card -->
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-2xl bg-blue-600 text-white font-black text-xs flex items-center justify-center shadow-xs">
                                <span x-text="selectedStaff.name.substring(0, 2).toUpperCase()"></span>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-slate-900" x-text="selectedStaff.name"></h3>
                                <p class="text-[11px] text-slate-400"
                                    x-text="'Role: ' + selectedStaff.user_role + ' | Total Spent: RM ' + parseFloat(selectedStaff.total_spent).toFixed(2)">
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="isStaffModalOpen = false"
                            class="text-slate-400 hover:text-rose-600 text-lg">
                            <i class="fa-solid fa-circle-xmark"></i>
                        </button>
                    </div>

                    <!-- Individual Claims List -->
                    <div class="space-y-2">
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider block">
                            Historical Vouchers Approved (FY{{ $year }})
                        </span>
                        <p class="text-[11px] text-slate-400">Click on any claim voucher below to examine itemized
                            receipt breakdown & bank disbursement proof.</p>

                        <div class="border border-slate-200 rounded-2xl overflow-hidden bg-slate-50/50 mt-2">
                            <table class="w-full text-left text-xs">
                                <thead
                                    class="bg-slate-100 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                                    <tr>
                                        <th class="p-3">Claim ID</th>
                                        <th class="p-3">Merchant / Particulars</th>
                                        <th class="p-3">Category</th>
                                        <th class="p-3">Date</th>
                                        <th class="p-3 text-right">Amount</th>
                                        <th class="p-3 text-center">Status</th>
                                        <th class="p-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200/60 font-medium">
                                    <template x-for="claim in selectedStaff.claims_list" :key="claim.claim_id">
                                        <tr class="hover:bg-white transition cursor-pointer"
                                            @click="inspectingClaim = claim">
                                            <td class="p-3 font-mono font-bold text-slate-900"
                                                x-text="'#CLM-' + claim.claim_id"></td>
                                            <td class="p-3">
                                                <span class="font-bold text-slate-800 block"
                                                    x-text="claim.merchant_name"></span>
                                                <span class="text-[10px] text-slate-400 font-mono"
                                                    x-text="'Inv: ' + (claim.receipt_invoice_no || 'N/A')"></span>
                                            </td>
                                            <td class="p-3">
                                                <span
                                                    class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md font-semibold text-[10px]"
                                                    x-text="claim.predicted_category"></span>
                                            </td>
                                            <td class="p-3 text-slate-500 font-mono text-[11px]"
                                                x-text="claim.transaction_date ? claim.transaction_date.substring(0, 10) : 'N/A'">
                                            </td>
                                            <td class="p-3 text-right font-mono font-bold text-slate-900"
                                                x-text="'RM ' + parseFloat(claim.amount).toFixed(2)"></td>
                                            <td class="p-3 text-center">
                                                <span
                                                    class="px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wide"
                                                    :class="claim.status === 'Reimbursed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-teal-50 text-teal-700 border border-teal-200'"
                                                    x-text="claim.status"></span>
                                            </td>
                                            <td class="p-3 text-right">
                                                <span class="text-blue-600 font-bold text-[11px]">View Receipt →</span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex items-center justify-between pt-3 border-t border-slate-100 text-xs">
                        <span class="text-slate-400"
                            x-text="selectedStaff.claims_list.length + ' approved transaction record(s)'"></span>
                        <button type="button" @click="isStaffModalOpen = false"
                            class="px-4 py-2 bg-slate-900 text-white font-bold rounded-xl text-xs cursor-pointer">
                            Close Inspection
                        </button>
                    </div>
                </div>
            </template>

            <!-- LEVEL 2 VIEW: Detailed Forensic Audit with Dual Slips -->
            <template x-if="inspectingClaim">
                <div class="space-y-5">
                    <!-- Top Navigation Bar (Back to Staff List) -->
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <button type="button" @click="inspectingClaim = null"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer">
                            <i class="fa-solid fa-arrow-left"></i> Back to <span x-text="selectedStaff.name"></span>
                            Claims
                        </button>
                        <span class="px-3 py-1 rounded-full font-bold text-xs uppercase tracking-wider"
                            :class="inspectingClaim.status === 'Reimbursed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                            x-text="inspectingClaim.status"></span>
                    </div>

                    <!-- Claim Header Banner -->
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block"
                                x-text="'CLAIM ID: CLM-' + inspectingClaim.claim_id"></span>
                            <h2 class="text-xl font-black text-slate-900" x-text="inspectingClaim.merchant_name"></h2>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Claim Cost</span>
                            <span class="text-xl font-black text-emerald-600 font-mono"
                                x-text="'RM ' + parseFloat(inspectingClaim.amount).toFixed(2)"></span>
                        </div>
                    </div>

                    <!-- 2-Column Split Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">

                        <!-- Left Column: Metadata, Itemized Breakdown & Settlement Info -->
                        <div class="space-y-4">
                            <!-- Quick Grid Data -->
                            <div class="grid grid-cols-2 gap-2.5">
                                <div class="p-2.5 bg-slate-50 rounded-2xl border border-slate-100">
                                    <span class="text-[9px] font-bold text-slate-400 uppercase block">Invoice No</span>
                                    <span class="font-bold text-slate-800 font-mono text-[11px]"
                                        x-text="inspectingClaim.receipt_invoice_no || 'N/A'"></span>
                                </div>
                                <div class="p-2.5 bg-slate-50 rounded-2xl border border-slate-100">
                                    <span class="text-[9px] font-bold text-slate-400 uppercase block">Transaction
                                        Date</span>
                                    <span class="font-bold text-slate-800 font-mono text-[11px]"
                                        x-text="inspectingClaim.transaction_date ? inspectingClaim.transaction_date.substring(0, 10) : 'N/A'"></span>
                                </div>
                                <div class="p-2.5 bg-slate-50 rounded-2xl border border-slate-100">
                                    <span class="text-[9px] font-bold text-slate-400 uppercase block">Category</span>
                                    <span class="font-bold text-slate-800"
                                        x-text="inspectingClaim.predicted_category"></span>
                                </div>
                                <div class="p-2.5 bg-slate-50 rounded-2xl border border-slate-100">
                                    <span class="text-[9px] font-bold text-slate-400 uppercase block">Payment
                                        Method</span>
                                    <span class="font-bold text-slate-800"
                                        x-text="inspectingClaim.payment_method"></span>
                                </div>
                            </div>

                            <!-- Itemized Cost Breakdown -->
                            <div class="space-y-1.5">
                                <span
                                    class="font-bold text-slate-700 uppercase tracking-wider text-[10px] block">Itemized
                                    Cost Breakdown</span>
                                <div
                                    class="bg-slate-50 rounded-2xl border border-slate-100 p-3 space-y-2 max-h-36 overflow-y-auto">
                                    <template x-for="item in inspectingClaim.items" :key="item.item_id">
                                        <div
                                            class="flex items-center justify-between border-b border-slate-200/60 pb-1.5 last:border-0 last:pb-0">
                                            <div>
                                                <span class="font-bold text-slate-800 block"
                                                    x-text="item.item_name"></span>
                                                <span class="text-[10px] text-slate-400 font-mono"
                                                    x-text="item.quantity + ' x RM ' + parseFloat(item.unit_price).toFixed(2)"></span>
                                            </div>
                                            <span class="font-bold font-mono text-slate-900"
                                                x-text="'RM ' + parseFloat(item.subtotal).toFixed(2)"></span>
                                        </div>
                                    </template>
                                    <template x-if="!inspectingClaim.items || inspectingClaim.items.length === 0">
                                        <div class="text-center text-slate-400 py-2 text-[11px]">No individual breakdown
                                            items recorded.</div>
                                    </template>
                                </div>
                            </div>

                            <!-- Finance Bank Settlement Proof Audit Card -->
                            <div
                                class="p-3.5 bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-2xl space-y-1.5 shadow-xs">
                                <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">
                                    <i class="fa-solid fa-building-columns mr-1"></i> Finance Bank Settlement Audit
                                </span>
                                <div class="grid grid-cols-2 gap-2 text-[11px] font-mono">
                                    <div>
                                        <span class="text-slate-400 block text-[9px]">Bank Reference ID</span>
                                        <span class="font-bold text-white break-all"
                                            x-text="inspectingClaim.payment_reference || 'Pending Payout'"></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[9px]">Settlement Timestamp</span>
                                        <span class="font-bold text-white"
                                            x-text="inspectingClaim.paid_at ? new Date(inspectingClaim.paid_at).toLocaleString('en-MY') : 'In Queue'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Original Receipt Image & Bank Transfer Proof Slip -->
                        <div class="space-y-4">
                            <!-- 1. Original Merchant Receipt -->
                            <div class="space-y-1.5">
                                <span class="font-bold text-slate-700 uppercase tracking-wider text-[10px] block">
                                    <i class="fa-solid fa-receipt text-blue-600 mr-1"></i> Original Merchant Receipt
                                </span>
                                <div
                                    class="rounded-2xl border border-slate-200 bg-slate-50 p-2 max-h-48 overflow-hidden flex items-center justify-center relative group">
                                    <template x-if="inspectingClaim.receipt_image_path">
                                        <div class="w-full h-full flex items-center justify-center cursor-zoom-in"
                                            @click="zoomImageUrl = '/storage/' + inspectingClaim.receipt_image_path; zoomImageTitle = 'Original Merchant Receipt'; isZoomModalOpen = true;">
                                            <img :src="'/storage/' + inspectingClaim.receipt_image_path"
                                                alt="Merchant Receipt"
                                                class="max-h-44 object-contain rounded-xl transition group-hover:scale-[1.02]">
                                            <div
                                                class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 flex items-center justify-center rounded-2xl transition text-white font-bold gap-1 text-xs backdrop-blur-3xs">
                                                <i class="fa-solid fa-magnifying-glass-plus"></i> Zoom Receipt
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- 2. Finance Bank Transfer Slip -->
                            <div class="space-y-1.5">
                                <span class="font-bold text-slate-700 uppercase tracking-wider text-[10px] block">
                                    <i class="fa-solid fa-file-invoice-dollar text-emerald-600 mr-1"></i> Official Bank
                                    Transfer Slip (Finance Proof)
                                </span>
                                <div
                                    class="rounded-2xl border border-slate-200 bg-slate-50 p-2 min-h-32 max-h-48 overflow-hidden flex items-center justify-center relative group">
                                    <!-- PDF Format -->
                                    <template
                                        x-if="inspectingClaim.payment_proof_path && inspectingClaim.payment_proof_path.toLowerCase().endsWith('.pdf')">
                                        <div class="w-full text-center p-3 space-y-1.5">
                                            <div
                                                class="w-10 h-10 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center mx-auto text-lg border border-rose-100">
                                                <i class="fa-solid fa-file-pdf"></i>
                                            </div>
                                            <span class="font-bold text-slate-800 block text-xs">Bank Transfer Slip
                                                (PDF)</span>
                                            <a :href="'/storage/' + inspectingClaim.payment_proof_path" target="_blank"
                                                class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-[11px] transition shadow-xs">
                                                <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i> View /
                                                Download PDF
                                            </a>
                                        </div>
                                    </template>

                                    <!-- Image Format -->
                                    <template
                                        x-if="inspectingClaim.payment_proof_path && !inspectingClaim.payment_proof_path.toLowerCase().endsWith('.pdf')">
                                        <div class="w-full h-full flex items-center justify-center cursor-zoom-in"
                                            @click="zoomImageUrl = '/storage/' + inspectingClaim.payment_proof_path; zoomImageTitle = 'Bank Transfer Slip'; isZoomModalOpen = true;">
                                            <img :src="'/storage/' + inspectingClaim.payment_proof_path"
                                                alt="Bank Transfer Slip"
                                                class="max-h-44 object-contain rounded-xl transition group-hover:scale-[1.02]">
                                            <div
                                                class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 flex items-center justify-center rounded-2xl transition text-white font-bold gap-1 text-xs backdrop-blur-3xs">
                                                <i class="fa-solid fa-magnifying-glass-plus"></i> Zoom Slip
                                            </div>
                                        </div>
                                    </template>

                                    <!-- No Proof Attached -->
                                    <template x-if="!inspectingClaim.payment_proof_path">
                                        <div class="p-6 text-center text-slate-400 font-medium text-xs">
                                            <i class="fa-solid fa-hourglass-half block text-xl mb-1 text-slate-300"></i>
                                            Awaiting Finance bank payment slip upload.
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Bottom Action Controls -->
                    <div class="flex items-center justify-end pt-3 border-t border-slate-100">
                        <button type="button" @click="isStaffModalOpen = false; inspectingClaim = null;"
                            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-xs cursor-pointer">
                            Close Window
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Lightbox Zoom Modal -->
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
                <img :src="zoomImageUrl" alt="Zoomed Document"
                    class="max-w-full max-h-[75vh] object-contain rounded-xl shadow-xs">
            </div>
        </div>
    </div>

    <!-- Chart.js Engine -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Monthly Trend Line Chart
            const ctxLine = document.getElementById('monthlyTrendChart').getContext('2d');
            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Expenditure (RM)',
                        data: @json($monthlyData),
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.08)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2.5,
                        pointRadius: 4,
                        pointBackgroundColor: '#2563eb'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                        x: { grid: { display: false } }
                    }
                }
            });

            // 2. Category Share Donut Chart
            const ctxDonut = document.getElementById('categoryDonutChart').getContext('2d');
            new Chart(ctxDonut, {
                type: 'doughnut',
                data: {
                    labels: @json($categoryLabels),
                    datasets: [{
                        data: @json($categoryTotals),
                        backgroundColor: ['#059669', '#2563eb', '#f59e0b', '#dc2626', '#8b5cf6'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } }
                    },
                    cutout: '70%'
                }
            });
        });
    </script>
</body>

</html>