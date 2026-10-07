<!-- Comment: Staff Account Profile & Banking Configuration Workspace -->
<!DOCTYPE html>
<html lang="en" x-data="{ isMobileSidebarOpen: false, activeTab: 'banking' }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - My Profile & Banking Settings</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased"
    :class="isMobileSidebarOpen ? 'overflow-hidden lg:overflow-auto' : ''">

    <div class="min-h-screen flex flex-col lg:flex-row">

        <!-- Staff Sidebar Partial -->
        @include('layouts.partials.staff-sidebar')

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">


            <main class="flex-1 w-full px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-6 overflow-y-auto pb-28 md:pb-8">

                <!-- Header Section -->
                <div class="border-b border-slate-200 pb-5">
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">My Profile & Account
                        Settings</h1>
                    <p class="text-xs md:text-sm text-slate-500">Manage personal identification, reimbursement banking
                        credentials, and password security.</p>
                </div>

                <!-- Flash Status Alerts -->
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
                            <p class="flex items-center gap-1.5">
                                <i class="fa-solid fa-circle-exclamation text-rose-500"></i>
                                <span>{{ $error }}</span>
                            </p>
                        @endforeach
                    </div>
                @endif

                <!-- Profile Banner Card -->
                <div class="bg-white rounded-3xl border border-slate-200/60 shadow-xs overflow-hidden">
                    <div class="h-24 bg-[#0d1527] relative px-6 flex items-end">
                        <div
                            class="w-16 h-16 bg-white rounded-2xl p-1 shadow-md translate-y-8 flex items-center justify-center border border-slate-100">
                            <div
                                class="w-full h-full bg-slate-900 text-emerald-400 rounded-xl flex items-center justify-center font-black text-xl font-mono">
                                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                            </div>
                        </div>
                    </div>
                    <div class="pt-10 p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-black text-slate-900">{{ Auth::user()->name }}</h2>
                            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">
                                {{ Auth::user()->role ?? 'Staff Employee' }} • {{ Auth::user()->matrix_id ?? 'STAFF' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span
                                class="px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[11px] font-bold">
                                <i class="fa-solid fa-circle-check text-emerald-500 mr-1"></i> Active Status
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 2-Column Split Workspace -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Left Column: Read-Only System Identity -->
                    <div class="space-y-4">
                        <div
                            class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-4 text-xs font-semibold">
                            <h3
                                class="font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5 border-b border-slate-100 pb-3">
                                <i class="fa-solid fa-id-badge text-slate-400"></i> Staff Identity
                            </h3>

                            <div class="space-y-1">
                                <span class="text-[10px] text-slate-400 font-bold uppercase">Staff Matrix ID</span>
                                <div
                                    class="p-2.5 bg-slate-50 border border-slate-100 rounded-xl font-mono text-slate-800">
                                    {{ Auth::user()->matrix_id ?? 'STAFF-ID' }}
                                </div>
                            </div>

                            <div class="space-y-1">
                                <span class="text-[10px] text-slate-400 font-bold uppercase">Corporate Email</span>
                                <div
                                    class="p-2.5 bg-slate-50 border border-slate-100 rounded-xl text-slate-800 truncate">
                                    {{ Auth::user()->email }}
                                </div>
                            </div>

                            <div class="space-y-1">
                                <span class="text-[10px] text-slate-400 font-bold uppercase">Client Organization</span>
                                <div
                                    class="p-2.5 bg-slate-50 border border-slate-100 rounded-xl text-slate-800 font-bold">
                                    Aero Art Sdn Bhd
                                </div>
                            </div>
                        </div>

                        <!-- Transport Quick Access -->
                        <div
                            class="bg-gradient-to-br from-slate-900 to-slate-800 p-5 rounded-3xl text-white shadow-xs space-y-3">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-car-side text-emerald-400 text-base"></i>
                                <h4 class="font-bold text-xs uppercase tracking-wider">Registered Vehicles</h4>
                            </div>
                            <p class="text-[11px] text-slate-300 leading-relaxed">
                                Ensure your personal vehicle license plates are registered for Mileage Allowance claims.
                            </p>
                            <a href="{{ route('vehicles.index') }}"
                                class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition cursor-pointer">
                                Manage Vehicles <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Right Column: Editable Forms (Banking & Security) -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- 1. BANKING & REIMBURSEMENT FORM -->
                        <div class="bg-white p-6 rounded-3xl border border-slate-200/60 shadow-xs space-y-4">
                            <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                        <i class="fa-solid fa-building-columns text-emerald-600"></i> Direct
                                        Reimbursement Banking Details
                                    </h3>
                                    <p class="text-[11px] text-slate-400">Used by the Finance Department to execute
                                        electronic fund transfers directly to your bank account.</p>
                                </div>
                            </div>

                            <form action="{{ route('profile.update') }}" method="POST"
                                class="space-y-4 text-xs font-semibold">
                                @csrf
                                @method('PUT')

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <!-- Bank Selection -->
                                    <div class="space-y-1.5">
                                        <label class="block text-slate-700">Bank Name *</label>
                                        <select name="bank_name" required
                                            class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl outline-none font-bold text-slate-800 text-xs cursor-pointer">
                                            <option value="">Select Your Bank</option>
                                            @php
                                                $banks = ['Maybank', 'CIMB Bank', 'Public Bank', 'RHB Bank', 'Hong Leong Bank', 'AmBank', 'Bank Islam', 'Bank Muamalat', 'Affin Bank', 'Alliance Bank', 'Standard Chartered', 'HSBC Bank'];
                                                $currentBank = Auth::user()->bank_name;
                                            @endphp
                                            @foreach($banks as $b)
                                                <option value="{{ $b }}" {{ $currentBank === $b ? 'selected' : '' }}>{{ $b }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Account Number -->
                                    <div class="space-y-1.5">
                                        <label class="block text-slate-700">Bank Account Number *</label>
                                        <input type="text" name="bank_account_no"
                                            value="{{ old('bank_account_no', Auth::user()->bank_account_no) }}" required
                                            placeholder="e.g. 164012345678"
                                            class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl outline-none font-mono font-bold text-slate-800 text-xs">
                                    </div>

                                    <!-- Account Holder Name -->
                                    <div class="space-y-1.5 sm:col-span-2">
                                        <label class="block text-slate-700">Account Holder Full Name (Per NRIC /
                                            Passport) *</label>
                                        <input type="text" name="bank_account_holder"
                                            value="{{ old('bank_account_holder', Auth::user()->bank_account_holder ?? Auth::user()->name) }}"
                                            required placeholder="e.g. Full legal name matching bank account"
                                            class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl outline-none font-bold text-slate-800 text-xs">
                                    </div>
                                </div>

                                <div
                                    class="p-3 bg-blue-50/60 rounded-2xl border border-blue-100 flex items-start gap-2 text-[11px] text-blue-800 font-normal leading-relaxed">
                                    <i class="fa-solid fa-circle-info text-blue-500 mt-0.5 shrink-0"></i>
                                    <span>Ensure the account number and holder name strictly match your bank
                                        passbook/statement. The Finance Department cannot disburse funds to mismatched
                                        third-party accounts.</span>
                                </div>

                                <div class="flex justify-end pt-2">
                                    <button type="submit"
                                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-xs transition cursor-pointer flex items-center gap-2">
                                        <i class="fa-solid fa-floppy-disk"></i> Save Banking Info
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- 2. CHANGE PASSWORD FORM -->
                        <div class="bg-white p-6 rounded-3xl border border-slate-200/60 shadow-xs space-y-4">
                            <div class="border-b border-slate-100 pb-3">
                                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <i class="fa-solid fa-lock text-slate-600"></i> Account Authentication & Security
                                </h3>
                                <p class="text-[11px] text-slate-400">Update your password regularly to maintain account
                                    security.</p>
                            </div>

                            <form action="{{ route('profile.password') }}" method="POST"
                                class="space-y-4 text-xs font-semibold">
                                @csrf
                                @method('PUT')

                                <div class="space-y-1.5">
                                    <label class="block text-slate-700">Current Password *</label>
                                    <input type="password" name="current_password" required
                                        class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl outline-none text-slate-800 text-xs">
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="block text-slate-700">New Password *</label>
                                        <input type="password" name="password" required
                                            class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl outline-none text-slate-800 text-xs">
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="block text-slate-700">Confirm New Password *</label>
                                        <input type="password" name="password_confirmation" required
                                            class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl outline-none text-slate-800 text-xs">
                                    </div>
                                </div>

                                <div class="flex justify-end pt-2">
                                    <button type="submit"
                                        class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl transition cursor-pointer">
                                        Update Password
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>

            </main>
        </div>
    </div>
    @include('layouts.partials.bottom-nav')
</body>

</html>
