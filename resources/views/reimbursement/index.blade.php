<!DOCTYPE html>
<html lang="en" x-data="{ isMobileSidebarOpen: false, isClaimsOpen: false }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Reimbursement Status</title>
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
                        <button type="button" @click.prevent="isClaimsOpenMobile = !isClaimsOpenMobile" class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50 cursor-pointer">
                            <span class="flex items-center gap-3"><i class="fa-solid fa-file-pen"></i> Claims</span>
                            <i class="fa-solid text-[10px]" :class="isClaimsOpenMobile ? 'fa-chevron-down rotate-180' : 'fa-chevron-right'"></i>
                        </button>
                        <div x-show="isClaimsOpenMobile" class="pl-6 mt-1 space-y-1 py-1 bg-slate-50 rounded-xl border border-slate-100">
                            <a href="{{ route('claims.create') }}?type=Receipt" class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-slate-500 flex items-center gap-2"><i class="fa-solid fa-file-invoice text-[11px]"></i> Based on Receipt (OCR)</a>
                            <a href="{{ route('claims.create') }}?type=Mileage" class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-slate-500 flex items-center gap-2"><i class="fa-solid fa-motorcycle text-[11px]"></i> Mileage Allowance</a>
                            <a href="{{ route('claims.history') }}" class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium text-slate-500 flex items-center gap-2"><i class="fa-solid fa-clipboard-list text-[11px]"></i> My Claims</a>
                        </div>
                    </div>
                    <a href="{{ route('reimbursement.index') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold bg-[#f1f5f9] text-blue-600">
                        <i class="fa-solid fa-hand-holding-dollar"></i> Reimbursement Status
                    </a>
                    <a href="{{ route('profile.index') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50">
                        <i class="fa-solid fa-user"></i> My Profile
                    </a>
                    <a href="{{ route('policy.index') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50">
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
                    <button type="button" @click.prevent="isClaimsOpen = !isClaimsOpen" class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50 hover:text-slate-900 transition-all cursor-pointer">
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
                
                <a href="{{ route('reimbursement.index') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold bg-[#f1f5f9] text-blue-600 transition-all">
                    <i class="fa-solid fa-hand-holding-dollar text-base"></i> Reimbursement Status
                </a>
                
                <a href="{{ route('profile.index') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50 transition-all"><i class="fa-solid fa-user text-base"></i> My Profile</a>
                <a href="{{ route('policy.index') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-slate-50 transition-all"><i class="fa-solid fa-file-shield text-base"></i> Company Policy</a>
                <a href="{{ route('logout') }}" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-500 hover:bg-rose-50 hover:text-rose-600 transition-all"><i class="fa-solid fa-door-open text-base"></i> Sign Out</a>
            </nav>
        </aside>

        <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full pb-24 lg:pb-8 overflow-hidden">
            <div class="space-y-6">
                
                <div class="border-b border-slate-200 pb-5">
                    <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Reimbursement Pipeline</h1>
                    <p class="text-xs md:text-sm text-slate-500">Track the reimbursement status of funds for approved expenses.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-5">
                    
                    <div class="bg-white p-4 md:p-5 rounded-2xl md:rounded-3xl border border-slate-200/60 shadow-2xs flex items-center justify-between">
                        <div class="space-y-0.5 truncate">
                            <span class="text-[10px] md:text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Total Approved</span>
                            <h3 class="text-base md:text-xl font-bold text-slate-900 font-mono tracking-tight">RM {{ number_format($approvedTotal, 2) }}</h3>
                        </div>
                        <div class="w-9 h-9 md:w-10 md:h-10 bg-slate-50 rounded-xl flex items-center justify-center border border-slate-100 shrink-0 ml-2">
                            <i class="fa-solid fa-wallet text-slate-600 text-xs md:text-sm"></i>
                        </div>
                    </div>
                    
                    <div class="bg-white p-4 md:p-5 rounded-2xl md:rounded-3xl border border-slate-200/60 shadow-2xs flex items-center justify-between">
                        <div class="space-y-0.5 truncate">
                            <span class="text-[10px] md:text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Paid</span>
                            <h3 class="text-base md:text-xl font-bold text-emerald-600 font-mono tracking-tight">RM {{ number_format($paidTotal, 2) }}</h3>
                        </div>
                        <div class="w-9 h-9 md:w-10 md:h-10 bg-emerald-50 rounded-xl flex items-center justify-center border border-emerald-100 shrink-0 ml-2">
                            <i class="fa-solid fa-circle-check text-emerald-600 text-xs md:text-sm"></i>
                        </div>
                    </div>

                    <div class="bg-white p-4 md:p-5 rounded-2xl md:rounded-3xl border border-slate-200/60 shadow-2xs flex items-center justify-between">
                        <div class="space-y-0.5 truncate">
                            <span class="text-[10px] md:text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Processing</span>
                            <h3 class="text-base md:text-xl font-bold text-amber-600 font-mono tracking-tight">RM {{ number_format($processingTotal, 2) }}</h3>
                        </div>
                        <div class="w-9 h-9 md:w-10 md:h-10 bg-amber-50 rounded-xl flex items-center justify-center border border-amber-100 shrink-0 ml-2">
                            <i class="fa-solid fa-arrows-rotate text-amber-600 text-xs md:text-sm animate-spin"></i>
                        </div>
                    </div>

                </div>

                <div class="bg-white p-4 md:p-6 rounded-2xl md:rounded-3xl border border-slate-200/60 shadow-2xs space-y-4">
                    <h3 class="text-xs md:text-sm font-bold text-slate-800 tracking-tight">Voucher Settlement Logs</h3>
                    
                    <div class="overflow-x-auto -mx-4 px-4 md:mx-0 md:px-0">
                        <table class="w-full text-left border-collapse text-xs min-w-[650px] sm:min-w-full">
                            <thead>
                                <tr class="border-b border-slate-100 text-slate-400 font-bold tracking-wide uppercase bg-slate-50/50 rounded-xl">
                                    <th class="py-3 px-3 md:px-4">Claim ID</th>
                                    <th class="py-3 px-3 md:px-4">Particulars</th>
                                    <th class="py-3 px-3 md:px-4">Method</th>
                                    <th class="py-3 px-3 md:px-4 font-mono">Approved Date</th>
                                    <th class="py-3 px-3 md:px-4 text-right">Amount</th>
                                    <th class="py-3 px-3 md:px-4 text-center">Disbursement Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 text-slate-700 font-medium">
                                @forelse($approvedClaims as $claim)
                                    <tr class="hover:bg-slate-50/60 transition-all">
                                        <td class="py-3.5 px-3 md:px-4 font-bold font-mono text-slate-400 whitespace-nowrap">CLM-{{ $claim->claim_id }}</td>
                                        <td class="py-3.5 px-3 md:px-4 font-bold text-slate-950 max-w-[150px] truncate" title="{{ $claim->merchant_name }}">{{ $claim->merchant_name }}</td>
                                        <td class="py-3.5 px-3 md:px-4 whitespace-nowrap"><span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-md font-bold text-[10px]">{{ $claim->payment_method }}</span></td>
                                        <td class="py-3.5 px-3 md:px-4 font-mono text-slate-500 whitespace-nowrap">{{ $claim->updated_at->format('Y-m-d H:i') }}</td>
                                        <td class="py-3.5 px-3 md:px-4 text-right font-black text-slate-900 whitespace-nowrap">RM {{ number_format($claim->amount, 2) }}</td>
                                        <td class="py-3.5 px-3 md:px-4 text-center whitespace-nowrap">
                                            <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase tracking-wide
                                                {{ $claim->claim_id % 2 === 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-amber-50 text-amber-700 border border-amber-100 animate-pulse' }}">
                                                {{ $claim->claim_id % 2 === 0 ? 'Success / Paid' : 'Processing Transfer' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-12 text-center text-slate-400 font-semibold">
                                            <i class="fa-solid fa-receipt block text-xl mb-1.5 text-slate-300"></i> Tiada rekod tuntutan yang diluluskan untuk pembayaran balik.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
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