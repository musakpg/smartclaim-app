<!DOCTYPE html>
<html lang="en" x-data="ocrForm()" @google-maps-loaded.window="initGoogleMapsDependentLogic()">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartClaim - Submit Claim</title>
    <meta name="google-maps-api-key" content="{{ config('services.google.maps_api_key', env('GOOGLE_MAPS_API_KEY')) }}">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/imask"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased"
    :class="isModalOpen || isMobileSidebarOpen ? 'overflow-hidden' : ''">

    <div class="flex min-h-screen flex-col lg:flex-row">
        <!-- Reusable Staff Navigation Sidebar -->
        @include('layouts.partials.staff-sidebar')

        <!-- Main Workspace Area -->
        <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <div class="flex-1 p-4 md:p-8 max-w-4xl mx-auto w-full pb-24 overflow-y-auto space-y-6">

                <!-- Header Title Banner -->
                <div class="space-y-0.5">
                    <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight"
                        x-text="activeForm === 'Receipt' ? 'Submit Expense Claim' : 'Submit Mileage Allowance'"></h1>
                    <p class="text-xs md:text-sm text-slate-500"
                        x-text="activeForm === 'Receipt' ? 'Upload a receipt and verify the AI-parsed details before submitting.' : 'Log journey distance parameters and select an authorized personal vehicle for allowance payout.'">
                    </p>
                </div>

                <!-- Form Type Switcher Toggle -->
                <div class="flex items-center p-1 bg-slate-200/60 rounded-xl w-full shadow-3xs text-xs mb-2">
                    <button type="button" @click="switchForm('Receipt')"
                        :class="activeForm === 'Receipt' ? 'bg-white text-slate-900 font-bold shadow-xs border border-slate-300' : 'text-slate-500 hover:text-slate-900 font-medium'"
                        class="flex-1 py-2.5 rounded-lg transition-all cursor-pointer flex items-center justify-center gap-2">
                        <i class="fa-solid fa-file-invoice"></i> Based on Receipt
                    </button>
                    <button type="button" @click="switchForm('Mileage')"
                        :class="activeForm === 'Mileage' ? 'bg-white text-blue-600 font-bold shadow-xs border border-slate-300' : 'text-slate-500 hover:text-slate-900 font-medium'"
                        class="flex-1 py-2.5 rounded-lg transition-all cursor-pointer flex items-center justify-center gap-2">
                        <i class="fa-solid fa-route"></i> Mileage Allowance
                    </button>
                </div>

                <!-- Duplicate / Tamper Interception Warning Box -->
                <div x-show="isDuplicate && activeForm === 'Receipt'" x-cloak x-transition
                    class="p-4 bg-rose-50 border border-rose-300 rounded-2xl text-rose-800 text-xs font-semibold space-y-1.5 shadow-xs">
                    <p class="font-bold text-sm text-rose-900 flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-rose-600 text-base animate-bounce"></i>
                        <span>Submission Blocked: Duplicate / Manipulated Record Detected</span>
                    </p>
                    <div class="pl-6 space-y-1 text-rose-700 font-medium leading-relaxed">
                        <p
                            x-text="duplicateMessage || 'Security Interception: A voucher record with identical image hash or invoice details already exists in the organization database.'">
                        </p>
                        <p class="text-[11px] font-bold text-rose-900 pt-1">
                            <i class="fa-solid fa-arrow-rotate-left mr-1"></i> Please click <strong>Change
                                Image</strong> to upload a different, authentic physical receipt.
                        </p>
                    </div>
                </div>

                <!-- Flash Errors Banner -->
                @if($errors->any())
                    <div
                        class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-xs font-semibold space-y-1">
                        @foreach($errors->all() as $error)
                            <p class="flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-rose-500"></i>
                                {{ $error }}
                            </p>
                        @endforeach
                    </div>
                @endif

                <!-- Primary Form Card -->
                <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-xs">
                    <form id="claimForm" action="{{ route('claims.store') }}" method="POST"
                        enctype="multipart/form-data" class="space-y-6" @submit="submitForm($event)">
                        @csrf

                        <!-- Hidden Metadata Payloads -->
                        <input type="hidden" name="claim_type" :value="activeForm">
                        <input type="hidden" name="extracted_raw_text" id="extracted_raw_text">
                        <input type="hidden" name="raw_ocr_amount" :value="rawOcrAmount">

                        <!-- ========================================================================= -->
                        <!-- SECTION A: RECEIPT-BASED CLAIM FORM                                       -->
                        <!-- ========================================================================= -->
                        <div x-show="activeForm === 'Receipt'" class="space-y-6" x-transition>
                            <!-- Receipt File Upload and Preview -->
                            <div class="space-y-3">
                                <div x-show="!imagePreview" class="space-y-4">
                                    <div
                                        class="relative bg-white rounded-2xl border-2 border-dashed border-slate-300 hover:border-slate-400 p-6 md:p-10 text-center transition-all cursor-pointer group">
                                        <input type="file" name="receipt" id="receipt" accept="image/*"
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-30"
                                            @change="handleFileChange($event)" :required="activeForm === 'Receipt'">
                                        <div class="relative z-20 pointer-events-none">
                                            <div
                                                class="w-10 h-10 md:w-12 md:h-12 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3 border border-slate-100">
                                                <i
                                                    class="fa-solid fa-cloud-arrow-up text-slate-400 group-hover:text-slate-600 text-base md:text-lg"></i>
                                            </div>
                                            <h3 class="text-slate-800 font-bold text-xs md:text-sm mb-0.5">
                                                Drop your receipt here, or <span
                                                    class="text-blue-600 group-hover:underline">browse gallery</span>
                                            </h3>
                                            <p class="text-slate-400 text-[10px] md:text-xs font-medium">JPEG or PNG —
                                                Max 5MB</p>
                                        </div>
                                    </div>

                                    <!-- Direct Mobile Camera Snap Option -->
                                    <div class="block sm:hidden relative">
                                        <button type="button" @click="openCamera()"
                                            class="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-xs uppercase tracking-wider flex items-center justify-center gap-2 shadow-xs transition-all cursor-pointer">
                                            <i class="fa-solid fa-camera text-sm text-emerald-400"></i> Snap Receipt via Camera Direct
                                        </button>
                                    </div>
                                </div>

                                <div x-show="imagePreview" x-cloak
                                    class="bg-white p-3 md:p-4 rounded-2xl border border-slate-200/80 shadow-3xs space-y-4">
                                    <div
                                        class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-100 pb-3 gap-2">
                                        <div class="flex items-center gap-2 truncate">
                                            <span
                                                class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                                            <p class="text-xs font-bold text-slate-700 truncate max-w-xs"
                                                x-text="'Attached: ' + fileName"></p>
                                        </div>
                                        <button type="button" @click="triggerReupload()"
                                            class="w-full sm:w-auto px-3 py-2 bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                                            <i class="fa-solid fa-arrow-rotate-left"></i> Change Image
                                        </button>
                                    </div>
                                    <div @click="isModalOpen = true"
                                        class="relative rounded-xl border border-slate-100 bg-slate-50 overflow-hidden group flex items-center justify-center max-h-[300px] md:max-h-[380px] cursor-zoom-in">
                                        <img :src="imagePreview" alt="Receipt Preview"
                                            class="w-full max-h-[300px] md:max-h-[380px] object-contain transition-all duration-300 group-hover:scale-[1.01]">
                                        <div
                                            class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-all duration-200 text-white font-semibold text-xs gap-1.5 backdrop-blur-xs">
                                            <i class="fa-solid fa-magnifying-glass-plus text-sm"></i> Click to preview
                                            image
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Extracted Metadata Fields -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 md:gap-y-5">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700 tracking-wide">Merchant
                                        Name</label>
                                    <input type="text" name="merchant_name" x-model="merchant"
                                        placeholder="e.g. Petronas / Starbucks" :required="activeForm === 'Receipt'"
                                        class="w-full px-4 py-2.5 md:py-3 bg-white border border-slate-200 rounded-xl outline-none text-xs md:text-sm shadow-2xs">
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700 tracking-wide">Receipt /
                                        Invoice No.</label>
                                    <input type="text" name="receipt_invoice_no" x-model="invoiceNo"
                                        @input.debounce.250ms="checkDuplicateAndPopup()" placeholder="e.g. INV-10492"
                                        :required="activeForm === 'Receipt'"
                                        class="w-full px-4 py-2.5 md:py-3 bg-white border border-slate-200 rounded-xl outline-none text-xs md:text-sm shadow-2xs">
                                </div>

                                <div class="space-y-1.5 md:col-span-2">
                                    <label class="block text-xs font-bold text-slate-700 tracking-wide">Location (Branch
                                        Address)</label>
                                    <input type="text" name="location_address" x-model="location"
                                        placeholder="Store address..."
                                        class="w-full px-4 py-2.5 md:py-3 bg-white border border-slate-200 rounded-xl outline-none text-xs md:text-sm shadow-2xs">
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700 tracking-wide">Transaction
                                        Date</label>
                                    <input type="date" name="transaction_date" x-model="date"
                                        @change="checkDuplicateAndPopup()" :required="activeForm === 'Receipt'"
                                        :disabled="activeForm !== 'Receipt'"
                                        class="w-full px-4 py-2.5 md:py-3 bg-white border border-slate-200 rounded-xl outline-none text-xs md:text-sm shadow-2xs">
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700 tracking-wide">Total
                                        Amount</label>
                                    <div class="relative">
                                        <span
                                            class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs md:text-sm font-medium">RM</span>
                                        <input type="text" id="amount_input"
                                            @input.debounce.250ms="checkDuplicateAndPopup()" placeholder="0.00"
                                            :required="activeForm === 'Receipt'"
                                            class="w-full pl-11 pr-4 py-2.5 md:py-3 bg-white border border-slate-200 rounded-xl outline-none text-xs md:text-sm shadow-2xs font-bold font-mono">
                                        <input type="hidden" name="amount" x-model="amount">
                                    </div>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700 tracking-wide">Expense
                                        Category</label>
                                    <div class="relative">
                                        <select id="category" name="category" x-model="category"
                                            @change="checkDuplicateAndPopup()" :required="activeForm === 'Receipt'"
                                            class="w-full px-4 py-2.5 md:py-3 bg-white border border-slate-200 rounded-xl outline-none text-xs md:text-sm shadow-2xs appearance-none transition-all">
                                            <option value="" disabled selected>Select a category</option>
                                            @foreach($categories as $cat)
                                                <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                        <span
                                            class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 text-xs">
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </span>
                                    </div>
                                </div>

                                <!-- Company Fleet Dropdown (Terkini: Menyokong Fuel & Fleet Logistics) -->
                                <div class="space-y-1.5" x-show="isFuelCategory()" x-transition x-cloak>
                                    <label class="block text-xs font-bold text-rose-700 tracking-wide">
                                        <i class="fa-solid fa-truck-ramp-box"></i> Select Company Fleet Vehicle *
                                    </label>
                                    <div class="relative">
                                        <select name="vehicle_plate_number" x-model="vehiclePlate"
                                            :required="isFuelCategory() && activeForm === 'Receipt'"
                                            :disabled="activeForm !== 'Receipt'"
                                            class="w-full px-4 py-2.5 md:py-3 bg-rose-50/50 border border-rose-200 focus:border-rose-400 rounded-xl outline-none text-xs md:text-sm font-bold shadow-2xs appearance-none transition-all">
                                            <option value="" disabled selected>-- Choose Company Fleet Plate --</option>
                                            @forelse($companyFleet ?? [] as $fleet)
                                                <option value="{{ $fleet->plate_number }}">
                                                    {{ $fleet->plate_number }} — {{ $fleet->brand_model }}
                                                    ({{ $fleet->vehicle_type }})
                                                </option>
                                            @empty
                                                <option value="" disabled>No active company fleet vehicles available. Please
                                                    contact management.</option>
                                            @endforelse
                                        </select>
                                        <span
                                            class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-rose-400 text-xs">
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700 tracking-wide">Payment
                                        Method</label>
                                    <div class="relative">
                                        <select name="payment_method" x-model="paymentMethod"
                                            :required="activeForm === 'Receipt'"
                                            class="w-full px-4 py-2.5 md:py-3 bg-white border border-slate-200 rounded-xl outline-none text-xs md:text-sm shadow-2xs appearance-none">
                                            <option value="Cash">Cash</option>
                                            <option value="Card">Card</option>
                                            <option value="e-Wallet">e-Wallet</option>
                                            <option value="Touch'n Go">Touch'n Go</option>
                                        </select>
                                        <span
                                            class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 text-xs">
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- PARSED LINE ITEMS BREAKDOWN TABLE SECTION -->
                            <div x-show="activeForm === 'Receipt'" x-cloak
                                class="space-y-3 pt-4 border-t border-slate-100">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold text-slate-700 tracking-wide uppercase">
                                        <i class="fa-solid fa-list-check text-emerald-600 mr-1"></i> Itemized Receipt
                                        Breakdown
                                        <span class="text-slate-400 font-normal"
                                            x-text="'(' + items.length + ' items)'"></span>
                                    </label>
                                    <button type="button" @click="addItemRow()"
                                        class="px-2.5 py-1 bg-blue-50 text-blue-700 hover:bg-blue-100 font-bold rounded-lg text-xs transition cursor-pointer flex items-center gap-1">
                                        <i class="fa-solid fa-plus text-[10px]"></i> Add Item
                                    </button>
                                </div>

                                <input type="hidden" name="items" :value="JSON.stringify(items)">

                                <div
                                    class="overflow-hidden border border-slate-200 rounded-2xl bg-slate-50/50 shadow-3xs">
                                    <table class="w-full text-left text-xs">
                                        <thead
                                            class="bg-slate-100 text-slate-500 font-bold uppercase text-[10px] border-b border-slate-200">
                                            <tr>
                                                <th class="p-2.5">Item Description</th>
                                                <th class="p-2.5 text-center w-20">Qty</th>
                                                <th class="p-2.5 text-right w-28">Unit (RM)</th>
                                                <th class="p-2.5 text-right w-28">Subtotal (RM)</th>
                                                <th class="p-2.5 text-center w-10"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200/60 font-medium">
                                            <template x-for="(item, index) in items" :key="index">
                                                <tr class="hover:bg-white transition">
                                                    <td class="p-2">
                                                        <input type="text" x-model="item.item_name" required
                                                            placeholder="Item description..."
                                                            class="w-full p-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-800">
                                                    </td>
                                                    <td class="p-2 text-center">
                                                        <input type="number" min="1" x-model="item.quantity"
                                                            @input="recalculateItem(index)"
                                                            class="w-full p-1.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-center">
                                                    </td>
                                                    <td class="p-2 text-right">
                                                        <input type="number" step="0.01" x-model="item.unit_price"
                                                            @input="recalculateItem(index)"
                                                            class="w-full p-1.5 bg-white border border-slate-200 rounded-lg text-xs font-mono text-right">
                                                    </td>
                                                    <td class="p-2 text-right">
                                                        <span class="font-bold font-mono text-slate-900"
                                                            x-text="'RM ' + parseFloat(item.subtotal || 0).toFixed(2)"></span>
                                                    </td>
                                                    <td class="p-2 text-center">
                                                        <button type="button" @click="removeItemRow(index)"
                                                            class="text-slate-400 hover:text-rose-600 transition p-1 cursor-pointer">
                                                            <i class="fa-solid fa-trash-can text-[11px]"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>
                                            <tr x-show="items.length === 0">
                                                <td colspan="5" class="p-4 text-center text-slate-400 font-medium">
                                                    No line items parsed yet. Upload a receipt or click <strong>+ Add
                                                        Item</strong>.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- ========================================================================= -->
                        <!-- SECTION B: MILEAGE ALLOWANCE CLAIM FORM                                   -->
                        <!-- ========================================================================= -->
                        <div x-show="activeForm === 'Mileage'" class="space-y-5" x-transition style="display: none;">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 md:gap-y-5 text-xs">
                                <div class="space-y-1.5 md:col-span-2">
                                    <label class="block text-xs font-bold text-slate-700 tracking-wide">
                                        <i class="fa-solid fa-shield-check text-blue-600 mr-1"></i> Select Verified
                                        Personal Vehicle (Active Roadtax) *
                                    </label>
                                    <div class="relative">
                                        <select name="vehicle_id" id="mileage_vehicle_id" x-model="selectedVehicleId"
                                            @change="updateVehicleDetails($event)" :required="activeForm === 'Mileage'"
                                            :disabled="activeForm !== 'Mileage'"
                                            class="w-full px-4 py-2.5 md:py-3 bg-white border border-slate-200 rounded-xl outline-none text-xs md:text-sm font-bold shadow-2xs appearance-none transition-all">
                                            <option value="" disabled selected>-- Select Authorized Vehicle --</option>
                                            @forelse($personalVehicles ?? [] as $v)
                                                <option value="{{ $v->vehicle_id }}" data-type="{{ $v->vehicle_type }}"
                                                    data-plate="{{ $v->plate_number }}"
                                                    data-rate="{{ strtolower($v->vehicle_type) === 'motorcycle' ? 0.30 : 0.60 }}">
                                                    {{ $v->brand_model }} ({{ $v->plate_number }}) — Roadtax:
                                                    {{ \Carbon\Carbon::parse($v->roadtax_expiry)->format('d/m/Y') }}
                                                    [Verified]
                                                </option>
                                            @empty
                                                <option value="" disabled>No approved personal vehicles with active roadtax
                                                    found. Please register or update roadtax in My Vehicles.</option>
                                            @endforelse
                                        </select>
                                        <span
                                            class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 text-xs">
                                            <i class="fa-solid fa-chevron-down"></i>
                                        </span>
                                    </div>
                                </div>

                                <input type="hidden" name="vehicle_type" :value="vehicleType">
                                <input type="hidden" name="vehicle_plate_number" :value="vehiclePlate">

                                <div class="md:col-span-2 space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700 tracking-wide">Purpose / Title
                                        of Travel *</label>
                                    <input type="text" name="title" x-model="mileageTitle"
                                        placeholder="e.g. Client site visit at Senai Industrial Park"
                                        :required="activeForm === 'Mileage'"
                                        class="w-full px-4 py-2.5 md:py-3 bg-white border border-slate-200 rounded-xl outline-none text-xs md:text-sm shadow-2xs">
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-emerald-700 tracking-wide">
                                        <i class="fa-solid fa-location-dot"></i> Starting Location *
                                    </label>
                                    <input type="text" id="origin" name="start_location"
                                        placeholder="Search origin location via Google Places..."
                                        :required="activeForm === 'Mileage'"
                                        class="w-full px-4 py-2.5 md:py-3 bg-white border border-slate-200 rounded-xl outline-none text-xs md:text-sm shadow-2xs">
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-rose-700 tracking-wide">
                                        <i class="fa-solid fa-location-pin-lock"></i> Destination Location *
                                    </label>
                                    <input type="text" id="destination" name="destination_location"
                                        placeholder="Search destination address via Google Places..."
                                        :required="activeForm === 'Mileage'"
                                        class="w-full px-4 py-2.5 md:py-3 bg-white border border-slate-200 rounded-xl outline-none text-xs md:text-sm shadow-2xs">
                                </div>


                                <!-- Pure Numeric Distance Field (No "KM" string suffix in input value) -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700 tracking-wide">Total Distance
                                        Traveled</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" id="distance" name="mileage_km"
                                            x-model="mileageKm" @input="calculateManualAllowance()" placeholder="0.00"
                                            :required="activeForm === 'Mileage'" readonly
                                            class="w-full px-4 pr-12 py-2.5 md:py-3 border border-slate-200 bg-slate-50 text-slate-800 rounded-xl outline-none font-mono font-bold text-xs md:text-sm shadow-2xs">
                                        <span
                                            class="absolute right-4 top-1/2 -translate-y-1/2 font-bold text-slate-400 text-xs">KM</span>
                                    </div>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700 tracking-wide">Date of
                                        Journey</label>
                                    <input type="date" name="transaction_date" :required="activeForm === 'Mileage'"
                                        :disabled="activeForm !== 'Mileage'"
                                        class="w-full px-4 py-2.5 md:py-3 bg-white border border-slate-200 rounded-xl outline-none font-mono font-bold text-xs md:text-sm shadow-2xs"
                                        value="{{ date('Y-m-d') }}">
                                </div>

                                <div class="space-y-1.5 md:col-span-2">
                                    <label class="block text-xs font-bold text-blue-700 tracking-wide">
                                        <i class="fa-solid fa-paperclip"></i> Upload Proof of Travel / Route Screenshot
                                        *
                                    </label>
                                    <input type="file" name="mileage_document" id="mileage_document" accept="image/*"
                                        :required="activeForm === 'Mileage'" :disabled="activeForm !== 'Mileage'"
                                        class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs md:text-sm font-semibold text-slate-600 file:mr-4 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer">
                                </div>
                            </div>

                            <div
                                class="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between mt-2 text-xs">
                                <div>
                                    <span
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Estimated
                                        Reimbursement Payout</span>
                                    <span class="text-xl md:text-2xl font-black text-emerald-600 font-mono"
                                        x-text="'RM ' + allowanceTotal"></span>
                                </div>
                                <div
                                    class="w-full sm:w-auto text-left sm:text-right text-[10px] md:text-[11px] font-bold text-slate-500 bg-white border border-slate-200/60 shadow-xs px-3 py-1.5 rounded-xl">
                                    Classification: <span class="text-blue-600 font-black" x-text="vehicleType"></span>
                                    | Rate: <span class="text-emerald-500 font-black"
                                        x-text="'RM ' + currentRate.toFixed(2) + ' / KM'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Real-Time Policy Advisory Alert -->
                        <div x-show="isPolicyBreached && activeForm === 'Receipt'" x-transition x-cloak
                            class="p-4 bg-amber-50 border border-amber-300 rounded-2xl text-amber-800 text-xs font-semibold space-y-1.5 shadow-xs mb-4">
                            <p class="font-bold text-sm text-amber-900 flex items-center gap-2">
                                <i class="fa-solid fa-triangle-exclamation text-amber-600 text-base"></i>
                                <span>Policy Advisory: Threshold Exceeded</span>
                            </p>
                            <div class="pl-6 space-y-1 text-amber-800 font-medium leading-relaxed">
                                <p>
                                    This amount exceeds the standard policy threshold (RM <span x-text="breachedPolicyLimit"></span>). A valid business justification is required for manager approval.
                                </p>
                            </div>
                        </div>

                        <!-- Business Purpose Context Field -->
                        <div class="space-y-1.5 text-xs">
                            <label class="block text-xs font-bold text-slate-700 tracking-wide">Business Purpose Context
                                <span x-show="(isPolicyBreached && activeForm === 'Receipt') || activeForm === 'Mileage'" class="text-rose-500">*</span>
                            </label>
                            <textarea name="business_purpose" x-model="businessPurpose" rows="3"
                                placeholder="Describe the corporate objective for this expenditure or journey..."
                                :required="(isPolicyBreached && activeForm === 'Receipt') || activeForm === 'Mileage'"
                                class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl outline-none text-xs md:text-sm shadow-2xs resize-none"></textarea>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex flex-col sm:flex-row justify-end gap-2 pt-4 border-t border-slate-100">
                            <button type="submit" x-show="!isExtracting" :disabled="isDuplicate || isSubmitting"
                                :class="(isDuplicate || isSubmitting) ? 'bg-slate-200 text-slate-400 border border-slate-300/60 cursor-not-allowed opacity-70' : 'bg-[#00d1b2] hover:bg-[#00bfa5] text-white cursor-pointer'"
                                class="w-full sm:w-auto font-bold py-3 px-6 rounded-xl text-xs tracking-wider uppercase transition-all shadow-xs text-center flex items-center justify-center gap-2">
                                <template x-if="isSubmitting"><i class="fa-solid fa-spinner fa-spin"></i></template>
                                <span x-text="isSubmitting ? 'Submitting...' : (isDuplicate ? 'Submission Blocked' : 'Submit Claim')"></span>
                            </button>

                            <button type="button" x-show="isExtracting" x-cloak disabled
                                class="w-full sm:w-auto bg-[#00bfa5] text-white font-bold py-3 px-6 rounded-xl text-xs tracking-wider uppercase flex items-center justify-center gap-2 shadow-xs opacity-90 cursor-not-allowed">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Extracting OCR Data...
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Image Full Modal Preview -->
    <div x-show="isModalOpen" x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-all duration-300">
        <div class="relative bg-white rounded-3xl p-3 max-w-2xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
            @click.away="isModalOpen = false">
            <div class="flex items-center justify-between px-4 py-2 border-b border-slate-100">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">
                    <i class="fa-solid fa-receipt mr-1"></i> Full View Receipt
                </span>
                <button type="button" @click="isModalOpen = false"
                    class="text-slate-400 hover:text-rose-600 transition-all text-lg cursor-pointer p-1">
                    <i class="fa-solid fa-circle-xmark"></i>
                </button>
            </div>
            <div class="p-2 bg-slate-50 rounded-2xl overflow-y-auto flex-1 flex justify-center items-center min-h-0">
                <img :src="imagePreview" alt="Receipt Full View"
                    class="max-w-full max-h-[75vh] object-contain rounded-xl">
            </div>
        </div>
    </div>

    <!-- WebRTC Camera OCR Modal -->
    <div x-show="isCameraOpen" x-cloak
        class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/90 backdrop-blur-sm transition-all duration-300">
        <div class="relative bg-black rounded-3xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col"
            @click.away="closeCamera()">
            <div class="absolute top-0 inset-x-0 p-4 flex justify-between items-center z-10 bg-gradient-to-b from-black/60 to-transparent">
                <span class="text-white text-xs font-bold uppercase tracking-wide flex items-center gap-2">
                    <i class="fa-solid fa-camera"></i> Quick Scan
                </span>
                <button type="button" @click="closeCamera()"
                    class="text-white/80 hover:text-white transition-all text-xl cursor-pointer p-1 bg-black/40 rounded-full w-8 h-8 flex items-center justify-center">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="relative w-full aspect-[3/4] bg-slate-800 flex items-center justify-center overflow-hidden">
                <video id="webrtc-video" autoplay playsinline class="w-full h-full object-cover"></video>
                <div class="absolute inset-8 border-2 border-emerald-400/80 rounded-xl shadow-[0_0_0_9999px_rgba(0,0,0,0.5)] pointer-events-none flex flex-col items-center justify-center">
                    <span class="bg-emerald-500 text-white text-[10px] font-bold px-2 py-1 rounded-md uppercase tracking-wider mb-2">Align Receipt Here</span>
                </div>
            </div>
            <div class="p-6 bg-slate-900 flex justify-center items-center gap-6">
                <button type="button" @click="captureAndProcess()"
                    class="w-16 h-16 rounded-full bg-white border-4 border-slate-300 flex items-center justify-center hover:scale-105 active:scale-95 transition-all shadow-lg cursor-pointer">
                    <div class="w-12 h-12 rounded-full border border-slate-300"></div>
                </button>
            </div>
        </div>
        <canvas id="webrtc-canvas" class="hidden"></canvas>
    </div>

    <!-- OCR Processing Modal Overlay -->
    <div x-show="isExtracting" x-cloak
        class="fixed inset-0 z-[200] flex flex-col items-center justify-center p-4 bg-slate-900/80 backdrop-blur-md transition-all duration-300">
        <div
            class="bg-white rounded-3xl p-6 md:p-8 max-w-sm w-full shadow-2xl border border-slate-100 flex flex-col items-center text-center space-y-5">
            <div class="relative w-16 h-16 flex items-center justify-center">
                <div class="absolute inset-0 rounded-full border-4 border-slate-100"></div>
                <div
                    class="absolute inset-0 rounded-full border-4 border-emerald-500 border-t-transparent animate-spin">
                </div>
                <i class="fa-solid fa-brain text-2xl text-emerald-500 animate-pulse absolute"></i>
            </div>
            <div class="space-y-1.5 w-full">
                <h3 class="text-sm font-black text-slate-900 tracking-tight uppercase">SmartClaim AI Core</h3>
                <p class="text-xs text-slate-500 leading-relaxed px-2">
                    Parsing document layout, running OCR text extraction, and predicting category via TF-IDF...
                </p>
            </div>
        </div>
    </div>

    <!-- Google Places API and Alpine.js Form Engine -->
    <script>
        function loadGoogleMaps(callback) {
            const apiKey = document.querySelector('meta[name="google-maps-api-key"]')?.getAttribute('content');
            if (!apiKey) {
                console.error('Google Maps API key is missing.');
                return;
            }
            if (window.google && window.google.maps && window.google.maps.Map) {
                callback();
                return;
            }
            if (document.getElementById('google-maps-script')) {
                // Already appended, wait for it
                window.addEventListener('google-maps-loaded', callback, { once: true });
                return;
            }
            window.initGoogleMapsCallback = function() {
                window.dispatchEvent(new Event('google-maps-loaded'));
                callback();
            };
            const script = document.createElement('script');
            script.id = 'google-maps-script';
            script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places,geometry&callback=initGoogleMapsCallback`;
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        }
    </script>
    <script>
        // Comment: Alpine.js form controller for SmartClaim receipt & mileage validation
        function ocrForm() {
            return {
                isMobileSidebarOpen: false,
                isSubmitting: false,
                activeForm: (new URLSearchParams(window.location.search)).get('type') === 'Mileage' ? 'Mileage' : 'Receipt',
                selectedVehicleId: '',
                vehicleType: 'Car',
                vehiclePlate: '',
                currentRate: 0.60,
                mileageKm: '',
                mileageTitle: '',
                allowanceTotal: '0.00',
                isExtracting: false,
                isDuplicate: false,
                duplicateMessage: '',
                isModalOpen: false,
                  isCameraOpen: false,
                  videoStream: null,
                  fileName: '',
                imagePreview: '',
                merchant: '',
                invoiceNo: '',
                location: '',
                date: '',
                amount: '',
                rawOcrAmount: '0.00',
                paymentMethod: 'Cash',
                category: '',
                businessPurpose: '',
                items: [],
                currencyMask: null,
                userLocationBounds: null,
                expensePolicies: @json($expensePolicies ?? []),

                get isPolicyBreached() {
                    if (this.activeForm !== 'Receipt' || !this.category || !this.amount) return false;
                    const policy = this.expensePolicies.find(p => p.category_name === this.category);
                    if (policy && policy.max_single_claim_limit) {
                        return parseFloat(this.amount) > parseFloat(policy.max_single_claim_limit);
                    }
                    return false;
                },

                get breachedPolicyLimit() {
                    const policy = this.expensePolicies.find(p => p.category_name === this.category);
                    return policy ? parseFloat(policy.max_single_claim_limit).toFixed(2) : '0.00';
                },

                init() {
                    const amountInput = document.getElementById('amount_input');
                    if (amountInput) {
                        this.currencyMask = IMask(amountInput, {
                            mask: Number,
                            scale: 2,
                            signed: false,
                            thousandsSeparator: ',',
                            padFractionalZeros: true,
                            normalizeZeros: true,
                            radix: '.',
                            mapToRadix: ['.']
                        });
                        this.currencyMask.on('accept', () => {
                            this.amount = this.currencyMask.unmaskedValue;
                        });
                        
                        this.$watch('amount', value => {
                            if (this.currencyMask && value !== this.currencyMask.unmaskedValue) {
                                this.currencyMask.unmaskedValue = String(value);
                            }
                        });
                    }

                    // Always call init logic; the loader handles async map loading
                    this.initGoogleMapsDependentLogic();
                },

                initGoogleMapsDependentLogic() {
                    loadGoogleMaps(() => {
                        if (this.activeForm === 'Mileage') {
                            this.$nextTick(() => { this.initializeGooglePlacesEngine(); });
                        }
                        
                        if (navigator.geolocation) {
                            navigator.geolocation.getCurrentPosition((position) => {
                                if (typeof google === 'undefined') return;
                                const pos = { lat: position.coords.latitude, lng: position.coords.longitude };
                                const circle = new google.maps.Circle({
                                    center: pos,
                                    radius: position.coords.accuracy
                                });
                                this.userLocationBounds = circle.getBounds();
                            }, () => {
                                // Default location fallback if needed
                            });
                        }
                    });
                },

                // Helper to check fuel categories dynamically
                isFuelCategory() {
                    return ['Fuel & Fleet Logistics', 'Fuel / Automotive', 'Fuel'].includes(this.category);
                },

                switchForm(formType) {
                    this.activeForm = formType;
                    const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?type=' + formType;
                    window.history.pushState({ path: newUrl }, '', newUrl);

                    if (formType === 'Mileage') {
                        this.$nextTick(() => { this.initializeGooglePlacesEngine(); });
                    }
                },

                updateVehicleDetails(event) {
                    const selectedOption = event.target.options[event.target.selectedIndex];
                    this.vehicleType = selectedOption.getAttribute('data-type') || 'Car';
                    this.vehiclePlate = selectedOption.getAttribute('data-plate') || '';
                    this.currentRate = parseFloat(selectedOption.getAttribute('data-rate')) || (this.vehicleType.toLowerCase() === 'motorcycle' ? 0.30 : 0.60);
                    this.calculateAllowance();
                    this.calculateManualAllowance();
                },

                initializeGooglePlacesEngine() {
                    const startAddressField = document.getElementById('origin');
                    const targetAddressField = document.getElementById('destination');
                    if (!startAddressField || !targetAddressField || typeof google === 'undefined') return;

                    const geolocationOptions = { componentRestrictions: { country: 'my' } };
                    
                    if (this.userLocationBounds) {
                        geolocationOptions.bounds = this.userLocationBounds;
                    }
                    
                    const originAutocomplete = new google.maps.places.Autocomplete(startAddressField, geolocationOptions);
                    const destinationAutocomplete = new google.maps.places.Autocomplete(targetAddressField, geolocationOptions);

                    originAutocomplete.addListener('place_changed', () => { this.calculateAllowance(); });
                    destinationAutocomplete.addListener('place_changed', () => { this.calculateAllowance(); });
                },

                calculateAllowance() {
                    const originValue = document.getElementById('origin')?.value;
                    const destinationValue = document.getElementById('destination')?.value;
                    if (!originValue || !destinationValue || typeof google === 'undefined') return;

                    const distanceMatrixEngine = new google.maps.DistanceMatrixService();
                    distanceMatrixEngine.getDistanceMatrix({
                        origins: [originValue],
                        destinations: [destinationValue],
                        travelMode: 'DRIVING',
                        unitSystem: google.maps.UnitSystem.METRIC,
                    }, (matrixDataResponse, callStatus) => {
                        if (callStatus === 'OK') {
                            const resultMatrixElement = matrixDataResponse.rows[0].elements[0];
                            if (resultMatrixElement.status === 'OK') {
                                const computedKm = (resultMatrixElement.distance.value / 1000);
                                // Pure number string without ' KM' suffix to pass numeric validation
                                this.mileageKm = computedKm.toFixed(2);
                                this.allowanceTotal = (computedKm * this.currentRate).toFixed(2);
                                this.amount = this.allowanceTotal;
                            } else {
                                Swal.fire({ icon: 'warning', title: 'Route Error', text: 'Addresses could not be resolved for driving transit paths.' });
                            }
                        }
                    });
                },

                calculateManualAllowance() {
                    let rawKm = parseFloat(this.mileageKm);
                    if (!isNaN(rawKm) && rawKm > 0) {
                        this.allowanceTotal = (rawKm * this.currentRate).toFixed(2);
                    } else {
                        this.allowanceTotal = '0.00';
                    }
                    this.amount = this.allowanceTotal;
                },

                handleFileChange(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    this.fileName = file.name;
                    if (this.imagePreview) { URL.revokeObjectURL(this.imagePreview); }
                    this.imagePreview = URL.createObjectURL(file);
                    this.isExtracting = true;
                    this.isDuplicate = false;
                    this.duplicateMessage = '';

                    let formData = new FormData();
                    formData.append('receipt', file);
                    formData.append('_token', '{{ csrf_token() }}');

                    fetch('{{ route("claims.asyncScan") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            this.isExtracting = false;

                            if (data.is_duplicate_image) {
                                this.isDuplicate = true;
                                this.duplicateMessage = data.duplicate_reason;
                                this.merchant = '';
                                this.invoiceNo = '';
                                this.location = '';
                                this.amount = '';
                                this.items = [];

                                Swal.fire({
                                    icon: 'error',
                                    title: 'Duplicate Receipt Detected',
                                    text: data.duplicate_reason,
                                    confirmButtonColor: '#e11d48'
                                });
                                return;
                            }

                            if (data.is_tampered) {
                                this.isDuplicate = true;
                                this.duplicateMessage = data.tamper_reason;

                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Tampered Image Blocked',
                                    text: data.tamper_reason,
                                    confirmButtonColor: '#e11d48'
                                });
                                return;
                            }

                            if (data.success) {
                                this.merchant = data.merchant_name;
                                this.invoiceNo = data.receipt_invoice_no;
                                this.location = data.location_address;
                                this.date = data.transaction_date;
                                this.amount = data.amount;
                                this.rawOcrAmount = data.amount;
                                this.paymentMethod = data.payment_method;
                                if (data.predicted_category) { this.category = data.predicted_category; }

                                this.items = Array.isArray(data.items) ? data.items : [];
                                document.getElementById('extracted_raw_text').value = data.raw_text;
                                this.$nextTick(() => { this.checkDuplicateAndPopup(); });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Extraction Notice',
                                    text: data.message || 'Unable to parse receipt details.'
                                });
                            }
                        })
                        .catch(error => {
                            console.error("OCR Error:", error);
                            this.isExtracting = false;
                            Swal.fire({
                                icon: 'error',
                                title: 'Network Error',
                                text: 'Failed to communicate with OCR server. Please check your connection.'
                            });
                        });
                },

                openCamera() {
                    this.isCameraOpen = true;
                    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                        .then(stream => {
                            this.videoStream = stream;
                            const videoEl = document.getElementById('webrtc-video');
                            if (videoEl) {
                                videoEl.srcObject = stream;
                            }
                        })
                        .catch(err => {
                            console.error('Camera access denied or unavailable.', err);
                            this.isCameraOpen = false;
                            Swal.fire('Camera Error', 'Unable to access your device camera. Please check permissions.', 'error');
                        });
                },

                closeCamera() {
                    if (this.videoStream) {
                        this.videoStream.getTracks().forEach(track => track.stop());
                        this.videoStream = null;
                    }
                    this.isCameraOpen = false;
                },

                captureAndProcess() {
                    const videoEl = document.getElementById('webrtc-video');
                    const canvas = document.getElementById('webrtc-canvas');
                    if (!videoEl || !canvas) return;

                    canvas.width = videoEl.videoWidth;
                    canvas.height = videoEl.videoHeight;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(videoEl, 0, 0, canvas.width, canvas.height);

                    canvas.toBlob((blob) => {
                        this.closeCamera();
                        const file = new File([blob], 'camera_capture.jpg', { type: 'image/jpeg' });
                        this.handleFileChange({ target: { files: [file] } });
                    }, 'image/jpeg', 0.85);
                },

                addItemRow() {
                    this.items.push({ item_name: '', quantity: 1, unit_price: '0.00', subtotal: '0.00' });
                },

                removeItemRow(index) {
                    this.items.splice(index, 1);
                    this.recalculateGrandTotalFromItems();
                },

                recalculateItem(index) {
                    let q = parseInt(this.items[index].quantity) || 1;
                    let u = parseFloat(this.items[index].unit_price) || 0;
                    this.items[index].subtotal = (q * u).toFixed(2);
                    this.recalculateGrandTotalFromItems();
                },

                recalculateGrandTotalFromItems() {
                    if (this.items.length > 0) {
                        let sum = this.items.reduce((total, cur) => total + (parseFloat(cur.subtotal) || 0), 0);
                        this.amount = sum.toFixed(2);
                    }
                },

                triggerReupload() {
                    this.imagePreview = '';
                    this.fileName = '';
                    this.merchant = '';
                    this.invoiceNo = '';
                    this.location = '';
                    this.date = '';
                    this.amount = '';
                    this.rawOcrAmount = '0.00';
                    this.category = '';
                    this.isDuplicate = false;
                    this.duplicateMessage = '';
                    this.items = [];
                    const receiptInput = document.getElementById('receipt');
                    if (receiptInput) { receiptInput.value = ''; }
                    document.getElementById('extracted_raw_text').value = '';
                    this.$nextTick(() => { if (receiptInput) { receiptInput.click(); } });
                },

                checkDuplicateAndPopup() {
                    if (!this.invoiceNo || !this.amount || this.invoiceNo === 'NOT FOUND') {
                        return;
                    }

                    let cleanAmount = parseFloat(this.amount).toFixed(2);
                    let cleanInvoice = this.invoiceNo.trim();

                    fetch('{{ route("claims.checkDuplicate") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            invoice_no: cleanInvoice,
                            amount: cleanAmount
                        })
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.duplicate) {
                                this.isDuplicate = true;
                                this.duplicateMessage = "Security Interception: A voucher record with identical Invoice No (" + cleanInvoice + ") and Amount (RM " + cleanAmount + ") already exists in the system.";
                            }
                        })
                        .catch(err => {
                            console.error("Duplicate check failed:", err);
                        });
                },

                submitForm(e) {
                    if (this.activeForm === 'Receipt') {
                        if (this.isDuplicate) {
                            e.preventDefault();
                            Swal.fire({
                                icon: 'error',
                                title: 'Submission Blocked',
                                text: this.duplicateMessage || 'Duplicate receipt record detected. Please upload an authentic receipt.'
                            });
                            return false;
                        }
                        if (!this.category || this.category === '') {
                            e.preventDefault();
                            Swal.fire({ icon: 'warning', text: 'Please select an expense category.' });
                            return false;
                        }
                        if (this.isFuelCategory() && !this.vehiclePlate) {
                            e.preventDefault();
                            Swal.fire({ icon: 'warning', text: 'Please select an active company fleet vehicle for fuel expenditure.' });
                            return false;
                        }
                    } else if (this.activeForm === 'Mileage') {
                        if (!this.selectedVehicleId) {
                            e.preventDefault();
                            Swal.fire({ icon: 'warning', text: 'Please select an authorized personal vehicle before submitting.' });
                            return false;
                        }
                        this.amount = this.allowanceTotal;
                    }
                    this.isSubmitting = true;
                    return true;
                }
            };
        }
    </script>
</body>

</html>
