<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - AI & NLP Model Evaluation</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased"
    x-data="{ isMobileSidebarOpen: false, isUploadModalOpen: false, isSubmitting: false }">

    <div class="flex min-h-screen">
        <!-- Reusable Manager Sidebar -->
        @include('layouts.partials.manager-sidebar')

        <!-- Main Workspace -->
        <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full pb-24 overflow-y-auto space-y-6">

            <!-- Header & Actions -->
            <div class="border-b border-slate-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">AI & NLP Model Evaluation
                    </h1>
                    <p class="text-xs md:text-sm text-slate-500">TF-IDF Vector Space Category Classifier & OCR Field
                        Extraction Benchmark (FYP Thesis Metrics).</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="isUploadModalOpen = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition shadow-xs cursor-pointer">
                        <i class="fa-solid fa-cloud-arrow-up text-emerald-400"></i> Upload Live Receipt
                    </button>
                    <form action="{{ route('manager.model-evaluation.run') }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition shadow-xs cursor-pointer">
                            <i class="fa-solid fa-bolt-lightning text-amber-300"></i> Run Benchmark Suite
                        </button>
                    </form>
                </div>
            </div>

            <!-- Flash Alerts -->
            @if(session('success'))
                <div
                    class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-2xl flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div
                    class="p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold rounded-2xl space-y-1">
                    @foreach($errors->all() as $error)
                        <p class="flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-rose-500"></i>
                            {{ $error }}
                        </p>
                    @endforeach
                </div>
            @endif

            <!-- Overall Performance Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">TF-IDF Accuracy</span>
                    <h3
                        class="text-2xl font-black font-mono {{ $categoryAccuracy >= 80 ? 'text-emerald-600' : 'text-amber-600' }}">
                        {{ number_format($categoryAccuracy, 1) }}%
                    </h3>
                    <p class="text-[11px] text-slate-400">Classification Accuracy</p>
                </div>
                <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">OCR Value
                        Precision</span>
                    <h3 class="text-2xl font-black font-mono text-blue-600">
                        {{ number_format($ocrAmountAccuracy, 1) }}%
                    </h3>
                    <p class="text-[11px] text-slate-400">Grand Total Extraction Rate</p>
                </div>
                <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Avg Inference
                        Latency</span>
                    <h3 class="text-2xl font-black font-mono text-slate-900">
                        {{ number_format($avgExecutionTime, 2) }} <span
                            class="text-xs font-sans font-bold text-slate-500">ms</span>
                    </h3>
                    <p class="text-[11px] text-slate-400">Per sample execution</p>
                </div>
                <div class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Evaluated Corpus</span>
                    <h3 class="text-2xl font-black font-mono text-slate-900">
                        {{ $evaluatedCount }} / {{ $totalSamples }}
                    </h3>
                    <p class="text-[11px] text-slate-400">Standard Test Dataset</p>
                </div>
            </div>

            <!-- Confusion Matrix & Classification Metrics Table -->
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-xs p-5 space-y-4">
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-table-cells text-blue-600"></i> Category Confusion Matrix & Information
                    Retrieval Metrics
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs min-w-[650px]">
                        <thead
                            class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="p-3">Expense Category</th>
                                <th class="p-3 text-center">TP</th>
                                <th class="p-3 text-center">FP</th>
                                <th class="p-3 text-center">FN</th>
                                <th class="p-3 text-center">Precision</th>
                                <th class="p-3 text-center">Recall</th>
                                <th class="p-3 text-center">F1-Score</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @foreach($confusionMatrix as $categoryName => $metric)
                                @php
                                    $tp = $metric['TP'];
                                    $fp = $metric['FP'];
                                    $fn = $metric['FN'];
                                    $precision = ($tp + $fp) > 0 ? ($tp / ($tp + $fp)) : 0;
                                    $recall = ($tp + $fn) > 0 ? ($tp / ($tp + $fn)) : 0;
                                    $f1 = ($precision + $recall) > 0 ? (2 * ($precision * $recall) / ($precision + $recall)) : 0;
                                @endphp
                                <tr class="hover:bg-slate-50/50">
                                    <td class="p-3 font-bold text-slate-800">{{ $categoryName }}</td>
                                    <td class="p-3 text-center font-mono font-bold text-emerald-600">{{ $tp }}</td>
                                    <td class="p-3 text-center font-mono font-bold text-rose-500">{{ $fp }}</td>
                                    <td class="p-3 text-center font-mono font-bold text-amber-500">{{ $fn }}</td>
                                    <td class="p-3 text-center font-mono font-bold">
                                        {{ number_format($precision * 100, 1) }}%
                                    </td>
                                    <td class="p-3 text-center font-mono font-bold">{{ number_format($recall * 100, 1) }}%
                                    </td>
                                    <td class="p-3 text-center font-mono font-bold text-blue-600">
                                        {{ number_format($f1 * 100, 1) }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Detailed Test Sample Breakdown -->
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 font-bold text-xs text-slate-800">
                    Sample Test Evaluation Records
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs min-w-[800px]">
                        <thead
                            class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="p-3">Sample Name</th>
                                <th class="p-3">Actual Category</th>
                                <th class="p-3">Predicted Category</th>
                                <th class="p-3 text-right">Actual RM</th>
                                <th class="p-3 text-right">Extracted RM</th>
                                <th class="p-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @foreach($benchmarks as $row)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="p-3 font-bold text-slate-800">{{ $row->sample_name }}</td>
                                    <td class="p-3 text-slate-600">{{ $row->actual_category }}</td>
                                    <td
                                        class="p-3 font-mono font-bold {{ $row->is_category_correct ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $row->predicted_category ?? 'Not Evaluated' }}
                                    </td>
                                    <td class="p-3 text-right font-mono">{{ number_format($row->actual_amount, 2) }}</td>
                                    <td
                                        class="p-3 text-right font-mono font-bold {{ $row->is_amount_correct ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $row->extracted_amount ? number_format($row->extracted_amount, 2) : '-' }}
                                    </td>
                                    <td class="p-3 text-center">
                                        @if($row->is_category_correct && $row->is_amount_correct)
                                            <span
                                                class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">PASS</span>
                                        @else
                                            <span
                                                class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">FAIL
                                                / PARTIAL</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- Upload & Live Evaluation Modal -->
    <div x-show="isUploadModalOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl"
            @click.away="!isSubmitting && (isUploadModalOpen = false)">
            <div class="flex justify-between items-center pb-2 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900">Upload Live Receipt for Evaluation</h3>
                <button type="button" @click="isUploadModalOpen = false" :disabled="isSubmitting"
                    class="text-slate-400 hover:text-slate-700 cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('manager.model-evaluation.upload') }}" method="POST" enctype="multipart/form-data"
                class="space-y-4 text-xs" @submit="isSubmitting = true">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Sample Identifier / Title *</label>
                    <input type="text" name="sample_name" required placeholder="e.g. Shell Petrol Live Test"
                        class="w-full p-2.5 bg-white border border-slate-200 rounded-xl outline-none">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Receipt Image File (JPEG/PNG) *</label>
                    <input type="file" name="receipt" required accept="image/*"
                        class="w-full p-2 bg-slate-50 border border-slate-200 rounded-xl outline-none cursor-pointer">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Ground Truth Category *</label>
                        <select name="actual_category" required
                            class="w-full p-2.5 bg-white border border-slate-200 rounded-xl outline-none">
                            <option value="Meals & Entertainment">Meals & Entertainment</option>
                            <option value="Fuel / Automotive">Fuel / Automotive</option>
                            <option value="Office Supplies">Office Supplies</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Ground Truth Total (RM) *</label>
                        <input type="number" step="0.01" name="actual_amount" required placeholder="0.00"
                            class="w-full p-2.5 bg-white border border-slate-200 rounded-xl outline-none font-mono font-bold">
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="isUploadModalOpen = false" :disabled="isSubmitting"
                        class="px-4 py-2 bg-slate-100 font-bold rounded-xl text-slate-600 cursor-pointer">Cancel</button>

                    <button type="submit" x-show="!isSubmitting"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl cursor-pointer">
                        Process & Evaluate
                    </button>

                    <button type="button" x-show="isSubmitting" x-cloak disabled
                        class="px-4 py-2 bg-emerald-600 text-white font-bold rounded-xl flex items-center gap-2 opacity-80 cursor-not-allowed">
                        <svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        Running OCR & NLP...
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>

</html>