<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Policy & Budget Cap Engine</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" x-data="{ isMobileSidebarOpen: false }">

    <div class="flex min-h-screen">
        @include('layouts.partials.manager-sidebar')

        <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full pb-24 overflow-y-auto space-y-6">

            <!-- Header -->
            <div class="border-b border-slate-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Corporate Expense Policies
                    </h1>
                    <p class="text-xs md:text-sm text-slate-500">Configure monthly budget ceilings and single receipt
                        thresholds for real-time validation.</p>
                </div>
            </div>

            @if(session('success'))
                <div
                    class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-2xl flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Policy Table Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @foreach($policies as $policy)
                    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-xs p-6 space-y-4"
                        x-data="{ isEditing: false }">
                        <div class="flex items-start justify-between">
                            <div>
                                <span
                                    class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider {{ $policy->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $policy->is_active ? 'Active Policy' : 'Disabled' }}
                                </span>
                                <h3 class="text-base font-bold text-slate-900 mt-2">{{ $policy->category_name }}</h3>
                                <p class="text-xs text-slate-500">
                                    {{ $policy->description ?? 'No specific guidelines provided.' }}</p>
                            </div>
                            <button type="button" @click="isEditing = !isEditing"
                                class="w-8 h-8 flex items-center justify-center rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-600 cursor-pointer transition">
                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                            </button>
                        </div>

                        <!-- Static Metrics Display -->
                        <div x-show="!isEditing" class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-100">
                            <div class="p-3 bg-slate-50 rounded-2xl">
                                <span class="text-[10px] font-bold uppercase text-slate-400 block">Monthly Cap</span>
                                <span class="text-lg font-black font-mono text-slate-900">RM
                                    {{ number_format($policy->monthly_budget_cap, 2) }}</span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-2xl">
                                <span class="text-[10px] font-bold uppercase text-slate-400 block">Single Receipt Max</span>
                                <span class="text-lg font-black font-mono text-slate-900">RM
                                    {{ number_format($policy->max_single_claim_limit, 2) }}</span>
                            </div>
                        </div>

                        <!-- Edit Form Drawer -->
                        <form x-show="isEditing" x-cloak
                            action="{{ route('manager.expense_policies.update', $policy->id) }}" method="POST"
                            class="pt-3 border-t border-slate-100 space-y-3 text-xs">
                            @csrf
                            @method('PUT')

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Monthly Cap (RM) *</label>
                                    <input type="number" step="0.01" name="monthly_budget_cap"
                                        value="{{ $policy->monthly_budget_cap }}" required
                                        class="w-full p-2.5 bg-white border border-slate-200 rounded-xl outline-none font-mono font-bold">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Single Max (RM) *</label>
                                    <input type="number" step="0.01" name="max_single_claim_limit"
                                        value="{{ $policy->max_single_claim_limit }}" required
                                        class="w-full p-2.5 bg-white border border-slate-200 rounded-xl outline-none font-mono font-bold">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Status</label>
                                    <select name="is_active"
                                        class="w-full p-2.5 bg-white border border-slate-200 rounded-xl outline-none font-medium">
                                        <option value="1" {{ $policy->is_active ? 'selected' : '' }}>Active (Enforced)
                                        </option>
                                        <option value="0" {{ !$policy->is_active ? 'selected' : '' }}>Disabled (Bypass)
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Short Description</label>
                                    <input type="text" name="description" value="{{ $policy->description }}"
                                        class="w-full p-2.5 bg-white border border-slate-200 rounded-xl outline-none">
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="isEditing = false"
                                    class="px-3 py-2 bg-slate-100 text-slate-600 rounded-xl font-bold cursor-pointer">Cancel</button>
                                <button type="submit"
                                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold cursor-pointer">Save
                                    Policy</button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>

        </main>
    </div>

</body>

</html>