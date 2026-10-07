<!DOCTYPE html>
<html lang="en" x-data="{ isMobileSidebarOpen: false, isAuditingOpen: false, isAdminOpen: false }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Manager Portal</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" :class="isMobileSidebarOpen ? 'overflow-hidden' : ''">

    <div class="flex flex-col lg:flex-row min-h-screen">

        @include('layouts.partials.manager-sidebar')

        <main class="flex-1 p-4 md:p-8 max-w-4xl mx-auto w-full overflow-hidden">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-50 text-green-700 border border-green-200 rounded-xl text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-4 p-4 bg-red-50 text-red-700 border border-red-200 rounded-xl text-sm">
                    <ul class="list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="space-y-6">
                <div class="border-b border-slate-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div>
                        <h1 class="text-xl md:text-2xl font-black text-slate-900">Manager Dashboard & Profile</h1>
                    <p class="text-xs md:text-sm text-slate-500">Manage your executive profile and monitor key operational metrics.</p>
                    </div>
                    <div class="hidden lg:flex items-center gap-3">
                        <x-system-clock />
                    </div>
                </div>

                <!-- KPI Stats Section -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-center items-center text-center">
                        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-3">
                            <i class="fa-solid fa-list-check text-xl"></i>
                        </div>
                        <h3 class="text-3xl font-black text-slate-800">{{ $totalReviewCount }}</h3>
                        <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mt-1">Total Claims Reviewed</p>
                    </div>
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-center items-center text-center">
                        <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center mb-3">
                            <i class="fa-solid fa-check-double text-xl"></i>
                        </div>
                        <h3 class="text-3xl font-black text-slate-800">{{ $approvalsThisMonth }}</h3>
                        <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mt-1">Approvals This Month</p>
                    </div>
                    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col justify-center items-center text-center">
                        <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mb-3">
                            <i class="fa-solid fa-scale-balanced text-xl"></i>
                        </div>
                        <h3 class="text-3xl font-black text-slate-800">{{ $policiesConfigured }}</h3>
                        <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mt-1">Policies Configured</p>
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="bg-white p-6 md:p-8 rounded-3xl border border-slate-200/60 shadow-2xs">
                    <h2 class="text-base md:text-lg font-bold text-slate-900 mb-4 md:mb-6">Personal Information</h2>
                    <form action="{{ route('profile.update') }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 text-xs font-semibold">
                            <div class="space-y-2">
                                <label class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wide">Full Name</label>
                                <input type="text" name="name" value="{{ old('name', Auth::user()->name) }}" required
                                    class="w-full p-3 rounded-xl border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-blue-500 outline-none text-slate-800 font-bold text-xs md:text-sm">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wide">Email Address</label>
                                <input type="email" name="email" value="{{ old('email', Auth::user()->email) }}" required
                                    class="w-full p-3 rounded-xl border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-blue-500 outline-none font-mono text-slate-600 text-xs md:text-sm">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wide">Department</label>
                                <input type="text" value="Aero Art Operations" disabled
                                    class="w-full p-3 rounded-xl border border-slate-200 bg-slate-100 text-slate-500 outline-none text-xs md:text-sm cursor-not-allowed">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wide">Role</label>
                                <input type="text" value="{{ Auth::user()->role }}" disabled
                                    class="w-full p-3 rounded-xl border border-slate-200 bg-slate-100 text-slate-500 outline-none text-xs md:text-sm cursor-not-allowed">
                            </div>
                        </div>
                        <button type="submit"
                            class="w-full sm:w-auto px-6 py-3 bg-[#0f172a] hover:bg-slate-800 text-white font-bold rounded-xl transition-all text-xs uppercase tracking-wider cursor-pointer">
                            Save Changes
                        </button>
                    </form>
                </div>

                <!-- Security & Encryption -->
                <div class="bg-white p-6 md:p-8 rounded-3xl border border-slate-200/60 shadow-2xs">
                    <h2 class="text-base md:text-lg font-bold text-slate-900 mb-4 md:mb-6">Security & Password Update</h2>
                    <form action="{{ route('profile.password') }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 text-xs font-semibold">
                            <div class="space-y-2 md:col-span-2">
                                <label class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wide">Current Password</label>
                                <input type="password" name="current_password" required placeholder="••••••••"
                                    class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-xs md:text-sm font-mono">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wide">New Password</label>
                                <input type="password" name="password" required placeholder="••••••••"
                                    class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-xs md:text-sm font-mono">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wide">Confirm New Password</label>
                                <input type="password" name="password_confirmation" required placeholder="••••••••"
                                    class="w-full p-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-xs md:text-sm font-mono">
                            </div>
                        </div>
                        <button type="submit"
                            class="w-full sm:w-auto px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-all text-xs uppercase tracking-wider cursor-pointer">
                            Update Password
                        </button>
                    </form>
                </div>

            </div>
        </main>
    </div>
</body>

</html>
