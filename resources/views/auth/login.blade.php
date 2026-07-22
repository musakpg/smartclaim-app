<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Expense Management System</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-white text-slate-800 font-sans antialiased min-h-screen flex items-center justify-center p-6">

    <div class="w-full max-w-sm mx-auto space-y-8">
        
        <div class="text-center space-y-1">
            <h1 class="font-black text-3xl text-slate-900 tracking-tight">SmartClaim</h1>
            <p class="text-sm text-slate-500">Expense Management System</p>
        </div>

        <hr class="border-slate-200">

        @if($errors->has('loginError') || $errors->has('email'))
            <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-xs font-semibold">
                {{ $errors->first('loginError') ?: $errors->first('email') }}
            </div>
        @endif

        @if(session('success'))
            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-xs font-semibold">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('login.process') }}" method="POST" class="space-y-5">
            
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
                <a href="#" class="text-xs font-bold text-slate-900 hover:underline">Forgot Password?</a>
            </div>

            <div class="pt-2">
                <button type="submit" 
                        class="w-full py-3 bg-[#00e1b1] hover:bg-[#00cda1] text-slate-950 font-black rounded-xl uppercase tracking-wider text-xs transition-all shadow-md shadow-emerald-100 flex items-center justify-center cursor-pointer">
                    LOGIN
                </button>
            </div>
        </form>

        <div class="text-center text-xs text-slate-400 font-medium">
            No Account? <a href="#" class="text-slate-900 font-bold hover:underline">Register</a>
        </div>

    </div>

</body>
</html>