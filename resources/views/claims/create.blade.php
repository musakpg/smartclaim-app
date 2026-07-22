<!DOCTYPE html>
<html lang="en" x-data="ocrForm()">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Submit Claim</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased"
    :class="isModalOpen || isMobileSidebarOpen ? 'overflow-hidden' : ''">

    <div class="flex min-h-screen flex-col lg:flex-row">

        <header
            class="lg:hidden bg-white border-b border-[#e2e8f0] px-4 py-4 flex items-center justify-between sticky top-0 z-40 shadow-2xs">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-wallet text-slate-800 text-xl"></i>
                <span class="font-bold text-lg tracking-tight text-slate-900">SmartClaim</span>
            </div>
            <button type="button" @click="isMobileSidebarOpen = true"
                class="w-9 h-9 flex items-center justify-center bg-slate-100 rounded-xl text-slate-700 cursor-pointer transition-all">
                <i class="fa-solid fa-bars text-base"></i>
            </button>
        </header>

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
                        :class="window.location.search === '' && window.location.pathname.includes('dashboard') ? 'text-blue-600 font-bold bg-blue-50/60' : 'text-slate-500 hover:bg-slate-50'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium">
                        <i class="fa-solid fa-house"></i> Dashboard
                    </a>

                    <div>
                        <button type="button" @click.prevent="isClaimsOpenMobile = !isClaimsOpenMobile"
                            class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-semibold bg-slate-100 text-slate-900 transition-all cursor-pointer">
                            <span class="flex items-center gap-3 pointer-events-none"><i
                                    class="fa-solid fa-file-pen"></i> Claims</span>
                            <i class="fa-solid text-[10px] transition-transform duration-200"
                                :class="isClaimsOpenMobile ? 'fa-chevron-down rotate-180' : 'fa-chevron-right'"></i>
                        </button>

                        <div x-show="isClaimsOpenMobile" x-cloak x-transition
                            class="pl-6 mt-1 space-y-1 py-1 bg-slate-50 rounded-xl border border-slate-100">
                            <button type="button" @click="switchForm('Receipt'); isMobileSidebarOpen = false;"
                                :class="activeForm === 'Receipt' ? 'text-blue-600 font-bold bg-blue-50/60' : 'text-slate-500 hover:text-slate-900'"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-2 cursor-pointer">
                                <i class="fa-solid fa-file-invoice text-[11px]"
                                    :class="activeForm === 'Receipt' ? 'text-blue-600' : 'text-slate-400'"></i> Based on
                                Receipt (OCR)
                            </button>

                            <button type="button" @click="switchForm('Mileage'); isMobileSidebarOpen = false;"
                                :class="activeForm === 'Mileage' ? 'text-blue-600 font-bold bg-blue-50/60' : 'text-slate-500 hover:text-slate-900'"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-2 cursor-pointer">
                                <i class="fa-solid fa-motorcycle text-[11px]"
                                    :class="activeForm === 'Mileage' ? 'text-blue-600' : 'text-slate-400'"></i> Mileage
                                Allowance
                            </button>

                            <a href="{{ route('claims.history') }}"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-slate-500 hover:text-slate-900 flex items-center gap-2">
                                <i class="fa-solid fa-clipboard-list text-[11px] text-slate-400"></i> My Claims
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

        <aside
            class="hidden lg:flex fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-[#e2e8f0] flex-col h-screen sticky top-0"
            x-data="{ isClaimsOpen: true }">
            <div class="px-6 py-5 border-b border-[#f1f5f9] flex items-center gap-2">
                <i class="fa-solid fa-wallet text-slate-800 text-2xl"></i>
                <span class="font-bold text-xl tracking-tight text-slate-900">SmartClaim</span>
            </div>

            <nav class="flex-1 px-4 py-4 space-y-1">
                <a href="{{ route('dashboard') }}"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-50 hover:text-slate-800 transition-all">
                    <i class="fa-solid fa-house text-base"></i> Dashboard
                </a>

                <div>
                    <button type="button" @click.prevent="isClaimsOpen = !isClaimsOpen"
                        class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-semibold bg-slate-100 text-slate-900 transition-all cursor-pointer">
                        <span class="flex items-center gap-3 pointer-events-none">
                            <i class="fa-solid fa-file-pen text-base"></i> Claims
                        </span>
                        <i class="fa-solid text-[10px] transition-transform duration-200 pointer-events-none"
                            :class="isClaimsOpen ? 'fa-chevron-down rotate-180' : 'fa-chevron-right'"></i>
                    </button>

                    <div x-show="isClaimsOpen" x-cloak x-transition
                        class="pl-6 mt-1 space-y-1 py-1 bg-slate-50 rounded-xl border border-slate-100">
                        <button type="button" @click="switchForm('Receipt')"
                            :class="activeForm === 'Receipt' ? 'text-blue-600 font-bold bg-blue-50/60' : 'text-slate-500 hover:text-slate-900'"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium transition-all cursor-pointer flex items-center gap-2">
                            <i class="fa-solid fa-file-invoice text-[11px]"
                                :class="activeForm === 'Receipt' ? 'text-blue-600' : 'text-slate-400'"></i> Based on
                            Receipt (OCR)
                        </button>

                        <button type="button" @click="switchForm('Mileage')"
                            :class="activeForm === 'Mileage' ? 'text-blue-600 font-bold bg-blue-50/60' : 'text-slate-500 hover:text-slate-900'"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium transition-all cursor-pointer flex items-center gap-2">
                            <i class="fa-solid fa-motorcycle text-[11px]"
                                :class="activeForm === 'Mileage' ? 'text-blue-600' : 'text-slate-400'"></i> Mileage
                            Allowance
                        </button>

                        <a href="{{ route('claims.history') }}"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-slate-500 hover:text-slate-900 transition-all flex items-center gap-2">
                            <i class="fa-solid fa-clipboard-list text-[11px]"></i> My Claims
                        </a>
                    </div>
                </div>

                <a href="{{ route('reimbursement.index') }}"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-50 hover:text-slate-800 transition-all">
                    <i class="fa-solid fa-hand-holding-dollar text-base"></i> Reimbursement Status
                </a>
                <a href="{{ route('profile.index') }}"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-50 hover:text-slate-800 transition-all">
                    <i class="fa-solid fa-user text-base"></i> My Profile
                </a>
                <a href="{{ route('policy.index') }}"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-50 hover:text-slate-800 transition-all">
                    <i class="fa-solid fa-file-shield text-base"></i> Company Policy
                </a>
                <a href="{{ route('logout') }}"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition-all">
                    <i class="fa-solid fa-door-open text-base"></i> Sign Out
                </a>
            </nav>
        </aside>

        <main class="flex-1 p-4 md:p-8 max-w-4xl mx-auto w-full pb-24 lg:pb-8 overflow-hidden">
            <div class="space-y-6">

                <div class="space-y-0.5">
                    <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight"
                        x-text="activeForm === 'Receipt' ? 'Submit Expense Claim' : 'Submit Mileage Allowance'"></h1>
                    <p class="text-xs md:text-sm text-slate-500"
                        x-text="activeForm === 'Receipt' ? 'Upload a receipt and verify the AI-parsed details before submitting.' : 'Log your journey distance parameters and attach validation files to generate travel reimbursement packets.'">
                    </p>
                </div>

                <div class="flex items-center p-1 bg-slate-200/60 rounded-xl w-full shadow-3xs text-xs mb-2">
                    <button type="button" @click="switchForm('Receipt')"
                        :class="activeForm === 'Receipt' ? 'bg-white text-slate-900 font-bold shadow-xs border border-slate-300' : 'text-slate-500 hover:text-slate-900 font-medium'"
                        class="flex-1 py-2.5 rounded-lg transition-all cursor-pointer flex items-center justify-center gap-2"><i
                            class="fa-solid fa-file-invoice"></i> Based on Receipt</button>
                    <button type="button" @click="switchForm('Mileage')"
                        :class="activeForm === 'Mileage' ? 'bg-white text-blue-600 font-bold shadow-xs border border-slate-300' : 'text-slate-500 hover:text-slate-900 font-medium'"
                        class="flex-1 py-2.5 rounded-lg transition-all cursor-pointer flex items-center justify-center gap-2"><i
                            class="fa-solid fa-route"></i> Mileage Allowance</button>
                </div>

                <div x-show="isDuplicate && activeForm === 'Receipt'" x-cloak x-transition
                    class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-xs font-semibold space-y-1 shadow-xs animate-fade-in">
                    <p class="font-bold text-sm text-rose-900 flex items-center gap-1.5">
                        <i class="fa-solid fa-triangle-exclamation text-rose-500 text-base"></i> Submission Blocked!
                        Duplicate Record Intercepted:
                    </p>
                    <ul class="list-disc pl-5 space-y-0.5 font-medium text-rose-700">
                        <li>Security Interception: A voucher record with identical Invoice No and Amount already exists
                            in records.</li>
                        <li>Please click 'Change Image' and upload a different merchant receipt to clear the fraud lock.
                        </li>
                    </ul>
                </div>

                <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-xs">

                    <form id="claimForm" action="{{ route('claims.store') }}" method="POST"
                        enctype="multipart/form-data" class="space-y-6" @submit="submitForm($event)">
                        @csrf

                        <input type="hidden" name="claim_type" :value="activeForm">
                        <input type="hidden" name="extracted_raw_text" id="extracted_raw_text">

                        <template x-if="activeForm === 'Receipt' && isCategoryLocked">
                            <input type="hidden" name="category" :value="category">
                        </template>

                        <div x-show="activeForm === 'Receipt'" class="space-y-6" x-transition>
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
                                                    class="text-blue-600 group-hover:underline">click to browse
                                                    gallery</span>
                                            </h3>
                                            <p class="text-slate-400 text-[10px] md:text-xs font-medium">JPEG or PNG —
                                                Max 5MB</p>
                                        </div>
                                    </div>

                                    <div class="block sm:hidden relative">
                                        <input type="file" id="mobile_camera_capture" accept="image/*"
                                            capture="environment"
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-30"
                                            @change="handleFileChange($event)">
                                        <button type="button"
                                            class="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-xs uppercase tracking-wider flex items-center justify-center gap-2 shadow-xs transition-all cursor-pointer">
                                            <i class="fa-solid fa-camera text-sm text-emerald-400"></i> Snap Receipt via
                                            Camera Direct
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
                                                x-text="'File Attached: ' + fileName"></p>
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
                                            image details
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 md:gap-y-5">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700 tracking-wide">Merchant
                                        Name</label>
                                    <input type="text" name="merchant_name" x-model="merchant"
                                        placeholder="e.g. Acme Coffee Co." :required="activeForm === 'Receipt'"
                                        class="w-full px-4 py-2.5 md:py-3 bg-white border border-slate-200 rounded-xl outline-none text-xs md:text-sm shadow-2xs">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700 tracking-wide">Receipt/Invoice
                                        No.</label>
                                    <input type="text" name="receipt_invoice_no" x-model="invoiceNo"
                                        @input.debounce.250ms="checkDuplicateAndPopup()"
                                        placeholder="Detecting invoice number..." :required="activeForm === 'Receipt'"
                                        class="w-full px-4 py-2.5 md:py-3 bg-white border border-slate-200 rounded-xl outline-none text-xs md:text-sm shadow-2xs">
                                </div>
                                <div class="space-y-1.5 md:col-span-2">
                                    <label class="block text-xs font-bold text-slate-700 tracking-wide">Location (Branch
                                        Address)</label>
                                    <input type="text" name="location_address" x-model="location"
                                        placeholder="Detecting store address..." :required="activeForm === 'Receipt'"
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
                                        <input type="number" step="0.01" name="amount" x-model="amount"
                                            @input.debounce.250ms="checkDuplicateAndPopup()" placeholder="0.00"
                                            :required="activeForm === 'Receipt'"
                                            class="w-full pl-11 pr-4 py-2.5 md:py-3 bg-white border border-slate-200 rounded-xl outline-none text-xs md:text-sm shadow-2xs">
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
                                            <option value="Meals & Entertainment">Meals & Entertainment</option>
                                            <option value="Travel">Travel</option>
                                            <option value="Transportation">Transportation</option>
                                            <option value="Office Supplies">Office Supplies</option>
                                            <option value="Fuel / Automotive">Fuel / Automotive</option>
                                        </select>
                                        <span
                                            class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 text-xs"><i
                                                class="fa-solid fa-chevron-down"></i></span>
                                    </div>
                                </div>

                                <div class="space-y-1.5"
                                    x-show="(category === 'Fuel / Automotive' || category === 'Fuel')" x-transition
                                    x-cloak>
                                    <label class="block text-xs font-bold text-rose-700 tracking-wide">
                                        <i class="fa-solid fa-car-side"></i> Select Registered Vehicle / Plate Number *
                                    </label>
                                    <div class="relative">
                                        <select name="vehicle_plate_number" x-model="vehiclePlate"
                                            :required="(category === 'Fuel / Automotive' || category === 'Fuel') && activeForm === 'Receipt'"
                                            :disabled="activeForm !== 'Receipt'"
                                            class="w-full px-4 py-2.5 md:py-3 bg-rose-50/50 border border-rose-200 focus:border-rose-400 rounded-xl outline-none text-xs md:text-sm font-bold shadow-2xs appearance-none transition-all">
                                            <option value="" disabled selected>-- Choose Registered Plate --</option>
                                            @forelse($myVehicles ?? [] as $vehicle)
                                                <option value="{{ $vehicle->plate_number }}">
                                                    {{ $vehicle->plate_number }} — {{ $vehicle->brand_model }}
                                                </option>
                                            @empty
                                                <option value="" disabled>No registered vehicles found. Please contact
                                                    Manager.</option>
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
                                            class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 text-xs"><i
                                                class="fa-solid fa-chevron-down"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-show="activeForm === 'Mileage'" class="space-y-5" x-transition style="display: none;">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 md:gap-y-5 text-xs">
                                <div class="md:col-span-2 space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700 tracking-wide">Purpose / Title
                                        of Travel</label>
                                    <input type="text" name="title" x-model="mileageTitle"
                                        placeholder="e.g. Client meeting at Aero Art HQ"
                                        :required="activeForm === 'Mileage'"
                                        class="w-full px-4 py-2.5 md:py-3 bg-white border border-slate-200 rounded-xl outline-none text-xs md:text-sm shadow-2xs">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-emerald-700 tracking-wide"><i
                                            class="fa-solid fa-location-dot"></i> Starting Location</label>
                                    <input type="text" id="origin" name="start_location"
                                        placeholder="Search origin location via Google Places..."
                                        :required="activeForm === 'Mileage'"
                                        class="w-full px-4 py-2.5 md:py-3 bg-white border border-slate-200 rounded-xl outline-none text-xs md:text-sm shadow-2xs">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-rose-700 tracking-wide"><i
                                            class="fa-solid fa-location-pin-lock"></i> Destination Location</label>
                                    <input type="text" id="destination" name="destination_location"
                                        placeholder="Search destination address via Google Places..."
                                        :required="activeForm === 'Mileage'"
                                        class="w-full px-4 py-2.5 md:py-3 bg-white border border-slate-200 rounded-xl outline-none text-xs md:text-sm shadow-2xs">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700 tracking-wide">Vehicle
                                        Classification Type</label>
                                    <div class="relative">
                                        <select name="vehicle_type" x-model="vehicleType"
                                            @change="calculateAllowance(); calculateManualAllowance();"
                                            :required="activeForm === 'Mileage'"
                                            class="w-full px-4 py-2.5 md:py-3 bg-white border border-slate-200 rounded-xl outline-none text-xs md:text-sm shadow-2xs appearance-none font-bold">
                                            <option value="Car">Car (RM 0.60 / KM)</option>
                                            <option value="Motorcycle">Motorcycle (RM 0.30 / KM)</option>
                                        </select>
                                        <span
                                            class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400 text-xs"><i
                                                class="fa-solid fa-chevron-down"></i></span>
                                    </div>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700 tracking-wide">Total Distance
                                        Traveled (KM)</label>
                                    <input type="text" id="distance" name="mileage_km" x-model="mileageKm"
                                        @input="calculateManualAllowance()" placeholder="e.g. 12.50 KM"
                                        :required="activeForm === 'Mileage'"
                                        readonly class="w-full px-4 py-2.5 md:py-3 border border-slate-200 bg-white text-slate-700 rounded-xl outline-none font-mono font-bold text-xs md:text-sm shadow-2xs">

                                    <div x-show="activeForm === 'Mileage'" class="px-0.5 pt-1">
                                        <p
                                            class="text-[9px] text-slate-400 italic font-sans leading-relaxed flex items-start gap-1 bg-slate-50/80 p-2 rounded-lg border border-dashed border-slate-200">
                                            <i
                                                class="fa-solid fa-circle-info text-blue-500 mt-0.5 shrink-0 text-[10px]"></i>
                                            <span>
                                                <strong>Audit Note:</strong> Jarak variasi dijana automatik oleh Google
                                                Distance Matrix API berdasarkan rute logistik jalan raya paling optimum.
                                                Perbezaan kecil dengan odometer mekanikal kenderaan adalah sah di bawah
                                                pematuhan had toleransi audit Aero Art Sdn Bhd.
                                            </span>
                                        </p>
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
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-slate-700 tracking-wide">Vehicle Plate
                                        Registration No.</label>
                                    <input type="text" name="vehicle_plate_number" placeholder="e.g. WXD 8421"
                                        :required="activeForm === 'Mileage'" :disabled="activeForm !== 'Mileage'"
                                        class="w-full px-4 py-2.5 md:py-3 bg-white border border-slate-200 rounded-xl outline-none font-mono font-bold text-xs md:text-sm shadow-2xs">
                                </div>
                                <div class="space-y-1.5 md:col-span-2">
                                    <label class="block text-xs font-bold text-blue-700 tracking-wide"><i
                                            class="fa-solid fa-paperclip"></i> Upload Proof of Travel *</label>
                                    <input type="file" name="mileage_document" id="mileage_document" accept="image/*"
                                        :required="activeForm === 'Mileage'" :disabled="activeForm !== 'Mileage'"
                                        class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs md:text-sm font-semibold text-slate-600 file:mr-4 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-[10px] md:file:text-xs file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer">
                                </div>
                            </div>

                            <div
                                class="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between mt-2 text-xs">
                                <div>
                                    <span
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Estimated
                                        Reimbursement Reward</span>
                                    <span class="text-xl md:text-2xl font-black text-emerald-600 font-mono"
                                        x-text="'RM ' + allowanceTotal"></span>
                                </div>
                                <div
                                    class="w-full sm:w-auto text-left sm:text-right text-[10px] md:text-[11px] font-bold text-slate-500 bg-white border border-slate-200/60 shadow-xs px-3 py-1.5 rounded-xl">
                                    Rate Applied: <span class="text-emerald-500 font-black"
                                        x-text="vehicleType === 'Car' ? 'RM 0.60 / KM' : 'RM 0.30 / KM'"></span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-1.5 text-xs">
                            <label class="block text-xs font-bold text-slate-700 tracking-wide">Business Purpose
                                Context</label>
                            <textarea name="business_purpose" x-model="businessPurpose" rows="3"
                                placeholder="Describe the corporate purpose of this expense/journey claim packet..."
                                required
                                class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl outline-none text-xs md:text-sm shadow-2xs resize-none"></textarea>
                        </div>

                        <div class="flex flex-col sm:flex-row justify-end gap-2 pt-4 border-t border-slate-100"
                            x-effect="if(isDuplicate) { activeForm = activeForm }">
                            <button type="submit" x-show="!isExtracting" :disabled="isDuplicate"
                                :class="isDuplicate ? 'bg-slate-200 text-slate-400 border border-slate-300/60 cursor-not-allowed opacity-70' : 'bg-[#00d1b2] hover:bg-[#00bfa5] text-white cursor-pointer'"
                                class="w-full sm:w-auto font-bold py-3 px-6 rounded-xl text-xs tracking-wider uppercase transition-all shadow-xs text-center text-white">
                                <span x-text="isDuplicate ? 'Submission Blocked' : 'Submit Claim'"></span>
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
                                Extracting Assets...
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </main>

        <nav
            class="lg:hidden fixed bottom-0 inset-x-0 bg-white border-t border-[#e2e8f0] h-16 flex items-center justify-around z-40 px-2 shadow-md">
            <a href="{{ route('dashboard') }}"
                class="flex flex-col items-center justify-center flex-1 h-full py-2 text-slate-400">
                <i class="fa-solid fa-chart-pie text-xl block mb-0.5"></i><span
                    class="text-[10px] font-bold">Dashboard</span>
            </a>
            <a href="{{ route('claims.create') }}?type=Receipt"
                class="flex flex-col items-center justify-center flex-1 h-full py-2 text-[#3b82f6]">
                <i class="fa-solid fa-file-circle-plus text-xl block mb-0.5"></i><span class="text-[10px] font-bold">New
                    Claim</span>
            </a>
            <a href="{{ route('claims.history') }}"
                class="flex flex-col items-center justify-center flex-1 h-full py-2 text-slate-400">
                <i class="fa-solid fa-clock-rotate-left text-xl block mb-0.5"></i><span
                    class="text-[10px] font-bold">History</span>
            </a>
        </nav>

    </div>

    <div x-show="isModalOpen" x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-all duration-300">
        <div class="relative bg-white rounded-3xl p-3 max-w-2xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
            @click.away="isModalOpen = false">
            <div class="flex items-center justify-between px-4 py-2 border-b border-slate-100">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wide"><i
                        class="fa-solid fa-receipt mr-1"></i> Full View Receipt</span>
                <button type="button" @click="isModalOpen = false"
                    class="text-slate-400 hover:text-rose-600 transition-all text-lg cursor-pointer p-1"><i
                        class="fa-solid fa-circle-xmark"></i></button>
            </div>
            <div class="p-2 bg-slate-50 rounded-2xl overflow-y-auto flex-1 flex justify-center items-center min-h-0">
                <img :src="imagePreview" alt="Receipt Full Modal View"
                    class="max-w-full max-h-[75vh] object-contain rounded-xl">
            </div>
        </div>
    </div>

    <div x-show="isExtracting" x-cloak
        class="fixed inset-0 z-[200] flex flex-col items-center justify-center p-4 bg-slate-900/80 backdrop-blur-md transition-all duration-300">
        <div class="bg-white rounded-3xl p-6 md:p-8 max-w-sm w-full shadow-2xl border border-slate-100 flex flex-col items-center text-center space-y-5"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="relative w-16 h-16 flex items-center justify-center">
                <div class="absolute inset-0 rounded-full border-4 border-slate-100"></div>
                <div
                    class="absolute inset-0 rounded-full border-4 border-emerald-500 border-t-transparent animate-spin">
                </div>
                <i class="fa-solid fa-brain-circuit text-2xl text-emerald-500 animate-pulse absolute"></i>
            </div>
            <div class="space-y-1.5 w-full">
                <h3 class="text-sm font-black text-slate-900 tracking-tight uppercase">SmartClaim AI Core</h3>
                <p class="text-xs text-slate-500 leading-relaxed px-2">
                    Sila tunggu sebentar. Sistem sedang mengekstrak metadata resit and menapis kategori perbelanjaan
                    Aero Art...
                </p>
            </div>
            <div
                class="px-3 py-1 bg-slate-50 border border-slate-200/60 rounded-xl flex items-center gap-2 text-[10px] font-bold font-mono text-slate-600 uppercase tracking-wider">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                <span>Status: Extracting OCR Data Node</span>
            </div>
        </div>
    </div>

    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places"></script>
    <script>
        function ocrForm() {
            return {
                isMobileSidebarOpen: false,
                activeForm: (new URLSearchParams(window.location.search)).get('type') === 'Mileage' ? 'Mileage' : 'Receipt',
                vehicleType: 'Car', mileageKm: '', mileageTitle: '', allowanceTotal: '0.00',
                isExtracting: false, isDuplicate: false, isModalOpen: false, fileName: '', imagePreview: '',
                merchant: '', invoiceNo: '', location: '', date: '', amount: '', paymentMethod: 'Cash',
                category: '', vehiclePlate: '', businessPurpose: '', items: [],
                isCategoryLocked: false,

                init() {
                    if (this.activeForm === 'Mileage') {
                        this.$nextTick(() => { this.initializeGooglePlacesEngine(); });
                    }
                },

                switchForm(formType) {
                    this.activeForm = formType;
                    const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?type=' + formType;
                    window.history.pushState({ path: newUrl }, '', newUrl);

                    if (formType === 'Mileage') {
                        this.$nextTick(() => { this.initializeGooglePlacesEngine(); });
                    }
                },

                initializeGooglePlacesEngine() {
                    const startAddressField = document.getElementById('origin');
                    const targetAddressField = document.getElementById('destination');
                    if (!startAddressField || !targetAddressField) return;

                    const geolocationOptions = { componentRestrictions: { country: 'my' } };
                    const originAutocomplete = new google.maps.places.Autocomplete(startAddressField, geolocationOptions);
                    const destinationAutocomplete = new google.maps.places.Autocomplete(targetAddressField, geolocationOptions);

                    originAutocomplete.addListener('place_changed', () => { this.calculateAllowance(); });
                    destinationAutocomplete.addListener('place_changed', () => { this.calculateAllowance(); });
                },

                calculateAllowance() {
                    const originValue = document.getElementById('origin').value;
                    const destinationValue = document.getElementById('destination').value;
                    if (!originValue || !destinationValue) return;

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
                                this.mileageKm = computedKm.toFixed(2) + " KM";
                                const runningRate = (this.vehicleType === 'Car') ? 0.60 : 0.30;
                                this.allowanceTotal = (computedKm * runningRate).toFixed(2);
                            } else {
                                alert('Matrix Integration Error: Addresses could not be validated for land transit paths.');
                            }
                        } else {
                            console.error('Distance Matrix Execution Failure Flags: ' + callStatus);
                        }
                    });
                },

                calculateManualAllowance() {
                    let rawKm = parseFloat(this.mileageKm.replace(/[^\d.]/g, ''));
                    if (!isNaN(rawKm) && rawKm > 0) {
                        const runningRate = (this.vehicleType === 'Car') ? 0.60 : 0.30;
                        this.allowanceTotal = (rawKm * runningRate).toFixed(2);
                    } else {
                        this.allowanceTotal = '0.00';
                    }
                },

                handleFileChange(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    this.fileName = file.name;
                    if (this.imagePreview) { URL.revokeObjectURL(this.imagePreview); }
                    this.imagePreview = URL.createObjectURL(file);

                    this.isExtracting = true;

                    let formData = new FormData();
                    formData.append('receipt', file);
                    formData.append('_token', '{{ csrf_token() }}');

                    fetch('{{ route("claims.asyncScan") }}', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            this.isExtracting = false;

                            if (data.success) {
                                // 1. SEKATAN ANTIFRAUD A: GAMBAR RANDOM (BUKAN RESIT)
                                const rawTextLower = (data.raw_text || '').toLowerCase();
                                const financialAnchors = ['total', 'rm', 'invoice', 'tax', 'cash', 'amount', 'thank', 'receipt', 'price', 'qty', 'store', 'bayar', 'jumlah'];
                                const matchCount = financialAnchors.filter(keyword => rawTextLower.includes(keyword)).length;

                                if (matchCount < 2) {
                                    this.triggerReuploadSilent();

                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Security Blocked!',
                                        html: `
                                        <div class="text-left space-y-2 font-sans">
                                            <p class="font-bold text-slate-800 text-sm">Invalid Document Structure Detected:</p>
                                            <p class="text-xs text-slate-500 leading-relaxed">
                                                The uploaded image payload does not match official corporate accounting blueprints. 
                                                Our AI core failed to parse standard fiscal anchors (<span class="font-mono bg-slate-100 px-1 py-0.5 rounded text-rose-600 font-bold">TOTAL, RM, INVOICE</span>).
                                            </p>
                                            <div class="p-2.5 bg-rose-50 border border-rose-100 rounded-xl text-[11px] text-rose-700 font-semibold leading-relaxed">
                                                <i class="fa-solid fa-triangle-exclamation mr-1 text-rose-500"></i>
                                                Action Required: Please scan or upload an official merchant receipt document to proceed.
                                            </div>
                                        </div>
                                    `,
                                        confirmButtonText: 'Acknowledge & Retry',
                                        confirmButtonColor: '#0f172a',
                                        background: '#ffffff',
                                        customClass: {
                                            popup: 'rounded-3xl p-5 border border-slate-100',
                                            title: 'text-lg font-black text-rose-600 font-sans tracking-tight pt-2',
                                            confirmButton: 'w-full py-2.5 text-xs font-bold uppercase tracking-wider rounded-xl cursor-pointer'
                                        }
                                    });
                                    return;
                                }

                                this.merchant = data.merchant_name;
                                this.invoiceNo = data.receipt_invoice_no;
                                this.location = data.location_address;
                                this.date = data.transaction_date;
                                this.amount = data.amount;
                                this.paymentMethod = data.payment_method;
                                if (data.predicted_category) { this.category = data.predicted_category; }

                                document.getElementById('extracted_raw_text').value = data.raw_text;

                                this.$nextTick(() => {
                                    this.checkDuplicateAndPopup();
                                });
                            }
                        })
                        .catch(error => {
                            console.error("OCR Stream Interrupted:", error);
                            this.isExtracting = false;
                        });
                },

                triggerReupload() {
                    this.imagePreview = ''; this.fileName = ''; this.merchant = '';
                    this.invoiceNo = ''; this.location = ''; this.date = '';
                    this.amount = ''; this.category = ''; this.isDuplicate = false;

                    const receiptInput = document.getElementById('receipt');
                    if (receiptInput) { receiptInput.value = ''; }
                    document.getElementById('extracted_raw_text').value = '';

                    this.$nextTick(() => {
                        if (receiptInput) { receiptInput.click(); }
                    });
                },

                triggerReuploadSilent() {
                    this.imagePreview = ''; this.fileName = ''; this.merchant = '';
                    this.invoiceNo = ''; this.location = ''; this.date = '';
                    this.amount = ''; this.category = ''; this.isDuplicate = false;
                    const receiptInput = document.getElementById('receipt');
                    if (receiptInput) { receiptInput.value = ''; }
                    document.getElementById('extracted_raw_text').value = '';
                },

                // FIXED: POST REWRITE WITH DYNAMIC SWAL AUTO-REJECT INTERACTION
                checkDuplicateAndPopup() {
                    if (!this.invoiceNo || !this.amount) {
                        this.isDuplicate = false;
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
                            this.isDuplicate = data.duplicate;

                            // 🛡️ SEKATAN ANTIFRAUD B: AUTO POP-UP & REJECT WIPE IF DUPLICATE IS FOUND
                            if (this.isDuplicate) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Security Blocked!',
                                    html: `
                                    <div class="text-left space-y-2 font-sans">
                                        <p class="font-bold text-slate-800 text-sm">Duplicate Voucher Transacted:</p>
                                        <p class="text-xs text-slate-500 leading-relaxed">
                                            The corporate fraud engine discovered an identical database match for this resource asset. 
                                            A claim voucher with this exact <span class="font-mono bg-slate-100 px-1 py-0.5 rounded text-rose-600 font-bold">Invoice No</span> and <span class="font-mono bg-slate-100 px-1 py-0.5 rounded text-rose-600 font-bold">Amount</span> is already active.
                                        </p>
                                        <div class="p-2.5 bg-rose-50 border border-rose-100 rounded-xl text-[11px] text-rose-700 font-semibold leading-relaxed">
                                            <i class="fa-solid fa-triangle-exclamation mr-1 text-rose-500"></i>
                                            <strong>REJECTED:</strong> This receipt is a duplicate. Form data will be reset. Please click below to re-upload a unique receipt.
                                        </div>
                                    </div>
                                `,
                                    confirmButtonText: 'Acknowledge & Re-upload',
                                    confirmButtonColor: '#0f172a',
                                    background: '#ffffff',
                                    customClass: {
                                        popup: 'rounded-3xl p-5 border border-slate-100',
                                        title: 'text-lg font-black text-rose-600 font-sans tracking-tight pt-2',
                                        confirmButton: 'w-full py-2.5 text-xs font-bold uppercase tracking-wider rounded-xl cursor-pointer'
                                    }
                                }).then(() => {
                                    // Forces instant dynamic wipeout on block dialog dismissal
                                    this.triggerReuploadSilent();
                                });
                            }
                        })
                        .catch(err => {
                            console.error("Duplicate verification failed:", err);
                            this.isDuplicate = false;
                        });
                },

                submitForm(e) {
                    if (this.activeForm === 'Receipt') {
                        if (this.isDuplicate) {
                            e.preventDefault();
                            return false;
                        }
                        if (!this.category || this.category === '') {
                            e.preventDefault();
                            alert('Please select a category!');
                            return false;
                        }
                        if ((this.category === 'Fuel / Automotive' || this.category === 'Fuel') && !this.vehiclePlate) {
                            e.preventDefault();
                            alert('You selected Fuel category, please choose a Vehicle Plate!');
                            return false;
                        }
                    } else if (this.activeForm === 'Mileage') {
                        this.amount = this.allowanceTotal;
                    }
                    return true;
                }
            }
        }
    </script>