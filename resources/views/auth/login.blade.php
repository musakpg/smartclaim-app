<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartClaim - Expense Management System</title>
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
<body class="bg-white text-slate-800 font-sans antialiased min-h-screen flex items-center justify-center p-6" 
      x-data="{ 
          showInactiveModal: {{ session('account_inactive') ? 'true' : 'false' }},
          quickLogin(email, password) {
              const emailInput = document.getElementById('email');
              const passwordInput = document.getElementById('password');
              if (emailInput && passwordInput) {
                  emailInput.value = email;
                  passwordInput.value = password;
                  const form = document.querySelector('form');
                  if (form) form.submit();
              }
          }
      }">

    <div class="w-full max-w-sm mx-auto space-y-8">
        
        <div class="text-center space-y-1">
            <h1 class="font-black text-3xl text-slate-900 tracking-tight">SmartClaim</h1>
            <p class="text-sm text-slate-500">Expense Management System</p>
        </div>

        <hr class="border-slate-200">

        @if($errors->has('loginError') || $errors->has('email'))
            <div class="p-3.5 bg-red-50 border border-red-200 rounded-xl text-red-600 text-xs font-semibold flex items-center gap-2.5">
                <i class="fa-solid fa-circle-exclamation text-red-500 text-sm flex-shrink-0"></i>
                <span>{{ $errors->first('loginError') ?: $errors->first('email') }}</span>
            </div>
        @endif

        @if(session('success') || session('status'))
            <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs font-semibold flex items-center gap-2.5 shadow-sm">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base flex-shrink-0"></i>
                <span>{{ session('success') ?: session('status') }}</span>
            </div>
        @endif

        <form action="/login/process" method="POST" class="space-y-5">
            
            @csrf

            <h2 class="text-center text-sm font-black text-slate-900 tracking-wider uppercase">LOGIN</h2>

            <div class="space-y-1.5">
                <label for="email" class="block text-xs font-bold text-slate-500">Email</label>
                <input type="email" id="email" name="email" required value="{{ old('email') }}"
                       placeholder="Enter your Email"
                       class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-slate-800 placeholder-slate-400 outline-none text-sm font-medium focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
            </div>

            <div class="space-y-1.5">
                <label for="password" class="block text-xs font-bold text-slate-500">Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Enter your Password"
                       class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-slate-800 placeholder-slate-400 outline-none text-sm font-medium focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
            </div>

            <div class="text-left pt-1">
                <a href="/forgot-password" class="text-xs font-bold text-slate-900 hover:underline">Forgot Password?</a>
            </div>

            <div class="pt-2">
                <button type="submit" 
                        class="w-full py-3 bg-[#00e1b1] hover:bg-[#00cda1] text-slate-950 font-black rounded-xl uppercase tracking-wider text-xs transition-all shadow-md shadow-emerald-100 flex items-center justify-center cursor-pointer">
                    LOGIN
                </button>
            </div>
        </form>

        <div class="text-center text-xs text-slate-400 font-medium">
            No Account? <a href="/register" class="text-slate-900 font-bold hover:underline">Register</a>
        </div>

        <!-- Quick Demo Access Component -->
        <div class="pt-5 border-t border-slate-200 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-black uppercase tracking-wider text-slate-600 flex items-center gap-1.5">
                    <i class="fa-solid fa-bolt text-amber-500"></i> Quick Demo Access
                </span>
                <span class="text-[10px] font-semibold text-slate-500 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-full">Read-Only</span>
            </div>
            <p class="text-[11px] text-slate-400">One-click instant login into isolated showcase profiles:</p>
            
            <div class="grid grid-cols-3 gap-2">
                <button type="button" @click="quickLogin('demo-staff@smartclaim.com', 'demo1234')"
                        class="flex flex-col items-center justify-center p-2.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-emerald-50 hover:border-emerald-300 hover:text-emerald-700 transition-all text-center group cursor-pointer shadow-xs">
                    <i class="fa-solid fa-user-pen text-sm text-slate-400 group-hover:text-emerald-600 mb-1"></i>
                    <span class="text-[11px] font-bold text-slate-800 group-hover:text-emerald-800">Staff</span>
                    <span class="text-[9px] text-slate-400 font-medium">Claims</span>
                </button>

                <button type="button" @click="quickLogin('demo-finance@smartclaim.com', 'demo1234')"
                        class="flex flex-col items-center justify-center p-2.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 transition-all text-center group cursor-pointer shadow-xs">
                    <i class="fa-solid fa-file-invoice-dollar text-sm text-slate-400 group-hover:text-blue-600 mb-1"></i>
                    <span class="text-[11px] font-bold text-slate-800 group-hover:text-blue-800">Finance</span>
                    <span class="text-[9px] text-slate-400 font-medium">Audit Desk</span>
                </button>

                <button type="button" @click="quickLogin('demo-manager@smartclaim.com', 'demo1234')"
                        class="flex flex-col items-center justify-center p-2.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-purple-50 hover:border-purple-300 hover:text-purple-700 transition-all text-center group cursor-pointer shadow-xs">
                    <i class="fa-solid fa-shield-halved text-sm text-slate-400 group-hover:text-purple-600 mb-1"></i>
                    <span class="text-[11px] font-bold text-slate-800 group-hover:text-purple-800">Manager</span>
                    <span class="text-[9px] text-slate-400 font-medium">Sign-Off</span>
                </button>
            </div>
        </div>

        <div class="mt-8 pt-4 border-t border-slate-200/80 text-center text-xs text-slate-500 space-y-1">
            <p class="font-medium text-slate-600">SmartClaim v1.0.0-rc · Evaluation Sandbox Environment</p>
            <p>Observing system anomalies or running QA checks? Submit technical logs to 
               <a href="mailto:smartclaim.aeroart@gmail.com" class="text-blue-600 hover:text-blue-700 underline font-semibold">smartclaim.aeroart@gmail.com</a>
            </p>
        </div>

    </div>

    <!-- Inactive Account Modal -->
    <template x-teleport="body">
        <div x-show="showInactiveModal" style="display: none;"
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            
            <div x-show="showInactiveModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"
                 @click="showInactiveModal = false"></div>

            <div x-show="showInactiveModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative bg-white rounded-2xl shadow-xl border border-slate-200 p-6 w-full max-w-md mx-auto text-center z-10">
                 
                 <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-amber-100 mb-4">
                     <i class="fa-solid fa-user-lock text-amber-600 text-xl"></i>
                 </div>
                 
                 <h3 class="text-lg font-black text-slate-900 mb-2">Account Deactivated</h3>
                 <p class="text-sm text-slate-600 mb-6 leading-relaxed">
                     Your SmartClaim portal access is currently inactive. If you believe this is a mistake or require reinstatement, please contact your department manager or email <span class="font-semibold text-slate-800">{{ config('mail.support_address', 'manager@aeroart.com') }}</span>.
                 </p>
                 
                 <button type="button" @click="showInactiveModal = false"
                         class="w-full inline-flex justify-center rounded-xl border border-transparent bg-slate-900 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-slate-800 transition-colors cursor-pointer">
                     Understood / Close
                 </button>
            </div>
        </div>
    </template>

</body>
</html>
