<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim | Manager Portal</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-[#f8fafc] text-[#1e293b]">

<div class="flex min-h-screen">
    <aside class="w-64 bg-[#0b1727] text-white shrink-0">
        <div class="p-6">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-crown text-amber-400 text-2xl"></i>
                <div>
                    <h1 class="font-bold text-lg">SmartClaim</h1>
                    <p class="text-[10px] text-emerald-400 font-bold uppercase">Manager Portal</p>
                </div>
            </div>
        </div>

        <nav class="px-4 py-4 space-y-2">
            <a href="{{ route('manager.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white">
                <i class="fa-solid fa-chart-pie"></i> Dashboard
            </a>
            <a href="{{ route('manager.verification') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white">
                <i class="fa-solid fa-file-check"></i> Claims Verification
            </a>
            <a href="{{ route('manager.reports') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold bg-slate-800 text-white">
                <i class="fa-solid fa-chart-line"></i> Reports & BI Analytics
            </a>
        </nav>
    </aside>

    <main class="flex-1 p-8">
        @yield('content')
    </main>
</div>
</body>
</html>