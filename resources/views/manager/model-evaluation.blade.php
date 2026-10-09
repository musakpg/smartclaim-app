@extends('layouts.manager')

@section('title', 'SmartClaim - AI Performance & Model Evaluation Dashboard')

@section('content')
<div x-data="{ isUploadModalOpen: false, exportDropdownOpen: false, activeTab: 'feedback' }" class="space-y-6">

    <!-- Universal Demo Mode Indicator -->
    @include('layouts.partials.demo-banner')

    <!-- Header & Action Ribbon -->
    <div class="border-b border-slate-200 pb-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 shadow-3xs">
                    <i class="fa-solid fa-brain text-sm"></i>
                </div>
                <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">AI & Model Evaluation Dashboard</h1>
            </div>
            <p class="text-xs md:text-sm text-slate-500 mt-1">
                Real-time OCR accuracy benchmarks, confusion matrix telemetry, discrepancy feedback logs, and active learning pipelines.
            </p>
        </div>

        <div class="flex items-center flex-wrap gap-2.5">
            <!-- Retraining Dataset Export Dropdown -->
            <div class="relative" @click.outside="exportDropdownOpen = false">
                <button type="button" @click="exportDropdownOpen = !exportDropdownOpen"
                    class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-white border border-slate-200 hover:border-slate-300 text-slate-700 text-xs font-bold rounded-xl transition shadow-3xs cursor-pointer">
                    <i class="fa-solid fa-download text-emerald-600"></i>
                    <span>Export Dataset</span>
                    <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
                </button>

                <div x-show="exportDropdownOpen" x-cloak x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 mt-2 w-52 bg-white rounded-2xl shadow-xl border border-slate-100 py-1.5 z-50">
                    <div class="px-3 py-1.5 border-b border-slate-100 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        Retraining Datasets
                    </div>
                    <a href="{{ route('manager.model-evaluation.export', ['format' => 'csv']) }}"
                        class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        <i class="fa-solid fa-file-csv text-emerald-600 text-sm"></i>
                        <div>
                            <div class="text-slate-800">Export as CSV</div>
                            <div class="text-[10px] text-slate-400 font-normal">Spreadsheet / ML table</div>
                        </div>
                    </a>
                    <a href="{{ route('manager.model-evaluation.export', ['format' => 'json']) }}"
                        class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        <i class="fa-solid fa-file-code text-blue-600 text-sm"></i>
                        <div>
                            <div class="text-slate-800">Export as JSON</div>
                            <div class="text-[10px] text-slate-400 font-normal">Fine-tuning dataset payload</div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Upload Live Receipt Button -->
            <button type="button" @click="isUploadModalOpen = true"
                class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition shadow-3xs cursor-pointer">
                <i class="fa-solid fa-cloud-arrow-up text-emerald-400"></i>
                <span>Test Live Sample</span>
            </button>

            <!-- Run Benchmark Suite Action -->
            <form action="{{ route('manager.model-evaluation.run') }}" method="POST">
                @csrf
                <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-3xs cursor-pointer">
                    <i class="fa-solid fa-bolt-lightning text-amber-300"></i>
                    <span>Run Benchmarks</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-2xl flex items-center gap-2.5 shadow-3xs">
            <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold rounded-2xl space-y-1 shadow-3xs">
            @foreach($errors->all() as $error)
                <p class="flex items-center gap-2"><i class="fa-solid fa-circle-exclamation text-rose-500"></i> {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <!-- Executive KPI Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3.5">
        <!-- 1. Category Classification Accuracy -->
        <div class="bg-white p-4 rounded-3xl border border-slate-200/70 shadow-3xs space-y-2 relative overflow-hidden group hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Category Accuracy</span>
                <div class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-tags"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black font-mono {{ $categoryAccuracy >= 80 ? 'text-emerald-600' : 'text-amber-600' }}">
                    {{ number_format($categoryAccuracy, 1) }}%
                </h3>
                <p class="text-[11px] text-slate-500 mt-0.5">TF-IDF Vector Space</p>
            </div>
            <div class="pt-1.5 border-t border-slate-100 flex items-center justify-between text-[10px]">
                <span class="text-slate-400">Total Samples</span>
                <span class="font-bold text-slate-700 font-mono">{{ $totalPredictions }}</span>
            </div>
        </div>

        <!-- 2. OCR Amount Accuracy -->
        <div class="bg-white p-4 rounded-3xl border border-slate-200/70 shadow-3xs space-y-2 relative overflow-hidden group hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Amount Accuracy</span>
                <div class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-receipt"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black font-mono {{ $ocrAmountAccuracy >= 80 ? 'text-blue-600' : 'text-amber-600' }}">
                    {{ number_format($ocrAmountAccuracy, 1) }}%
                </h3>
                <p class="text-[11px] text-slate-500 mt-0.5">Regex & Grand Total</p>
            </div>
            <div class="pt-1.5 border-t border-slate-100 flex items-center justify-between text-[10px]">
                <span class="text-slate-400">Discrepancies</span>
                <span class="font-bold text-slate-700 font-mono">{{ $feedbacks->where('field_name', 'amount')->count() }}</span>
            </div>
        </div>

        <!-- 3. Merchant Recognition Accuracy -->
        <div class="bg-white p-4 rounded-3xl border border-slate-200/70 shadow-3xs space-y-2 relative overflow-hidden group hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Merchant Accuracy</span>
                <div class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-store"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black font-mono {{ $ocrMerchantAccuracy >= 80 ? 'text-indigo-600' : 'text-amber-600' }}">
                    {{ number_format($ocrMerchantAccuracy, 1) }}%
                </h3>
                <p class="text-[11px] text-slate-500 mt-0.5">Entity Header Match</p>
            </div>
            <div class="pt-1.5 border-t border-slate-100 flex items-center justify-between text-[10px]">
                <span class="text-slate-400">Discrepancies</span>
                <span class="font-bold text-slate-700 font-mono">{{ $feedbacks->where('field_name', 'merchant')->count() }}</span>
            </div>
        </div>

        <!-- 4. Date Extraction Accuracy -->
        <div class="bg-white p-4 rounded-3xl border border-slate-200/70 shadow-3xs space-y-2 relative overflow-hidden group hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Date Accuracy</span>
                <div class="w-6 h-6 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black font-mono {{ $ocrDateAccuracy >= 80 ? 'text-violet-600' : 'text-amber-600' }}">
                    {{ number_format($ocrDateAccuracy, 1) }}%
                </h3>
                <p class="text-[11px] text-slate-500 mt-0.5">Malaysian Formats</p>
            </div>
            <div class="pt-1.5 border-t border-slate-100 flex items-center justify-between text-[10px]">
                <span class="text-slate-400">Discrepancies</span>
                <span class="font-bold text-slate-700 font-mono">{{ $feedbacks->whereIn('field_name', ['date', 'transaction_date'])->count() }}</span>
            </div>
        </div>

        <!-- 5. Baseline Comparison -->
        <div class="bg-white p-4 rounded-3xl border border-slate-200/70 shadow-3xs space-y-2 relative overflow-hidden group hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Baseline Delta</span>
                <div class="w-6 h-6 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black font-mono text-emerald-600">
                    +{{ number_format(max(0, $categoryAccuracy - $baselineAccuracy), 1) }}%
                </h3>
                <p class="text-[11px] text-slate-500 mt-0.5">Vs. {{ number_format($baselineAccuracy, 1) }}% Baseline</p>
            </div>
            <div class="pt-1.5 border-t border-slate-100 flex items-center justify-between text-[10px]">
                <span class="text-slate-400">Active Adaptation</span>
                <span class="font-bold text-emerald-600">Active</span>
            </div>
        </div>

        <!-- 6. F1-Score / Latency -->
        <div class="bg-white p-4 rounded-3xl border border-slate-200/70 shadow-3xs space-y-2 relative overflow-hidden group hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Macro F1 & Latency</span>
                <div class="w-6 h-6 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-gauge-high"></i>
                </div>
            </div>
            <div>
                <h3 class="text-2xl font-black font-mono text-slate-900">
                    {{ number_format($macroF1, 1) }}%
                </h3>
                <p class="text-[11px] text-slate-500 mt-0.5">{{ number_format($avgExecutionTime, 1) }} ms / inference</p>
            </div>
            <div class="pt-1.5 border-t border-slate-100 flex items-center justify-between text-[10px]">
                <span class="text-slate-400">Classification</span>
                <span class="font-bold text-slate-700">Balanced</span>
            </div>
        </div>
    </div>

    <!-- Section 2: Confusion Matrix Grid -->
    <div class="bg-white rounded-3xl border border-slate-200/70 shadow-3xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
            <div>
                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-border-all text-blue-600"></i>
                    <span>Category Confusion Matrix & Classification Metrics</span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Multi-class precision, recall, and harmonic F1-score derived dynamically from staff validation feedback and test suites.
                </p>
            </div>
            <div class="flex items-center gap-3 text-xs font-mono font-medium text-slate-500">
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> TP: <strong class="text-slate-800">{{ $totalTP }}</strong>
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span> FP: <strong class="text-slate-800">{{ $totalFP }}</strong>
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span> FN: <strong class="text-slate-800">{{ $totalFN }}</strong>
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5">Category Class</th>
                        <th class="px-4 py-3.5 text-center">True Positives (TP)</th>
                        <th class="px-4 py-3.5 text-center">False Positives (FP)</th>
                        <th class="px-4 py-3.5 text-center">False Negatives (FN)</th>
                        <th class="px-4 py-3.5 text-center">Precision</th>
                        <th class="px-4 py-3.5 text-center">Recall</th>
                        <th class="px-4 py-3.5 text-center">F1-Score</th>
                        <th class="px-5 py-3.5 text-right">Performance Class</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @foreach($confusionMatrix as $categoryName => $matrix)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-800 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                                    <span>{{ $categoryName }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-mono text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    {{ $matrix['TP'] }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-mono text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    {{ $matrix['FP'] }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-mono text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                    {{ $matrix['FN'] }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center font-mono font-bold text-slate-700">
                                {{ number_format($matrix['precision'], 1) }}%
                            </td>
                            <td class="px-4 py-4 text-center font-mono font-bold text-slate-700">
                                {{ number_format($matrix['recall'], 1) }}%
                            </td>
                            <td class="px-4 py-4 text-center font-mono font-bold {{ $matrix['f1'] >= 80 ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ number_format($matrix['f1'], 1) }}%
                            </td>
                            <td class="px-5 py-4 text-right">
                                @if($matrix['f1'] >= 85)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        <i class="fa-solid fa-circle-check text-[9px]"></i> High Precision
                                    </span>
                                @elseif($matrix['f1'] >= 70)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-blue-100 text-blue-800">
                                        <i class="fa-solid fa-circle-notch text-[9px]"></i> Stable
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-amber-100 text-amber-800">
                                        <i class="fa-solid fa-triangle-exclamation text-[9px]"></i> Calibrating
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 3: Evaluated Samples / Feedback Discrepancies Table -->
    <div class="bg-white rounded-3xl border border-slate-200/70 shadow-3xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
            <div>
                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-arrows-rotate text-emerald-600"></i>
                    <span>Continuous Active Learning Feedback Ledger</span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Field-level discrepancies captured when staff corrected OCR predictions during claim submission or auditing.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-500">Total Captured:</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-black font-mono bg-slate-900 text-white">
                    {{ $feedbacks->count() }}
                </span>
            </div>
        </div>

        @if($feedbacks->isEmpty())
            <div class="p-12 text-center space-y-3">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-lg">
                    <i class="fa-solid fa-check-double"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-800">Zero Active Discrepancies Logged</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    All submitted claims perfectly matched initial AI OCR predictions, or no staff corrections have been recorded yet.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3.5">Voucher Reference</th>
                            <th class="px-4 py-3.5">Field Type</th>
                            <th class="px-4 py-3.5">AI Initial Prediction</th>
                            <th class="px-4 py-3.5">Staff Corrected Value</th>
                            <th class="px-4 py-3.5 text-center">Confidence</th>
                            <th class="px-4 py-3.5 text-center">Trigger Status</th>
                            <th class="px-5 py-3.5 text-right">Logged Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @foreach($feedbacks as $fb)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-5 py-4">
                                    <div class="font-bold text-slate-900">
                                        {{ $fb->claim_id ? '#CLM-' . $fb->claim_id : ($fb->receipt_reference ?? 'Sample #' . $fb->id) }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 font-normal">
                                        by {{ $fb->user->name ?? 'Staff Claimant' }}
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    @php
                                        $fieldPill = match(strtolower($fb->field_name)) {
                                            'category' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                            'amount' => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'merchant' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'date', 'transaction_date' => 'bg-violet-50 text-violet-700 border-violet-200',
                                            default => 'bg-slate-50 text-slate-700 border-slate-200',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wider border {{ $fieldPill }}">
                                        {{ $fb->field_name }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 text-xs font-semibold">
                                        <i class="fa-solid fa-xmark text-[10px]"></i>
                                        <span>{{ $fb->predicted_value }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold">
                                        <i class="fa-solid fa-check text-[10px]"></i>
                                        <span>{{ $fb->actual_value }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <div class="inline-flex items-center gap-1.5 font-mono text-xs font-bold text-slate-700">
                                        <div class="w-12 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-indigo-600 h-full rounded-full" style="width: {{ min(100, $fb->confidence_score ?? 85) }}%"></div>
                                        </div>
                                        <span>{{ number_format($fb->confidence_score ?? 85, 1) }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        <i class="fa-solid fa-user-pen text-[9px] text-slate-500"></i>
                                        <span>{{ $fb->correction_status }}</span>
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right text-[11px] text-slate-500 font-mono">
                                    {{ $fb->created_at ? $fb->created_at->format('M d, Y H:i') : 'Recently' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Section 4: Benchmark Dataset Test Samples (Accordion / Ledger) -->
    <div class="bg-white rounded-3xl border border-slate-200/70 shadow-3xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
            <div>
                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-vial-circle-check text-indigo-600"></i>
                    <span>Offline Benchmark Test Suite (FYP Thesis Validation Set)</span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Batch TF-IDF and OCR pattern test vectors executed against ground-truth receipts.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-500">Total Vectors:</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-black font-mono bg-blue-50 text-blue-700 border border-blue-200">
                    {{ $benchmarks->count() }} Samples
                </span>
            </div>
        </div>

        @if($benchmarks->isEmpty())
            <div class="p-8 text-center text-slate-400 text-xs">
                No benchmark samples in the database. Click "Run Benchmarks" or "Test Live Sample" to populate.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3.5">Sample Name</th>
                            <th class="px-4 py-3.5">Ground Truth Category</th>
                            <th class="px-4 py-3.5">Model Prediction</th>
                            <th class="px-4 py-3.5 text-center">Amount Match</th>
                            <th class="px-4 py-3.5 text-center">Category Match</th>
                            <th class="px-5 py-3.5 text-right">Inference Latency</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @foreach($benchmarks->take(10) as $b)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-5 py-3.5 font-bold text-slate-800">
                                    {{ $b->sample_name }}
                                </td>
                                <td class="px-4 py-3.5 text-slate-600">
                                    {{ $b->actual_category }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="font-bold {{ $b->is_category_correct ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ $b->predicted_category ?? 'Pending Run' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    @if($b->is_amount_correct)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i class="fa-solid fa-check"></i> RM {{ number_format($b->actual_amount, 2) }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            <i class="fa-solid fa-xmark"></i> RM {{ number_format($b->actual_amount, 2) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    @if($b->is_category_correct)
                                        <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 inline-flex items-center justify-center text-xs">
                                            <i class="fa-solid fa-check"></i>
                                        </span>
                                    @else
                                        <span class="w-6 h-6 rounded-full bg-rose-100 text-rose-700 inline-flex items-center justify-center text-xs">
                                            <i class="fa-solid fa-xmark"></i>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right font-mono text-slate-500">
                                    {{ $b->processing_time_ms ? number_format($b->processing_time_ms, 2) . ' ms' : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Live Receipt Test Modal -->
    <div x-show="isUploadModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">

        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-5"
            @click.outside="isUploadModalOpen = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 text-sm">Test Live Physical Receipt</h3>
                </div>
                <button type="button" @click="isUploadModalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('manager.model-evaluation.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="space-y-1">
                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Sample Identifier</label>
                    <input type="text" name="sample_name" required placeholder="e.g. Shell Fuel Receipt #09"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Actual Category</label>
                        <select name="actual_category" required
                            class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                            <option value="Meals & Entertainment">Meals & Entertainment</option>
                            <option value="Fuel / Automotive">Fuel / Automotive</option>
                            <option value="Office Supplies">Office Supplies</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Actual Amount (RM)</label>
                        <input type="number" step="0.01" name="actual_amount" required placeholder="0.00"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Receipt Image File</label>
                    <input type="file" name="receipt" accept="image/*" required
                        class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                </div>

                <div class="pt-2 flex items-center justify-end gap-2">
                    <button type="button" @click="isUploadModalOpen = false"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2.5 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 transition cursor-pointer">
                        Process & Benchmark
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
