<!DOCTYPE html>
<html lang="en" x-data="{ isMobileSidebarOpen: false }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Auditor Profile</title>
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

        <!-- Finance Sidebar Partial -->
        @include('layouts.partials.finance-sidebar')

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Main Page Content Area -->
            <main class="flex-1 w-full px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-6 overflow-y-auto pb-24 md:pb-8">
                <div class="space-y-6">

                    @if(session('success'))
                        <div class="p-4 bg-green-50 text-green-700 border border-green-200 rounded-xl text-sm font-semibold">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="p-4 bg-red-50 text-red-700 border border-red-200 rounded-xl text-sm font-semibold">
                            <ul class="list-disc pl-5">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Title & Header -->
                    <div class="border-b border-slate-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                            <div>
                        <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Finance Auditor Dashboard & Profile</h1>
                        <p class="text-xs md:text-sm text-slate-500">Manage account authentication credentials and device security information for financial administrators.</p>
                    </div>
                    <div class="hidden lg:flex items-center gap-3">
                        <x-system-clock />
                    </div>
                </div>

                    <!-- Finance Activity Metrics -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-center items-center text-center">
                            <div class="w-12 h-12 bg-[#00d1b2]/10 text-[#00d1b2] rounded-full flex items-center justify-center mb-3">
                                <i class="fa-solid fa-file-invoice-dollar text-xl"></i>
                            </div>
                            <h3 class="text-3xl font-black text-slate-800">{{ $totalBatchesSettled ?? 0 }}</h3>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mt-1">Total Claims Settled</p>
                        </div>
                        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-center items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-3">
                                <i class="fa-solid fa-coins text-xl"></i>
                            </div>
                            <h3 class="text-3xl font-black text-slate-800">RM {{ number_format($recentVolume ?? 0, 2) }}</h3>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mt-1">Recent Reimbursement Volume</p>
                        </div>
                    </div>

                    <!-- Officer Details Profile Card -->
                    <div class="bg-white p-5 md:p-7 rounded-3xl border border-slate-200/60 shadow-2xs space-y-6">
                        <div class="flex items-center gap-4 border-b border-slate-100 pb-5">
                            <div class="w-12 h-12 md:w-14 md:h-14 bg-slate-900 text-emerald-400 rounded-2xl flex items-center justify-center text-lg md:text-xl font-black border border-slate-800 shadow-inner shrink-0 font-mono">
                                {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 2)) : 'FA' }}
                            </div>
                            <div class="truncate">
                                <h3 class="text-sm md:text-base font-black text-slate-900 leading-tight truncate">
                                    {{ Auth::check() ? Auth::user()->name : 'Finance Auditor Node' }}
                                </h3>
                                <p class="text-[10px] md:text-xs font-mono text-slate-400 truncate mt-0.5">Authority: Level 2 Admin (Aero Art Accounts)</p>
                            </div>
                        </div>

                        <form action="{{ route('profile.update') }}" method="POST" class="space-y-6">
                            @csrf
                            @method('PUT')
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-semibold">
                                <div class="space-y-1.5">
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide">Account Secure Identity (Name)</span>
                                    <input type="text" name="name" value="{{ old('name', Auth::user()->name ?? 'Finance Officer') }}" required
                                        class="w-full p-3 bg-slate-50 rounded-xl font-bold text-slate-800 border border-slate-200 focus:ring-2 focus:ring-[#00d1b2] outline-none text-xs md:text-sm truncate">
                                </div>
                                <div class="space-y-1.5">
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide">System Registered Email</span>
                                    <input type="email" name="email" value="{{ old('email', Auth::user()->email ?? 'finance@aeroart.com') }}" required
                                        class="w-full p-3 bg-slate-50 rounded-xl font-mono text-slate-500 border border-slate-200 focus:ring-2 focus:ring-[#00d1b2] outline-none text-xs md:text-sm truncate">
                                </div>
                                <div class="space-y-1.5">
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide">Role</span>
                                    <input type="text" value="{{ Auth::user()->role ?? 'Finance' }}" disabled
                                        class="w-full p-3 bg-slate-100 rounded-xl font-bold text-slate-500 border border-slate-200 outline-none text-xs md:text-sm truncate cursor-not-allowed">
                                </div>
                            </div>
                            <button type="submit"
                                class="w-full sm:w-auto px-6 py-3 bg-[#0f172a] hover:bg-slate-800 text-white font-bold rounded-xl transition-all text-xs uppercase tracking-wider cursor-pointer">
                                Save Details
                            </button>
                        </form>
                    </div>

                    <!-- Security & Encryption -->
                    <div class="bg-white p-5 md:p-7 rounded-3xl border border-slate-200/60 shadow-2xs">
                        <h2 class="text-base md:text-lg font-bold text-slate-900 mb-4 md:mb-6">Security & Password Update</h2>
                        <form action="{{ route('profile.password') }}" method="POST" class="space-y-6">
                            @csrf
                            @method('PUT')
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 text-xs font-semibold">
                                <div class="space-y-2 md:col-span-2">
                                    <label class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wide">Current Password</label>
                                    <input type="password" name="current_password" required placeholder="••••••••"
                                        class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-[#00d1b2] outline-none text-xs md:text-sm font-mono">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wide">New Password</label>
                                    <input type="password" name="password" required placeholder="••••••••"
                                        class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-[#00d1b2] outline-none text-xs md:text-sm font-mono">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wide">Confirm New Password</label>
                                    <input type="password" name="password_confirmation" required placeholder="••••••••"
                                        class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-[#00d1b2] outline-none text-xs md:text-sm font-mono">
                                </div>
                            </div>
                            <button type="submit"
                                class="w-full sm:w-auto px-6 py-3 bg-[#00d1b2] hover:bg-[#00c0a3] text-white font-bold rounded-xl transition-all text-xs uppercase tracking-wider cursor-pointer shadow-md shadow-[#00d1b2]/30">
                                Update Password
                            </button>
                        </form>
                    </div>

                    <!-- Corporate Security Policy Notice -->
                    <div class="bg-amber-50/60 p-4 sm:p-5 rounded-2xl border border-amber-100 text-[11px] md:text-xs text-amber-900 space-y-1.5 leading-relaxed shadow-3xs">
                        <p class="font-bold uppercase text-[10px] tracking-wider flex items-center gap-1.5 text-amber-800">
                            <i class="fa-solid fa-triangle-exclamation text-amber-600"></i> Corporate Access Protection Policy
                        </p>
                        <p class="text-amber-800/90">
                            This account holds executive approval authority for Aero Art Sdn Bhd corporate compensation funds. Any password changes or portal identity recoveries must be channeled directly through the network systems director to maintain external audit compliance.
                        </p>
                    </div>

                </div>
            </main>
        </div>
    </div>
    @include('layouts.partials.bottom-nav')
</body>

</html>
