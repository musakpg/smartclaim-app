<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Edit & Resubmit Claim #CLM-{{ $claim->claim_id }}</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased">

    <div class="flex min-h-screen flex-col lg:flex-row">
        <!-- Reusable Staff Navigation Sidebar -->
        @include('layouts.partials.staff-sidebar')

        <!-- Main Workspace Area -->
        <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <div class="flex-1 p-4 md:p-8 max-w-4xl mx-auto w-full pb-24 overflow-y-auto space-y-6">

                <!-- Header Title Banner -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-300">
                                Revision Required
                            </span>
                            <span class="text-xs text-slate-500 font-mono">#CLM-{{ str_pad($claim->claim_id, 4, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight mt-1">
                            Edit & Resubmit Claim
                        </h1>
                        <p class="text-xs md:text-sm text-slate-500">
                            Please update the flagged information or upload replacement supporting documents below.
                        </p>
                    </div>

                    <a href="{{ route('claims.history') }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 shadow-2xs transition">
                        <i class="fa-solid fa-arrow-left"></i> Back to History
                    </a>
                </div>

                <!-- Prominent Auditor Revision Feedback Alert Banner -->
                <div class="p-5 bg-gradient-to-r from-amber-50 to-amber-100/60 border border-amber-300/80 rounded-2xl shadow-xs space-y-3">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                            <i class="fa-solid fa-triangle-exclamation text-base"></i>
                        </div>
                        <div class="flex-1 min-w-0 space-y-1">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-amber-950">Clarification & Revision Requested</h3>
                                <span class="text-3xs font-semibold text-amber-700 bg-amber-200/70 px-2 py-0.5 rounded-full">
                                    {{ $claim->updated_at->format('d M Y, h:i A') }}
                                </span>
                            </div>
                            
                            <!-- Exception Reason -->
                            <div class="text-xs text-amber-900 font-medium">
                                <span class="font-bold text-amber-950">Audit Exception Reason:</span>
                                <span class="inline-block bg-white/80 px-2 py-0.5 rounded border border-amber-200 font-semibold text-amber-900 ml-1">
                                    {{ $claim->revision_reason ?? 'Supporting Documentation Clarification' }}
                                </span>
                            </div>

                            <!-- Auditor Remarks / Instructions -->
                            @if($claim->remarks)
                                <div class="mt-2 p-3 bg-white/90 rounded-xl border border-amber-200/90 text-xs text-slate-800 space-y-1">
                                    <div class="font-bold text-slate-900 flex items-center gap-1.5 text-2xs uppercase tracking-wider text-amber-900">
                                        <i class="fa-solid fa-comment-dots"></i> Auditor Instructions:
                                    </div>
                                    <p class="leading-relaxed whitespace-pre-line">{{ $claim->remarks }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="p-4 bg-rose-50 border border-rose-300 rounded-xl text-xs text-rose-800 space-y-1">
                        <p class="font-bold">Please correct the following errors:</p>
                        <ul class="list-disc pl-5 space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Resubmission Form -->
                <form action="{{ route('claims.resubmit', $claim->claim_id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-5">
                        <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                            <i class="fa-solid fa-pen-to-square text-indigo-600"></i>
                            Claim Parameters ({{ $claim->claim_type }})
                        </h2>

                        <!-- Claim Title -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Claim Title / Purpose Summary *</label>
                            <input type="text" name="title" value="{{ old('title', $claim->title) }}" required
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                        </div>

                        @if ($claim->claim_type === 'Mileage')
                            <!-- Mileage Specific Fields -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Authorized Vehicle *</label>
                                    <select name="vehicle_id" required
                                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                                        @foreach($personalVehicles as $v)
                                            <option value="{{ $v->vehicle_id }}" {{ old('vehicle_id', $claim->vehicle_id) == $v->vehicle_id ? 'selected' : '' }}>
                                                {{ $v->plate_number }} - {{ $v->model }} ({{ $v->vehicle_type }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Distance Travelled (KM) *</label>
                                    <input type="number" step="0.1" min="0.1" name="mileage_km" value="{{ old('mileage_km', $claim->mileage_km) }}" required
                                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Start Location *</label>
                                    <input type="text" name="start_location" value="{{ old('start_location', $claim->start_location) }}" required
                                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Destination Location *</label>
                                    <input type="text" name="destination_location" value="{{ old('destination_location', $claim->destination_location) }}" required
                                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                                </div>
                            </div>

                            <!-- Mileage Proof Document -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Replacement Mileage Route / GPS Proof (Optional if keeping current)
                                </label>
                                <input type="file" name="mileage_document" accept="image/*"
                                    class="w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-200 rounded-xl p-1 bg-slate-50">
                                @if($claim->mileage_document_path)
                                    <p class="text-2xs text-slate-500 mt-1">
                                        <i class="fa-solid fa-check text-emerald-600"></i> Current proof document attached. Upload a new file only if requested by auditor.
                                    </p>
                                @endif
                            </div>
                        @else
                            <!-- Receipt Specific Fields -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Total Amount (RM) *</label>
                                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $claim->amount) }}" required
                                        class="w-full px-3.5 py-2.5 text-sm font-mono font-bold bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Invoice / Receipt No. *</label>
                                    <input type="text" name="receipt_invoice_no" value="{{ old('receipt_invoice_no', $claim->receipt_invoice_no) }}" required
                                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Transaction Date *</label>
                                    <input type="date" name="transaction_date" value="{{ old('transaction_date', $claim->transaction_date ? \Carbon\Carbon::parse($claim->transaction_date)->format('Y-m-d') : '') }}" required
                                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Expense Category *</label>
                                    <select name="category_id" required
                                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" {{ old('category_id', $claim->category_id) == $cat->id ? 'selected' : '' }}>
                                                {{ $cat->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Payment Method *</label>
                                    <select name="payment_method" required
                                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                                        <option value="Cash" {{ old('payment_method', $claim->payment_method) == 'Cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="Card" {{ old('payment_method', $claim->payment_method) == 'Card' ? 'selected' : '' }}>Credit / Debit Card</option>
                                        <option value="e-Wallet" {{ old('payment_method', $claim->payment_method) == 'e-Wallet' ? 'selected' : '' }}>e-Wallet / QR Pay</option>
                                        <option value="Bank Transfer" {{ old('payment_method', $claim->payment_method) == 'Bank Transfer' ? 'selected' : '' }}>Online Bank Transfer</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Replacement Receipt Upload -->
                            <div class="space-y-2">
                                <label class="block text-xs font-semibold text-slate-700">
                                    Upload Replacement / Clearer Receipt File (Optional)
                                </label>
                                <div class="flex items-center gap-4">
                                    <div class="flex-1">
                                        <input type="file" name="receipt_image" accept="image/*"
                                            class="w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-200 rounded-xl p-1 bg-slate-50">
                                    </div>
                                    @if($claim->receipt_image_path)
                                        <div class="shrink-0 text-xs text-slate-500 bg-slate-100 px-3 py-2 rounded-xl flex items-center gap-1.5 border border-slate-200">
                                            <i class="fa-solid fa-image text-slate-400"></i>
                                            <span>Current file on record</span>
                                        </div>
                                    @endif
                                </div>
                                <p class="text-2xs text-slate-400">
                                    If the auditor flagged the receipt as blurry or missing details, upload a clearer photo or PDF scan here.
                                </p>
                            </div>
                        @endif

                        <!-- Business Purpose / Justification -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Business Purpose / Justification *</label>
                            <textarea name="business_purpose" rows="3" required
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">{{ old('business_purpose', $claim->business_purpose) }}</textarea>
                        </div>

                        <!-- Resubmission Notes to Auditor -->
                        <div class="p-4 bg-indigo-50/50 border border-indigo-100 rounded-xl space-y-1.5">
                            <label class="block text-xs font-bold text-indigo-950 flex items-center gap-1.5">
                                <i class="fa-solid fa-reply text-indigo-600"></i> Response / Clarification Notes for Auditor (Optional)
                            </label>
                            <textarea name="resubmission_notes" rows="2" placeholder="Explain the corrections or additional files provided to expedite sign-off..."
                                class="w-full px-3.5 py-2 text-xs bg-white border border-indigo-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">{{ old('resubmission_notes') }}</textarea>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between pt-2">
                        <a href="{{ route('claims.history') }}"
                            class="px-5 py-2.5 text-xs font-semibold text-slate-600 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition shadow-2xs">
                            Cancel
                        </a>

                        <button type="submit"
                            class="px-6 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer">
                            <i class="fa-solid fa-paper-plane"></i>
                            Resubmit Claim for Review
                        </button>
                    </div>
                </form>

            </div>
        </main>
    </div>

</body>
</html>
