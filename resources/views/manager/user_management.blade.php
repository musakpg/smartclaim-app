@extends('layouts.manager')

@section('title', 'SmartClaim - Staff & User Management')

@section('content')
<div x-data="{ searchTerm: '' }" class="space-y-6">

    <!-- Header & Search Bar -->
    <div class="border-b border-slate-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Staff & User Directory</h1>
            <p class="text-xs md:text-sm text-slate-500">Corporate employee registry, system authorization levels, and registered reimbursement banking profiles.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="hidden lg:flex items-center gap-3">
                <x-system-clock />
            </div>

            <div class="w-full sm:w-72">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" x-model="searchTerm" placeholder="Search staff name or email..."
                        class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold outline-none focus:border-slate-400 shadow-xs">
                </div>
            </div>
        </div>
    </div>

    <!-- User Directory Table -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-xs overflow-hidden min-h-[420px] flex flex-col justify-between">
        <div class="overflow-x-auto flex-1">
            <table class="w-full text-left text-xs min-w-[750px]">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="p-4">Staff Member</th>
                        <th class="p-4">Authority Role</th>
                        <th class="p-4">Direct Banking Particulars</th>
                        <th class="p-4">Staff Matrix ID</th>
                        <th class="p-4 text-right">Banking Status</th>
                        <th class="p-4 text-center">Account Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($users as $regUser)
                        <tr class="hover:bg-slate-50/50 transition"
                            x-show="!searchTerm || '{{ strtolower($regUser->name) }} {{ strtolower($regUser->email) }} {{ strtolower($regUser->matrix_id ?? '') }} {{ strtolower($regUser->bank_name ?? '') }}'.includes(searchTerm.toLowerCase())">

                            <!-- 1. Staff Member -->
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-slate-900 text-emerald-400 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 font-mono shadow-inner">
                                        {{ strtoupper(substr($regUser->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <span class="font-bold text-slate-900 block text-sm leading-tight">{{ $regUser->name }}</span>
                                        <span class="text-[11px] text-slate-400 font-mono">{{ $regUser->email }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- 2. Authority Role -->
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider inline-block {{ strtolower($regUser->role ?? '') === 'manager' ? 'bg-amber-50 text-amber-700 border border-amber-200' : (strtolower($regUser->role ?? '') === 'finance' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-blue-50 text-blue-700 border border-blue-200') }}">
                                    {{ ucfirst($regUser->role ?? 'Staff') }}
                                </span>
                            </td>

                            <!-- 3. Banking Particulars -->
                            <td class="p-4 font-mono">
                                @if(!empty($regUser->bank_account_no))
                                    <span class="font-bold text-slate-800 block text-xs">{{ $regUser->bank_name }}</span>
                                    <span class="text-slate-600 text-[11px] block">{{ $regUser->bank_account_no }}</span>
                                    <span class="text-[10px] text-slate-400 truncate max-w-xs block">Holder: {{ $regUser->bank_account_holder ?? $regUser->name }}</span>
                                @else
                                    <span class="text-rose-600 font-sans font-bold text-[11px] bg-rose-50 px-2.5 py-1 rounded-lg inline-flex items-center gap-1 border border-rose-100">
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
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-bold text-[10px] uppercase tracking-wider inline-flex items-center gap-1">
                                        <i class="fa-solid fa-circle-check text-emerald-500"></i> Verified
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full font-bold text-[10px] uppercase tracking-wider inline-flex items-center gap-1">
                                        <i class="fa-solid fa-clock text-amber-500"></i> Pending Info
                                    </span>
                                @endif
                            </td>

                            <!-- 6. Account Status Toggle -->
                            <td class="p-4 text-center">
                                <div x-data="{ 
                                    isModalOpen: false, 
                                    isProcessing: false,
                                    isActive: {{ $regUser->is_active ? 'true' : 'false' }},
                                    actionUrl: '{{ route('manager.user_management.toggle', $regUser->user_id) }}',
                                    toggleStatus() {
                                        if (this.isActive) {
                                            this.isModalOpen = true; 
                                        } else {
                                            this.submitForm(); 
                                        }
                                    },
                                    submitForm() {
                                        this.isProcessing = true;
                                        this.$refs.form.submit();
                                    }
                                }">
                                    <button type="button" @click="toggleStatus()" :disabled="isProcessing"
                                        class="flex items-center gap-2 mx-auto cursor-pointer disabled:cursor-not-allowed">
                                        
                                        <div class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-300"
                                            :class="isActive ? 'bg-emerald-500' : 'bg-slate-300'">
                                            <div x-show="isProcessing" class="absolute inset-0 flex items-center justify-center">
                                                <i class="fa-solid fa-circle-notch fa-spin text-white text-[10px]"></i>
                                            </div>

                                            <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform duration-300 shadow-sm"
                                                :class="[isActive ? 'translate-x-4.5' : 'translate-x-1', isProcessing ? 'opacity-0' : 'opacity-100']">
                                            </span>
                                        </div>
                                        
                                        <span class="text-[10px] font-bold uppercase tracking-wider transition-colors duration-300 w-14 text-left"
                                            :class="isActive ? 'text-emerald-600' : 'text-slate-500'"
                                            x-text="isActive ? 'Active' : 'Inactive'">
                                        </span>
                                    </button>

                                    <form x-ref="form" :action="actionUrl" method="POST" class="hidden">
                                        @csrf
                                    </form>

                                    <template x-teleport="body">
                                        <div x-show="isModalOpen" style="display: none"
                                            class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                            <div x-show="isModalOpen"
                                                x-transition:enter="transition ease-out duration-300"
                                                x-transition:enter-start="opacity-0"
                                                x-transition:enter-end="opacity-100"
                                                x-transition:leave="transition ease-in duration-200"
                                                x-transition:leave-start="opacity-100"
                                                x-transition:leave-end="opacity-0"
                                                class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                                                @click="isModalOpen = false"></div>

                                            <div x-show="isModalOpen"
                                                x-transition:enter="transition ease-out duration-300"
                                                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                                x-transition:leave="transition ease-in duration-200"
                                                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                                class="relative bg-white rounded-2xl shadow-xl border border-slate-200 p-6 w-full max-w-sm mx-auto text-left z-10">

                                                <div class="flex items-center gap-3 mb-4">
                                                    <div class="w-10 h-10 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                                    </div>
                                                    <h3 class="text-lg font-black text-slate-900 leading-tight">Confirm Deactivation</h3>
                                                </div>
                                                
                                                <p class="text-sm text-slate-600 mb-6 leading-relaxed">Are you sure you want to deactivate the account for <strong class="text-slate-900">{{ $regUser->name }}</strong>? The user will be immediately logged out and blocked from logging in.</p>

                                                <div class="flex justify-end gap-3">
                                                    <button type="button" @click="isModalOpen = false"
                                                        class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition cursor-pointer">
                                                        Cancel
                                                    </button>
                                                    <button type="button" @click="isModalOpen = false; submitForm()"
                                                        class="px-4 py-2 text-sm font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm transition cursor-pointer">
                                                        Confirm Deactivation
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400 font-medium">
                                No employee records found in system.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-100 bg-white rounded-b-2xl mt-auto">
            {{ $users->links() }}
        </div>
    </div>

</div>
@endsection
