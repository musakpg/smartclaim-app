<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartClaim - Staff Account Registration</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 font-sans antialiased min-h-screen flex items-center justify-center p-4 sm:p-6">

    <div class="w-full max-w-md mx-auto space-y-6">
        
        <div class="text-center space-y-1">
            <h1 class="font-black text-3xl text-slate-900 tracking-tight">SmartClaim</h1>
            <p class="text-sm text-slate-500">Expense Management & Reimbursement Portal</p>
        </div>

        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xl border border-slate-200/80 space-y-5">
            <div class="text-center pb-2 border-b border-slate-100">
                <h2 class="text-base font-black text-slate-900 tracking-wider uppercase">STAFF REGISTRATION</h2>
                <p class="text-xs text-slate-500 mt-1">Register your staff profile and direct banking details for claim disbursements.</p>
            </div>

            @if($errors->any())
                <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-2xl text-rose-700 text-xs font-semibold space-y-1">
                    @foreach($errors->all() as $error)
                        <p class="flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-rose-500"></i> {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @if(session('success'))
                <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <form action="/register" method="POST" class="space-y-4" x-data="{ isSubmitting: false }" @submit="isSubmitting = true">
                @csrf

                <!-- Full Name -->
                <div class="space-y-1.5">
                    <label for="name" class="block text-xs font-bold text-slate-600">Full Name *</label>
                    <input type="text" id="name" name="name" required value="{{ old('name') }}"
                           placeholder="e.g. Muhammad Faiz bin Azman"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 outline-none text-xs font-medium focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
                </div>

                <!-- Email Address -->
                <div class="space-y-1.5">
                    <label for="email" class="block text-xs font-bold text-slate-600">Corporate / Work Email *</label>
                    <input type="email" id="email" name="email" required value="{{ old('email') }}"
                           placeholder="e.g. faiz@aeroart.com"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 outline-none text-xs font-medium focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
                </div>

                <!-- Bank Selection -->
                <div class="space-y-1.5" x-data="{ selectedBank: '{{ old('bank_name', '') }}', customBank: '' }">
                    <label for="bank_name" class="block text-xs font-bold text-slate-600">Reimbursement Bank *</label>
                    <select id="bank_name" name="bank_name" required x-model="selectedBank"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 outline-none text-xs font-medium focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
                        <option value="">-- Select Your Bank --</option>
                        @php
                            $banks = [
                                'Maybank', 'CIMB Bank', 'Public Bank', 'RHB Bank',
                                'Hong Leong Bank', 'AmBank', 'Bank Islam', 'Bank Muamalat',
                                'Affin Bank', 'Alliance Bank', 'Standard Chartered', 'HSBC Bank',
                                'OCBC Bank', 'UOB Malaysia', 'Agrobank', 'Other'
                            ];
                        @endphp
                        @foreach($banks as $b)
                            <option value="{{ $b }}" {{ old('bank_name') === $b ? 'selected' : '' }}>{{ $b }}</option>
                        @endforeach
                    </select>

                    <div x-show="selectedBank === 'Other'" class="pt-2" x-cloak>
                        <input type="text" name="custom_bank_name" placeholder="Specify Bank Name" value="{{ old('custom_bank_name') }}"
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 outline-none text-xs font-medium focus:bg-white focus:border-emerald-500 transition-all">
                    </div>
                </div>

                <!-- Bank Account Number -->
                <div class="space-y-1.5">
                    <label for="bank_account_no" class="block text-xs font-bold text-slate-600">Bank Account Number *</label>
                    <input type="text" id="bank_account_no" name="bank_account_no" required value="{{ old('bank_account_no') }}"
                           placeholder="e.g. 164012345678"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 outline-none text-xs font-medium font-mono focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
                </div>

                <!-- Password Notice -->
                <div class="p-3 bg-blue-50/80 border border-blue-200/60 rounded-2xl flex items-start gap-2.5 text-blue-900 text-xs">
                    <i class="fa-solid fa-shield-halved text-blue-600 mt-0.5 shrink-0"></i>
                    <p class="leading-relaxed">
                        <strong>Password Configuration:</strong> You will receive an email activation link to set up your personal login password once registered.
                    </p>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" :disabled="isSubmitting"
                            class="w-full py-3 bg-[#00e1b1] hover:bg-[#00cda1] text-slate-950 font-black rounded-xl uppercase tracking-wider text-xs transition-all shadow-md shadow-emerald-100 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60">
                        <template x-if="isSubmitting"><i class="fa-solid fa-spinner fa-spin"></i></template>
                        <span x-text="isSubmitting ? 'Registering...' : 'Register Account'"></span>
                    </button>
                </div>
            </form>

            <div class="text-center pt-2 border-t border-slate-100">
                <p class="text-xs text-slate-500">
                    Already registered? 
                    <a href="/" class="text-slate-900 font-bold hover:underline">Log In</a>
                </p>
            </div>
        </div>

    </div>

</body>
</html>
