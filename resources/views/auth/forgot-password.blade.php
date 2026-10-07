<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartClaim - Forgot Password</title>
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
<body class="bg-[#f8fafc] text-slate-800 font-sans antialiased min-h-screen flex items-center justify-center p-4 sm:p-6">

    <div class="w-full max-w-sm mx-auto space-y-6">
        
        <div class="text-center space-y-1">
            <h1 class="font-black text-3xl text-slate-900 tracking-tight">SmartClaim</h1>
            <p class="text-sm text-slate-500">Expense Management & Reimbursement Portal</p>
        </div>

        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xl border border-slate-200/80 space-y-5">
            <div class="text-center pb-2 border-b border-slate-100">
                <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center mx-auto mb-2 text-lg">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <h2 class="text-base font-black text-slate-900 tracking-wider uppercase">FORGOT PASSWORD</h2>
                <p class="text-xs text-slate-500 mt-1">Enter your registered email and we'll send you a password reset link.</p>
            </div>

            @if($errors->any())
                <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-2xl text-rose-700 text-xs font-semibold space-y-1">
                    @foreach($errors->all() as $error)
                        <p class="flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-rose-500"></i> {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @if(session('status') || session('success'))
                <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i>
                    <span>{{ session('status') ?: session('success') }}</span>
                </div>
            @endif

            <form action="/forgot-password" method="POST" class="space-y-4" x-data="{ isSubmitting: false }" @submit="isSubmitting = true">
                @csrf

                <div class="space-y-1.5">
                    <label for="email" class="block text-xs font-bold text-slate-600">Registered Work Email *</label>
                    <input type="email" id="email" name="email" required value="{{ old('email') }}"
                           placeholder="Enter your Email"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 outline-none text-xs font-medium focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
                </div>

                <div class="pt-2">
                    <button type="submit" :disabled="isSubmitting"
                            class="w-full py-3 bg-[#00e1b1] hover:bg-[#00cda1] text-slate-950 font-black rounded-xl uppercase tracking-wider text-xs transition-all shadow-md shadow-emerald-100 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60">
                        <template x-if="isSubmitting"><i class="fa-solid fa-spinner fa-spin"></i></template>
                        <span x-text="isSubmitting ? 'Sending Link...' : 'Send Reset Link'"></span>
                    </button>
                </div>
            </form>

            <div class="text-center pt-2 border-t border-slate-100">
                <a href="/" class="text-xs text-slate-600 font-bold hover:text-slate-900 inline-flex items-center gap-1.5 transition">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i> Back to Log In
                </a>
            </div>
        </div>

    </div>

</body>
</html>
