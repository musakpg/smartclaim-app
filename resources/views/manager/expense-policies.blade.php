<!DOCTYPE html>
<html lang="en" x-data="{
    isMobileSidebarOpen: false,
    editModalOpen: false,
    deleteModalOpen: false,
    deleteActionUrl: '',
    editPolicy: {
        id: '',
        category_name: '',
        category_is_active: 1,
        monthly_budget_cap: '',
        max_single_claim_limit: '',
        is_active: 1,
        description: ''
    }
}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Policy & Budget Cap Engine</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" :class="isMobileSidebarOpen ? 'overflow-hidden' : ''">

    <div class="flex flex-col lg:flex-row min-h-screen">
        @include('layouts.partials.manager-sidebar')

        <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full overflow-hidden space-y-6 pb-24">

            <!-- Header -->
            <div class="border-b border-slate-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div>
                        <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Corporate Expense Policies</h1>
                    <p class="text-xs md:text-sm text-slate-500">Configure monthly budget ceilings and single receipt thresholds for real-time validation.</p>
                    </div>
                    <div class="hidden lg:flex items-center gap-3">
                        <x-system-clock />
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-2xl flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Data Table -->
            <div class="overflow-x-auto bg-white rounded-2xl border border-slate-200/60 shadow-xs">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200/60">
                        <tr>
                            <th class="p-4">Category Name & Status</th>
                            <th class="p-4">Limits (RM)</th>
                            <th class="p-4">Policy Rules</th>
                            <th class="p-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700 text-sm">
                        @forelse($policies as $policy)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <!-- Category & Status -->
                                <td class="p-4">
                                    <div class="flex flex-col gap-1.5">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-900 text-sm">{{ $policy->category->name ?? 'Unknown Category' }}</span>
                                            @if(!$policy->category || !$policy->category->is_active)
                                                <span class="px-2 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200">
                                                    Category Inactive
                                                </span>
                                            @endif
                                            @if(!$policy->is_active)
                                                <span class="px-2 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-wider bg-slate-100 text-slate-500 border border-slate-200">
                                                    Policy Disabled
                                                </span>
                                            @endif
                                        </div>
                                        
                                        @if($policy->category && !$policy->category->is_active)
                                            <div class="text-[10px] text-amber-600 flex items-center gap-1.5">
                                                <i class="fa-solid fa-triangle-exclamation"></i>
                                                <span>Claims cannot be submitted while category is inactive.</span>
                                            </div>
                                        @else
                                            <div class="text-[10px] text-slate-500 truncate max-w-sm">
                                                {{ $policy->description ?: 'No specific guidelines provided.' }}
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Limits -->
                                <td class="p-4">
                                    <div class="flex flex-col gap-2 min-w-[140px]">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-slate-400 font-bold uppercase text-[9px] tracking-wider">Single Max:</span>
                                            <span class="font-mono font-black text-slate-800">RM {{ number_format($policy->max_single_claim_limit, 2) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-slate-400 font-bold uppercase text-[9px] tracking-wider">Monthly Cap:</span>
                                            <span class="font-mono font-black text-slate-800">RM {{ number_format($policy->monthly_budget_cap, 2) }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Policy Rules (Static/Visual) -->
                                <td class="p-4">
                                    <div class="flex flex-col gap-1.5">
                                        <span class="inline-flex w-fit items-center gap-1.5 px-2 py-1 rounded-md text-[9px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i class="fa-solid fa-receipt"></i> Receipt Req.
                                        </span>
                                        <span class="inline-flex w-fit items-center gap-1.5 px-2 py-1 rounded-md text-[9px] font-bold uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200">
                                            <i class="fa-solid fa-shield-halved"></i> Hard Limit Enforced
                                        </span>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" @click="editPolicy = {
                                                id: '{{ $policy->id }}',
                                                category_name: '{{ addslashes($policy->category->name ?? '') }}',
                                                category_is_active: {{ ($policy->category && $policy->category->is_active) ? 1 : 0 }},
                                                monthly_budget_cap: '{{ $policy->monthly_budget_cap }}',
                                                max_single_claim_limit: '{{ $policy->max_single_claim_limit }}',
                                                is_active: {{ $policy->is_active ? 1 : 0 }},
                                                description: '{{ addslashes($policy->description) }}'
                                            }; editModalOpen = true;" 
                                            class="w-8 h-8 rounded-lg bg-slate-50 hover:bg-blue-50 text-slate-400 hover:text-blue-600 flex items-center justify-center transition-colors border border-slate-100 hover:border-blue-200 cursor-pointer shadow-sm">
                                            <i class="fa-solid fa-pen text-xs"></i>
                                        </button>
                                        <button type="button" @click="deleteModalOpen = true; deleteActionUrl = '{{ route('manager.expense_policies.destroy', $policy->id ?? 0) }}'" class="w-8 h-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-500 flex items-center justify-center transition-colors border border-rose-100 cursor-pointer shadow-sm">
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center text-slate-400 font-medium">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <i class="fa-solid fa-folder-open text-4xl text-slate-200 mb-2"></i>
                                        <span class="text-sm text-slate-500 font-bold">No expense policies found</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Edit Policy Modal -->
    <div x-show="editModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-3xl shadow-xl w-full max-w-lg overflow-hidden" @click.away="editModalOpen = false">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-black text-slate-800 tracking-tight">Edit Policy Parameters</h3>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form :action="`{{ url('manager/expense-policies') }}/${editPolicy.id}`" method="POST" class="p-6 space-y-5" x-data="{ isSubmitting: false }" @submit="isSubmitting = true">
                @csrf
                @method('PUT')
                
                <!-- Category Select (Disabled, shows Inactive tag logic) -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700">Category</label>
                    <select disabled class="w-full px-4 py-2.5 bg-slate-100 border border-slate-200 rounded-xl outline-none text-sm font-medium text-slate-500 cursor-not-allowed">
                        <option x-text="editPolicy.category_is_active ? editPolicy.category_name : '[Inactive] ' + editPolicy.category_name"></option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Monthly Budget Cap</label>
                        <div class="relative text-sm">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">RM</span>
                            <input type="number" step="0.01" name="monthly_budget_cap" x-model="editPolicy.monthly_budget_cap" required class="w-full pl-9 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 font-bold font-mono text-slate-800">
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Single Claim Max</label>
                        <div class="relative text-sm">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">RM</span>
                            <input type="number" step="0.01" name="max_single_claim_limit" x-model="editPolicy.max_single_claim_limit" required class="w-full pl-9 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 font-bold font-mono text-slate-800">
                        </div>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700">Policy Status</label>
                    <select name="is_active" x-model="editPolicy.is_active" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm font-medium">
                        <option value="1">Active (Enforced)</option>
                        <option value="0">Disabled (Bypass)</option>
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700">Short Description</label>
                    <input type="text" name="description" x-model="editPolicy.description" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm">
                </div>

                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" @click="editModalOpen = false" :disabled="isSubmitting" class="px-5 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold transition-all tracking-wide cursor-pointer disabled:opacity-50">
                        Cancel
                    </button>
                    <button type="submit" :disabled="isSubmitting" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all tracking-wide shadow-md cursor-pointer flex items-center gap-1.5 disabled:opacity-70">
                        <i x-show="isSubmitting" class="fa-solid fa-spinner fa-spin"></i>
                        <i x-show="!isSubmitting" class="fa-solid fa-check"></i> 
                        <span x-text="isSubmitting ? 'Saving...' : 'Save Policy'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="deleteModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-3xl shadow-xl w-full max-w-sm overflow-hidden" @click.away="deleteModalOpen = false">
            <div class="p-6 text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto shadow-sm border border-rose-200">
                    <i class="fa-solid fa-triangle-exclamation text-2xl"></i>
                </div>
                <h3 class="text-lg font-black text-slate-800 tracking-tight">Delete Expense Policy</h3>
                <p class="text-sm text-slate-500">Are you sure you want to delete this expense policy? This action cannot be undone.</p>
            </div>
            <div class="p-5 border-t border-slate-100 bg-slate-50 flex items-center gap-3">
                <button type="button" @click="deleteModalOpen = false" class="flex-1 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold transition-all tracking-wide cursor-pointer text-center">
                    Cancel
                </button>
                <form :action="deleteActionUrl" method="POST" class="flex-1 m-0" x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    @method('DELETE')
                    <button type="submit" :disabled="loading" class="w-full py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-all tracking-wide shadow-md cursor-pointer disabled:opacity-50 flex items-center justify-center gap-1.5">
                        <template x-if="loading"><i class="fa-solid fa-spinner fa-spin"></i></template>
                        <span x-text="loading ? 'Deleting' : 'Delete Policy'"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
