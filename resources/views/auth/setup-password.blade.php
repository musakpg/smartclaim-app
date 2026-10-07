<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartClaim - Set Up Account Password</title>
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

    <div class="w-full max-w-md mx-auto space-y-6">
        
        <div class="text-center space-y-1">
            <h1 class="font-black text-3xl text-slate-900 tracking-tight">SmartClaim</h1>
            <p class="text-sm text-slate-500">Expense Management & Reimbursement Portal</p>
        </div>

        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xl border border-slate-200/80 space-y-5">
            <div class="text-center pb-2 border-b border-slate-100">
                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-2 text-lg">
                    <i class="fa-solid fa-key"></i>
                </div>
                <h2 class="text-base font-black text-slate-900 tracking-wider uppercase">ACTIVATE YOUR ACCOUNT</h2>
                <p class="text-xs text-slate-500 mt-1">Hello <strong class="text-slate-800">{{ $user->name }}</strong>, please create a secure password to complete activation.</p>
            </div>

            @if($errors->any())
                <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-2xl text-rose-700 text-xs font-semibold space-y-1">
                    @foreach($errors->all() as $error)
                        <p class="flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-rose-500"></i> {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="/setup-password/{{ $token }}" method="POST" class="space-y-4" x-data="{ isSubmitting: false, showPass: false }" @submit="isSubmitting = true">
                @csrf

                <!-- Email (Read Only Badge) -->
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-500">Registered Email</label>
                    <div class="px-4 py-2.5 bg-slate-100 rounded-xl text-xs font-semibold text-slate-700 font-mono flex items-center justify-between border border-slate-200">
                        <span>{{ $user->email }}</span>
                        <i class="fa-solid fa-circle-check text-emerald-500"></i>
                    </div>
                </div>

                <!-- New Password -->
                <div class="space-y-1.5">
                    <label for="password" class="block text-xs font-bold text-slate-600">New Password *</label>
                    <div class="relative">
                        <input :type="showPass ? 'text' : 'password'" id="password" name="password" required minlength="8"
                               placeholder="Minimum 8 characters"
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 outline-none text-xs font-medium focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all pr-10">
                        <button type="button" @click="showPass = !showPass" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs cursor-pointer">
                            <i class="fa-solid" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="space-y-1.5">
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-600">Confirm Password *</label>
                    <input :type="showPass ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required minlength="8"
                           placeholder="Re-enter your password"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 placeholder-slate-400 outline-none text-xs font-medium focus:bg-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" :disabled="isSubmitting"
                            class="w-full py-3 bg-[#00e1b1] hover:bg-[#00cda1] text-slate-950 font-black rounded-xl uppercase tracking-wider text-xs transition-all shadow-md shadow-emerald-100 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60">
                        <template x-if="isSubmitting"><i class="fa-solid fa-spinner fa-spin"></i></template>
                        <span x-text="isSubmitting ? 'Saving Password...' : 'Save Password & Activate'"></span>
                    </button>
                </div>
            </form>

            <div class="text-center pt-2 border-t border-slate-100">
                <p class="text-xs text-slate-500">
                    Need help? Contact support or <a href="/" class="text-slate-900 font-bold hover:underline">Return to Login</a>
                </p>
            </div>
        </div>

    </div>

</body>
</html>
