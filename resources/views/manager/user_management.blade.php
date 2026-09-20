<!-- Comment: Executive User Management & Staff Banking Registry -->
<!DOCTYPE html>
<html lang="en" x-data="{ isMobileSidebarOpen: false, searchTerm: '' }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Staff & User Management</title>
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

        <!-- Manager Sidebar Partial -->
        @include('layouts.partials.manager-sidebar')

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <!-- Mobile Top Header -->
            <header
                class="lg:hidden flex items-center justify-between bg-[#0d1527] border-b border-slate-800 px-4 py-3 sticky top-0 z-30 shadow-md">
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-8 h-8 rounded-lg bg-amber-400/10 border border-amber-400/20 flex items-center justify-center text-amber-400">
                        <i class="fa-solid fa-crown text-sm"></i>
                    </div>
                    <div>
                        <span class="text-sm font-black text-white tracking-tight leading-none block">SmartClaim</span>
                        <span class="text-[9px] font-bold text-emerald-400 tracking-wider uppercase block">Manager
                            Portal</span>
                    </div>
                </div>

                <button type="button" @click="isMobileSidebarOpen = true"
                    class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-800 text-slate-300 hover:text-white transition cursor-pointer">
                    <i class="fa-solid fa-bars text-sm"></i>
                </button>
            </header>

            <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full pb-24 lg:pb-8 overflow-y-auto space-y-6">

                <!-- Header & Search Bar -->
                <div
                    class="border-b border-slate-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Staff & User Directory
                        </h1>
                        <p class="text-xs md:text-sm text-slate-500">Corporate employee registry, system authorization
                            levels, and registered reimbursement banking profiles.</p>
                    </div>

                    <div class="w-full sm:w-72">
                        <div class="relative">
                            <i
                                class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" x-model="searchTerm" placeholder="Search staff name or email..."
                                class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold outline-none focus:border-slate-400 shadow-xs">
                        </div>
                    </div>
                </div>

                <!-- User Directory Table -->
                <div class="bg-white rounded-3xl border border-slate-200/60 shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs min-w-[750px]">
                            <thead
                                class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th class="p-4">Staff Member</th>
                                    <th class="p-4">Authority Role</th>
                                    <th class="p-4">Direct Banking Particulars</th>
                                    <th class="p-4">Staff Matrix ID</th>
                                    <th class="p-4 text-right">Banking Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                @php
                                    $registeredUsers = isset($users) ? $users : DB::table('users')->orderBy('user_id', 'asc')->get();
                                @endphp
                                @forelse($registeredUsers as $regUser)
                                    <tr class="hover:bg-slate-50/50 transition"
                                        x-show="!searchTerm || '{{ strtolower($regUser->name) }} {{ strtolower($regUser->email) }} {{ strtolower($regUser->matrix_id ?? '') }} {{ strtolower($regUser->bank_name ?? '') }}'.includes(searchTerm.toLowerCase())">

                                        <!-- 1. Staff Member -->
                                        <td class="p-4">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-9 h-9 bg-slate-900 text-emerald-400 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 font-mono shadow-inner">
                                                    {{ strtoupper(substr($regUser->name, 0, 2)) }}
                                                </div>
                                                <div>
                                                    <span
                                                        class="font-bold text-slate-900 block text-sm leading-tight">{{ $regUser->name }}</span>
                                                    <span
                                                        class="text-[11px] text-slate-400 font-mono">{{ $regUser->email }}</span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- 2. Authority Role -->
                                        <td class="p-4">
                                            <span
                                                class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider inline-block {{ strtolower($regUser->role ?? '') === 'manager' ? 'bg-amber-50 text-amber-700 border border-amber-200' : (strtolower($regUser->role ?? '') === 'finance' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-blue-50 text-blue-700 border border-blue-200') }}">
                                                {{ ucfirst($regUser->role ?? 'Staff') }}
                                            </span>
                                        </td>

                                        <!-- 3. Banking Particulars -->
                                        <td class="p-4 font-mono">
                                            @if(!empty($regUser->bank_account_no))
                                                <span
                                                    class="font-bold text-slate-800 block text-xs">{{ $regUser->bank_name }}</span>
                                                <span
                                                    class="text-slate-600 text-[11px] block">{{ $regUser->bank_account_no }}</span>
                                                <span class="text-[10px] text-slate-400 truncate max-w-xs block">Holder:
                                                    {{ $regUser->bank_account_holder ?? $regUser->name }}</span>
                                            @else
                                                <span
                                                    class="text-rose-600 font-sans font-bold text-[11px] bg-rose-50 px-2.5 py-1 rounded-lg inline-flex items-center gap-1 border border-rose-100">
                                                    <i class="fa-solid fa-triangle-exclamation text-[10px]"></i> Unconfigured
                                                </span>
                                            @endif
                                        </td>

                                        <!-- 4. Matrix ID -->
                                        <td class="p-4 font-mono text-slate-600 font-semibold">
                                            {{ $regUser->matrix_id ?? 'USR-00' . $regUser->user_id }}
                                        </td>

                                        <!-- 5. Banking Status Pill -->
                                        <td class="p-4 text-right">
                                            @if(!empty($regUser->bank_account_no))
                                                <span
                                                    class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-bold text-[10px] uppercase tracking-wider inline-flex items-center gap-1">
                                                    <i class="fa-solid fa-circle-check text-emerald-500"></i> Verified
                                                </span>
                                            @else
                                                <span
                                                    class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full font-bold text-[10px] uppercase tracking-wider inline-flex items-center gap-1">
                                                    <i class="fa-solid fa-clock text-amber-500"></i> Pending Info
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="p-8 text-center text-slate-400 font-medium">
                                            No employee records found in system.
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