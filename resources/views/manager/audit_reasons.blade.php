<!DOCTYPE html>
<html lang="en"
    x-data="{ 
        isMobileSidebarOpen: false, 
        currentTab: 'REVISION',
        isAddModalOpen: false,
        isEditModalOpen: false,
        editReason: { id: '', code: '', title: '', type: 'REVISION', requires_remarks: false, is_active: true }
    }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Audit Exception Codes</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" :class="isMobileSidebarOpen || isAddModalOpen || isEditModalOpen ? 'overflow-hidden' : ''">

    <div class="flex flex-col lg:flex-row min-h-screen">
        @include('layouts.partials.manager-sidebar')

        <main class="flex-1 p-4 md:p-8 max-w-5xl mx-auto w-full pb-24 lg:pb-8 overflow-hidden">
            <div class="space-y-6">

                <!-- Header Title Banner -->
                <div class="border-b border-slate-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                            <i class="fa-solid fa-clipboard-question text-indigo-600"></i>
                            <span>Audit Exception Codes</span>
                        </h1>
                        <p class="text-xs md:text-sm text-slate-500 mt-0.5">
                            Manage standardized master reasons and conditional remarks rules for claim revision and rejection decisions.
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="hidden lg:flex items-center gap-3">
                            <x-system-clock />
                        </div>
                        <button type="button" @click="isAddModalOpen = true" 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2.5 px-4 rounded-xl shadow-sm transition-all cursor-pointer flex items-center gap-2">
                            <i class="fa-solid fa-plus text-xs"></i>
                            <span>New Reason Code</span>
                        </button>
                    </div>
                </div>

                <!-- Flash Notifications -->
                @if(session('success'))
                    <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-semibold flex items-center gap-2.5 shadow-3xs">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-xs font-semibold flex items-center gap-2.5 shadow-3xs">
                        <i class="fa-solid fa-circle-exclamation text-rose-500 text-base"></i>
                        <ul class="list-disc pl-4 space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Partition Switcher Tabs -->
                <div class="flex p-1 bg-slate-200/70 rounded-2xl gap-1 text-xs font-bold">
                    <button type="button" @click="currentTab = 'REVISION'"
                        :class="currentTab === 'REVISION' ? 'bg-white text-amber-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                        class="flex-1 py-2.5 px-4 rounded-xl transition-all cursor-pointer flex items-center justify-center gap-2">
                        <i class="fa-solid fa-arrow-rotate-left text-amber-500"></i>
                        <span>Revision Requests ({{ $revisionReasons->count() }})</span>
                    </button>
                    <button type="button" @click="currentTab = 'REJECTION'"
                        :class="currentTab === 'REJECTION' ? 'bg-white text-rose-900 shadow-xs' : 'text-slate-500 hover:text-slate-800'"
                        class="flex-1 py-2.5 px-4 rounded-xl transition-all cursor-pointer flex items-center justify-center gap-2">
                        <i class="fa-solid fa-ban text-rose-500"></i>
                        <span>Rejections ({{ $rejectionReasons->count() }})</span>
                    </button>
                </div>

                <!-- 1. Revision Exception Codes Partition -->
                <div x-show="currentTab === 'REVISION'" class="bg-white rounded-3xl border border-slate-200/70 shadow-xs overflow-hidden">
                    <div class="p-4 md:p-5 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            <h3 class="text-xs md:text-sm font-bold text-slate-800">Staff Revision Clarification Codes</h3>
                        </div>
                        <span class="text-[11px] font-bold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200/60">
                            Returns voucher to employee for amendment
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/80 text-slate-400 font-bold uppercase text-[9px] tracking-wider border-b border-slate-100">
                                <tr>
                                    <th class="py-3 px-4">Code Identifier</th>
                                    <th class="py-3 px-4">Standard Reason Title</th>
                                    <th class="py-3 px-4 text-center">Remarks Rule</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                    <th class="py-3 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($revisionReasons as $reason)
                                    <tr class="hover:bg-slate-50/60 transition-colors">
                                        <td class="py-3.5 px-4 font-mono font-bold text-slate-700">
                                            <span class="px-2 py-0.5 bg-slate-100 rounded-md border border-slate-200 text-[10px]">
                                                {{ $reason->code }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 font-semibold text-slate-900">
                                            {{ $reason->title }}
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            @if($reason->requires_remarks)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100/70 text-amber-800 border border-amber-300/50">
                                                    <i class="fa-solid fa-asterisk text-[8px]"></i> Mandatory Remarks
                                                </span>
                                            @else
                                                <span class="text-[10px] font-medium text-slate-400">Optional Remarks</span>
                                            @endif
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <form action="{{ route('manager.audit_reasons.toggle', $reason->id) }}" method="POST" class="inline-block">
                                                @csrf
                                                <button type="submit" class="px-2.5 py-1 rounded-full text-[10px] font-bold cursor-pointer transition-all {{ $reason->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-400 border border-slate-200 hover:bg-slate-200' }}">
                                                    {{ $reason->is_active ? 'Active' : 'Inactive' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td class="py-3.5 px-4 text-right space-x-1">
                                            <button type="button" @click="editReason = { id: '{{ $reason->id }}', code: '{{ $reason->code }}', title: '{{ addslashes($reason->title) }}', type: '{{ $reason->type }}', requires_remarks: {{ $reason->requires_remarks ? 'true' : 'false' }}, is_active: {{ $reason->is_active ? 'true' : 'false' }} }; isEditModalOpen = true;"
                                                class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition-all cursor-pointer">
                                                <i class="fa-regular fa-pen-to-square text-[11px]"></i> Edit
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-slate-400 font-medium">
                                            No revision exception codes registered yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. Rejection Exception Codes Partition -->
                <div x-show="currentTab === 'REJECTION'" x-cloak class="bg-white rounded-3xl border border-slate-200/70 shadow-xs overflow-hidden">
                    <div class="p-4 md:p-5 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                            <h3 class="text-xs md:text-sm font-bold text-slate-800">Hard Rejection Violation Codes</h3>
                        </div>
                        <span class="text-[11px] font-bold text-rose-700 bg-rose-50 px-2.5 py-1 rounded-lg border border-rose-200/60">
                            Permanently halts claim processing
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/80 text-slate-400 font-bold uppercase text-[9px] tracking-wider border-b border-slate-100">
                                <tr>
                                    <th class="py-3 px-4">Code Identifier</th>
                                    <th class="py-3 px-4">Standard Reason Title</th>
                                    <th class="py-3 px-4 text-center">Remarks Rule</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                    <th class="py-3 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($rejectionReasons as $reason)
                                    <tr class="hover:bg-slate-50/60 transition-colors">
                                        <td class="py-3.5 px-4 font-mono font-bold text-slate-700">
                                            <span class="px-2 py-0.5 bg-slate-100 rounded-md border border-slate-200 text-[10px]">
                                                {{ $reason->code }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 font-semibold text-slate-900">
                                            {{ $reason->title }}
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            @if($reason->requires_remarks)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100/70 text-rose-800 border border-rose-300/50">
                                                    <i class="fa-solid fa-asterisk text-[8px]"></i> Mandatory Remarks
                                                </span>
                                            @else
                                                <span class="text-[10px] font-medium text-slate-400">Optional Remarks</span>
                                            @endif
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <form action="{{ route('manager.audit_reasons.toggle', $reason->id) }}" method="POST" class="inline-block">
                                                @csrf
                                                <button type="submit" class="px-2.5 py-1 rounded-full text-[10px] font-bold cursor-pointer transition-all {{ $reason->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-400 border border-slate-200 hover:bg-slate-200' }}">
                                                    {{ $reason->is_active ? 'Active' : 'Inactive' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td class="py-3.5 px-4 text-right space-x-1">
                                            <button type="button" @click="editReason = { id: '{{ $reason->id }}', code: '{{ $reason->code }}', title: '{{ addslashes($reason->title) }}', type: '{{ $reason->type }}', requires_remarks: {{ $reason->requires_remarks ? 'true' : 'false' }}, is_active: {{ $reason->is_active ? 'true' : 'false' }} }; isEditModalOpen = true;"
                                                class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition-all cursor-pointer">
                                                <i class="fa-regular fa-pen-to-square text-[11px]"></i> Edit
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-slate-400 font-medium">
                                            No rejection exception codes registered yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- Modal: Add New Exception Code -->
    <div x-show="isAddModalOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-all">
        <div class="relative bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-slate-100 space-y-4"
            @click.away="isAddModalOpen = false" x-transition:enter="transition ease-out duration-200 transform"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-plus-circle text-indigo-600"></i>
                    <span>Register Audit Exception Code</span>
                </h3>
                <button type="button" @click="isAddModalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form action="{{ route('manager.audit_reasons.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Reason Type <span class="text-rose-500">*</span></label>
                    <select name="type" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold outline-none focus:border-indigo-400">
                        <option value="REVISION">REVISION (Return claim to staff for amendment)</option>
                        <option value="REJECTION">REJECTION (Permanent claim rejection)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Standard Title <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" required placeholder="e.g. Blurry / Unreadable Receipt"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold outline-none focus:border-indigo-400">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Unique Code (Optional)</label>
                    <input type="text" name="code" placeholder="Auto-generated if left blank (e.g. IMG_BLUR)"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono font-semibold uppercase outline-none focus:border-indigo-400">
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                    <div>
                        <span class="block text-xs font-bold text-slate-800">Require Mandatory Remarks</span>
                        <span class="text-[10px] text-slate-400">If checked, auditor must write a custom explanation</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="requires_remarks" value="1" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="isAddModalOpen = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl cursor-pointer shadow-sm">
                        Create Reason Code
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Exception Code -->
    <div x-show="isEditModalOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-all">
        <div class="relative bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-slate-100 space-y-4"
            @click.away="isEditModalOpen = false" x-transition:enter="transition ease-out duration-200 transform"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">

            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                    <i class="fa-regular fa-pen-to-square text-indigo-600"></i>
                    <span>Edit Audit Exception Code</span>
                </h3>
                <button type="button" @click="isEditModalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form :action="'/manager/audit-reasons/' + editReason.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Code (Immutable)</label>
                    <input type="text" :value="editReason.code" disabled
                        class="w-full p-2.5 bg-slate-100 text-slate-500 border border-slate-200 rounded-xl text-xs font-mono font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Standard Title <span class="text-rose-500">*</span></label>
                    <input type="text" name="title" x-model="editReason.title" required
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold outline-none focus:border-indigo-400">
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                    <div>
                        <span class="block text-xs font-bold text-slate-800">Require Mandatory Remarks</span>
                        <span class="text-[10px] text-slate-400">Auditor explanation requirement</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="requires_remarks" value="1" x-model="editReason.requires_remarks" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                    <div>
                        <span class="block text-xs font-bold text-slate-800">Active Status</span>
                        <span class="text-[10px] text-slate-400">Visible to managers & finance auditors</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" x-model="editReason.is_active" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="isEditModalOpen = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl cursor-pointer shadow-sm">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
