<!DOCTYPE html>
<html lang="en"
    x-data="{ isMobileSidebarOpen: false, isAuditingOpen: false, isAdminOpen: true, activeSubTab: 'mileage_rates' }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Mileage Rates Administration</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" :class="isMobileSidebarOpen ? 'overflow-hidden' : ''">

    <div class="flex min-h-screen">

        @include('layouts.partials.manager-sidebar')
        <main class="flex-1 p-4 md:p-8 max-w-4xl mx-auto w-full overflow-hidden">
            <div class="space-y-6">
                <div class="border-b border-slate-200 pb-5">
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Mileage Allowance Rates
                    </h1>
                    <p class="text-xs md:text-sm text-slate-500">Corporate distance financial multi-tier multiplier
                        matrices for Aero Art Sdn Bhd.</p>
                </div>

                @if(session('success'))
                    <div
                        class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-semibold flex items-center gap-2.5 shadow-3xs">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6">

                    <div class="bg-white p-4 md:p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-4">
                        <div
                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-50 pb-2">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-400"><i
                                    class="fa-solid fa-car text-blue-500 mr-1"></i> Car Rate Configuration</span>
                            <span
                                class="px-2.5 py-1 bg-blue-50 text-blue-700 font-mono font-black text-xs rounded-xl self-start sm:self-auto">RM
                                0.60 / KM</span>
                        </div>
                        <form action="#" method="POST" class="space-y-3">
                            @csrf
                            <div class="space-y-1">
                                <label class="block text-[10px] md:text-[11px] font-bold text-slate-600 uppercase">Set
                                    New Car Multiplier Rate</label>
                                <div class="relative text-xs">
                                    <span
                                        class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">RM</span>
                                    <input type="number" step="0.01" name="car_rate" value="0.60" required
                                        class="w-full pl-9 pr-16 py-2.5 bg-slate-50 border border-slate-100 rounded-xl outline-none font-bold font-mono text-slate-800">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">/
                                        KM</span>
                                </div>
                            </div>
                            <button type="submit" disabled
                                class="w-full py-2.5 bg-slate-200 text-slate-400 rounded-xl text-xs font-bold transition-all cursor-not-allowed uppercase tracking-wider border border-slate-300/40">
                                <i class="fa-solid fa-floppy-disk mr-1"></i> Save Changes (Active)
                            </button>
                        </form>
                    </div>

                    <div class="bg-white p-4 md:p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-4">
                        <div
                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-50 pb-2">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-400"><i
                                    class="fa-solid fa-motorcycle text-orange-500 mr-1"></i> Motorcycle
                                Configuration</span>
                            <span
                                class="px-2.5 py-1 bg-orange-50 text-orange-700 font-mono font-black text-xs rounded-xl self-start sm:self-auto">RM
                                0.30 / KM</span>
                        </div>
                        <form action="#" method="POST" class="space-y-3">
                            @csrf
                            <div class="space-y-1">
                                <label class="block text-[10px] md:text-[11px] font-bold text-slate-600 uppercase">Set
                                    New Motorcycle Multiplier Rate</label>
                                <div class="relative text-xs">
                                    <span
                                        class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">RM</span>
                                    <input type="number" step="0.01" name="motor_rate" value="0.30" required
                                        class="w-full pl-9 pr-16 py-2.5 bg-slate-50 border border-slate-100 rounded-xl outline-none font-bold font-mono text-slate-800">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">/
                                        KM</span>
                                </div>
                            </div>
                            <button type="submit" disabled
                                class="w-full py-2.5 bg-slate-200 text-slate-400 rounded-xl text-xs font-bold transition-all cursor-not-allowed uppercase tracking-wider border border-slate-300/40">
                                <i class="fa-solid fa-floppy-disk mr-1"></i> Save Changes (Active)
                            </button>
                        </form>
                    </div>
                </div>

                <div
                    class="bg-slate-900/5 p-4 rounded-2xl border border-slate-100 text-xs text-slate-500 space-y-1.5 leading-relaxed">
                    <p class="font-bold text-slate-800 uppercase text-[10px] tracking-wider flex items-center gap-1">
                        <i class="fa-solid fa-circle-info text-blue-500"></i> Audit Compliance Notice
                    </p>
                    <p>Global multipliers configured here instantly manipulate subsequent logistics form entries.
                        Historical records logged in <strong>Approved</strong> matrices remain untouched to guarantee
                        ledger security integrity.</p>
                </div>
            </div>
        </main>
    </div>
</body>

</html>