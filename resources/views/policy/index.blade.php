<!DOCTYPE html>
<html lang="en" x-data="{ isMobileSidebarOpen: false, isClaimsOpen: false }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Company Policy</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased">

    <div class="flex min-h-screen flex-col lg:flex-row">
        
        <header class="lg:hidden bg-white border-b border-[#e2e8f0] px-4 py-4 flex items-center justify-between sticky top-0 z-40 shadow-2xs">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-wallet text-slate-800 text-xl"></i>
                <span class="font-bold text-lg tracking-tight text-slate-900">SmartClaim</span>
            </div>
            <button type="button" @click="isMobileSidebarOpen = true" class="w-9 h-9 flex items-center justify-center bg-slate-100 rounded-xl text-slate-700 cursor-pointer">
                <i class="fa-solid fa-bars text-base"></i>
            </button>
        </header>

        <div x-show="isMobileSidebarOpen" x-cloak class="lg:hidden fixed inset-0 z-50 flex" role="dialog" aria-modal="true">
            <div x-show="isMobileSidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="isMobileSidebarOpen = false"></div>

            <div x-show="isMobileSidebarOpen" x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="relative flex w-full max-w-xs flex-1 flex-col bg-white pt-5 pb-4 border-r border-[#e2e8f0]">
                <div class="absolute top-4 right-4">
                    <button type="button" @click="isMobileSidebarOpen = false" class="w-8 h-8 flex items-center justify-center bg-slate-100 rounded-lg text-slate-500 cursor-pointer">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="px-6 pb-4 border-b border-[#f1f5f9] flex items-center gap-2">
                    <i class="fa-solid fa-wallet text-slate-800 text-xl"></i>
                    <span class="font-bold text-lg tracking-tight text-slate-900">SmartClaim</span>
                </div>
                <nav class="mt-4 flex-1 px-4 space-y-1 overflow-y-auto" x-data="{ isClaimsOpenMobile: false }">
                    <a href="{{ route('dashboard') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50">
                        <i class="fa-solid fa-house"></i> Dashboard
                    </a>
                    <div>
                        <button type="button" @click.prevent="isClaimsOpenMobile = !isClaimsOpenMobile" class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50 hover:text-slate-900 cursor-pointer">
                            <span class="flex items-center gap-3"><i class="fa-solid fa-file-pen"></i> Claims</span>
                            <i class="fa-solid text-[10px]" :class="isClaimsOpenMobile ? 'fa-chevron-down rotate-180' : 'fa-chevron-right'"></i>
                        </button>
                        <div x-show="isClaimsOpenMobile" class="pl-6 mt-1 space-y-1 py-1 bg-slate-50 rounded-xl border border-slate-100">
                            <a href="{{ route('claims.create') }}?type=Receipt" class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-slate-500 flex items-center gap-2"><i class="fa-solid fa-file-invoice text-[11px]"></i> Based on Receipt (OCR)</a>
                            <a href="{{ route('claims.create') }}?type=Mileage" class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-slate-500 flex items-center gap-2"><i class="fa-solid fa-motorcycle text-[11px]"></i> Mileage Allowance</a>
                            <a href="{{ route('claims.history') }}" class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-slate-500 flex items-center gap-2"><i class="fa-solid fa-clipboard-list text-[11px]"></i> My Claims</a>
                        </div>
                    </div>
                    <a href="{{ route('reimbursement.index') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50">
                        <i class="fa-solid fa-hand-holding-dollar"></i> Reimbursement Status
                    </a>
                    <a href="{{ route('profile.index') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50">
                        <i class="fa-solid fa-user"></i> My Profile
                    </a>
                    <a href="{{ route('policy.index') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold bg-[#f1f5f9] text-blue-600">
                        <i class="fa-solid fa-file-shield"></i> Company Policy
                    </a>
                    <a href="{{ route('logout') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-rose-50 hover:text-rose-600">
                        <i class="fa-solid fa-door-open"></i> Sign Out
                    </a>
                </nav>
            </div>
        </div>

        <aside class="hidden lg:flex fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-[#e2e8f0] flex-col h-screen sticky top-0">
            <div class="px-6 py-5 border-b border-[#f1f5f9] flex items-center gap-2">
                <i class="fa-solid fa-wallet text-slate-800 text-2xl"></i>
                <span class="font-bold text-xl tracking-tight text-slate-900">SmartClaim</span>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-1">
                <a href="{{ route('dashboard') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50 transition-all">
                    <i class="fa-solid fa-house text-base"></i> Dashboard
                </a>
                <div>
                    <button type="button" @click="isClaimsOpen = !isClaimsOpen" class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50 hover:text-slate-900 transition-all cursor-pointer">
                        <span class="flex items-center gap-3 pointer-events-none">
                            <i class="fa-solid fa-file-pen text-base"></i> Claims
                        </span>
                        <i class="fa-solid text-[10px] transition-transform duration-200 pointer-events-none" :class="isClaimsOpen ? 'fa-chevron-down rotate-180' : 'fa-chevron-right'"></i>
                    </button>
                    <div x-show="isClaimsOpen" x-cloak x-transition class="pl-6 mt-1 space-y-1 py-1 bg-slate-50 rounded-xl border border-slate-100">
                        <a href="{{ route('claims.create') }}?type=Receipt" class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-slate-500 hover:text-slate-900 transition-all flex items-center gap-2"><i class="fa-solid fa-file-invoice text-[11px]"></i> Based on Receipt (OCR)</a>
                        <a href="{{ route('claims.create') }}?type=Mileage" class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-slate-500 hover:text-slate-900 transition-all flex items-center gap-2"><i class="fa-solid fa-motorcycle text-[11px]"></i> Mileage Allowance</a>
                        <a href="{{ route('claims.history') }}" class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-slate-500 hover:text-slate-900 transition-all flex items-center gap-2"><i class="fa-solid fa-clipboard-list text-[11px]"></i> My Claims</a>
                    </div>
                </div>
                <a href="{{ route('reimbursement.index') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50 transition-all"><i class="fa-solid fa-hand-holding-dollar text-base"></i> Reimbursement Status</a>
                <a href="{{ route('profile.index') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50 transition-all"><i class="fa-solid fa-user text-base"></i> My Profile</a>
                
                <a href="{{ route('policy.index') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold bg-[#f1f5f9] text-blue-600 transition-all">
                    <i class="fa-solid fa-file-shield text-base"></i> Company Policy
                </a>
                
                <a href="{{ route('logout') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-rose-50 hover:text-rose-600 transition-all"><i class="fa-solid fa-door-open text-base"></i> Sign Out</a>
            </nav>
        </aside>

        <main class="flex-1 p-4 md:p-8 max-w-4xl mx-auto w-full pb-24 lg:pb-8">
            <div class="space-y-6">
                
                <div class="border-b border-slate-200 pb-5">
                    <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Company Policy & Guidelines</h1>
                    <p class="text-xs md:text-sm text-slate-500">Aero Art Sdn Bhd Corporate Expense Eligibility Rates.</p>
                </div>

                <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-2xs space-y-4">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-route text-blue-600"></i> Mileage Claim Rates
                    </h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Mileage claims are automatically calculated based on the entered destination route. Any distance manipulation outside the Google Maps route error tolerance threshold will be rejected.</p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                        <div class="p-4 bg-indigo-50/50 rounded-2xl border border-indigo-100/60 flex items-center justify-between gap-4 text-xs">
                            <div class="truncate">
                                <span class="font-bold text-indigo-700 block mb-0.5 truncate"><i class="fa-solid fa-car mr-1"></i> Kadar Kereta (Car)</span>
                                <span class="text-slate-400 font-medium block truncate">For official business travel purposes</span>
                            </div>
                            <span class="text-base md:text-lg font-black text-indigo-600 font-mono whitespace-nowrap">RM 0.60 <span class="text-[10px] text-slate-400 font-bold">/ KM</span></span>
                        </div>
                        <div class="p-4 bg-orange-50/50 rounded-2xl border border-orange-100/60 flex items-center justify-between gap-4 text-xs">
                            <div class="truncate">
                                <span class="font-bold text-orange-700 block mb-0.5 truncate"><i class="fa-solid fa-motorcycle mr-1"></i> Kadar Motosikal</span>
                                <span class="text-slate-400 font-medium block truncate">On-site logistics / Short-distance travel</span>
                            </div>
                            <span class="text-base md:text-lg font-black text-orange-600 font-mono whitespace-nowrap">RM 0.30 <span class="text-[10px] text-slate-400 font-bold">/ KM</span></span>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-2xs space-y-4">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-list-check text-emerald-600"></i> Maximum Limit by Receipt Category (OCR)
                    </h3>
                    <p class="text-xs text-slate-500 leading-relaxed">Every receipt-based claim must undergo AI OCR system scanning for itemized line-by-line data extraction to prevent duplicate claim errors.</p>

                    <div class="divide-y divide-slate-100 text-xs">
                        <div class="py-3.5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
                            <span class="font-semibold text-slate-700"><i class="fa-solid fa-bowl-food mr-2 text-slate-400"></i> Meals & Entertainment</span>
                            <span class="font-bold text-slate-900 bg-slate-100 px-2.5 py-1 rounded-lg text-center w-full sm:w-auto self-start sm:self-auto text-[11px] sm:text-xs">Maximum RM 50.00 / Receipt</span>
                        </div>
                        <div class="py-3.5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
                            <span class="font-semibold text-slate-700"><i class="fa-solid fa-gas-pump mr-2 text-slate-400"></i> Fuel / Gas Fill-ups</span>
                            <span class="font-bold text-slate-900 bg-slate-100 px-2.5 py-1 rounded-lg text-center w-full sm:w-auto self-start sm:self-auto text-[11px] sm:text-xs">Required Staff Vehicle Registration Number</span>
                        </div>
                        <div class="py-3.5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
                            <span class="font-semibold text-slate-700"><i class="fa-solid fa-print mr-2 text-slate-400"></i> Office Supplies & Printing</span>
                            <span class="font-bold text-slate-900 bg-slate-100 px-2.5 py-1 rounded-lg text-center w-full sm:w-auto self-start sm:self-auto text-[11px] sm:text-xs">Maximum RM 150.00 / Month</span>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-white border-t border-[#e2e8f0] h-16 flex items-center justify-around z-40 px-2 shadow-md">
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center flex-1 h-full py-2 text-slate-400">
                <i class="fa-solid fa-chart-pie text-xl block mb-0.5"></i><span class="text-[10px] font-bold">Dashboard</span>
            </a>
            <a href="{{ route('claims.create') }}?type=Receipt" class="flex flex-col items-center justify-center flex-1 h-full py-2 text-slate-400">
                <i class="fa-solid fa-file-circle-plus text-xl block mb-0.5"></i><span class="text-[10px] font-bold">New Claim</span>
            </a>
            <a href="{{ route('claims.history') }}" class="flex flex-col items-center justify-center flex-1 h-full py-2 text-slate-400">
                <i class="fa-solid fa-clock-rotate-left text-xl block mb-0.5"></i><span class="text-[10px] font-bold">History</span>
            </a>
        </nav>

    </div>
</body>
</html>