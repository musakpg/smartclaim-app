<!-- Comment: Finance Portal Beneficiary Banking Directory Workspace -->
<!DOCTYPE html>
<html lang="en" x-data="{ isMobileSidebarOpen: false, searchTerm: '' }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Staff Banking Directory</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
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

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Mobile Top Header -->
            <header
                class="lg:hidden flex items-center justify-between bg-[#0d1527] border-b border-slate-800 px-4 py-3 sticky top-0 z-30 shadow-md">
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-8 h-8 rounded-lg bg-[#00d1b2]/10 border border-[#00d1b2]/20 flex items-center justify-center text-[#00d1b2]">
                        <i class="fa-solid fa-shield text-sm"></i>
                    </div>
                    <div>
                        <span class="text-sm font-black text-white tracking-tight leading-none block">SmartClaim</span>
                        <span class="text-[9px] font-bold text-[#00d1b2] tracking-wider uppercase block">Finance
                            Portal</span>
                    </div>
                </div>

                <button type="button" @click="isMobileSidebarOpen = true"
                    class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-800 text-slate-300 hover:text-white transition cursor-pointer">
                    <i class="fa-solid fa-bars text-sm"></i>
                </button>
            </header>

            <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full pb-24 lg:pb-8 overflow-y-auto space-y-6">

                <!-- Header & Search -->
                <div
                    class="border-b border-slate-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Staff Beneficiary
                            Directory</h1>
                        <p class="text-xs md:text-sm text-slate-500">Official bank settlement ledger and registered
                            account particulars for Aero Art staff.</p>
                    </div>

                    <div class="w-full sm:w-72">
                        <div class="relative">
                            <i
                                class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" x-model="searchTerm" placeholder="Search staff name or account number..."
                                class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold outline-none focus:border-slate-400 shadow-xs">
                        </div>
                    </div>
                </div>

                <!-- Beneficiary List Table -->
                <div class="bg-white rounded-3xl border border-slate-200/60 shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs min-w-[750px]">
                            <thead
                                class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th class="p-4">Staff Member</th>
                                    <th class="p-4">Bank Name</th>
                                    <th class="p-4">Bank Account No</th>
                                    <th class="p-4">Account Holder Name</th>
                                    <th class="p-4 text-right">EFT Eligibility</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                @forelse($staffMembers as $staff)
                                    <tr class="hover:bg-slate-50/50 transition"
                                        x-show="!searchTerm || '{{ strtolower($staff->name) }} {{ strtolower($staff->bank_name ?? '') }} {{ strtolower($staff->bank_account_no ?? '') }}'.includes(searchTerm.toLowerCase())">
                                        <td class="p-4">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-9 h-9 bg-slate-900 text-[#00d1b2] rounded-xl flex items-center justify-center font-bold text-xs shrink-0 font-mono shadow-inner">
                                                    {{ strtoupper(substr($staff->name, 0, 2)) }}
                                                </div>
                                                <div>
                                                    <span
                                                        class="font-bold text-slate-900 block text-sm">{{ $staff->name }}</span>
                                                    <span
                                                        class="text-[11px] text-slate-400 font-mono">{{ $staff->email }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-4 font-bold text-slate-800">
                                            @if($staff->bank_name)
                                                <span class="inline-flex items-center gap-1.5">
                                                    <i class="fa-solid fa-building-columns text-emerald-600 text-[11px]"></i>
                                                    {{ $staff->bank_name }}
                                                </span>
                                            @else
                                                <span class="text-slate-400 font-normal italic">Not Specified</span>
                                            @endif
                                        </td>
                                        <td class="p-4 font-mono font-bold text-slate-900">
                                            {{ $staff->bank_account_no ?? '-' }}
                                        </td>
                                        <td class="p-4 text-slate-700">
                                            {{ $staff->bank_account_holder ?? $staff->name }}
                                        </td>
                                        <td class="p-4 text-right">
                                            @if($staff->bank_account_no && $staff->bank_name)
                                                <span
                                                    class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-bold text-[10px] uppercase tracking-wider inline-flex items-center gap-1">
                                                    <i class="fa-solid fa-circle-check text-emerald-500"></i> Ready for Payout
                                                </span>
                                            @else
                                                <span
                                                    class="px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-200 rounded-full font-bold text-[10px] uppercase tracking-wider inline-flex items-center gap-1">
                                                    <i class="fa-solid fa-triangle-exclamation text-rose-500"></i> Missing Info
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="p-8 text-center text-slate-400">
                                            No registered staff beneficiaries found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>
</body>

</html>