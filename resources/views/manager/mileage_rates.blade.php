<!DOCTYPE html>
<html lang="en"
    x-data="{ isMobileSidebarOpen: false, isAuditingOpen: false, isAdminOpen: true, activeSubTab: 'mileage_rates', isCreateModalOpen: false, deleteModalOpen: false, deleteActionUrl: '' }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Mileage Rates Administration</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" :class="isMobileSidebarOpen ? 'overflow-hidden' : ''">

    <div class="flex flex-col lg:flex-row min-h-screen">

        @include('layouts.partials.manager-sidebar')
        <main class="flex-1 w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6">
            <div class="space-y-6">
                <div class="border-b border-slate-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div>
                        <div>
                        <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Mileage Allowance Rates
                        </h1>
                        <p class="text-xs md:text-sm text-slate-500">Corporate distance financial multi-tier multiplier
                            matrices for Aero Art Sdn Bhd.</p>
                    </div>
                    <div class="hidden lg:flex items-center gap-3">
                        <x-system-clock />
                    </div>
                </div>
                    <button type="button" @click="isCreateModalOpen = true" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition shadow-xs cursor-pointer">
                        <i class="fa-solid fa-plus text-emerald-400"></i> Add New Rate
                    </button>
                </div>

                @if(session('success'))
                    <div
                        class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-semibold flex items-center gap-2.5 shadow-3xs">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <div class="overflow-x-auto bg-white rounded-2xl border border-slate-200/60 shadow-xs">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200/60">
                            <tr>
                                <th class="p-4">Vehicle Type</th>
                                <th class="p-4">Distance Range</th>
                                <th class="p-4 text-right">Multiplier Rate</th>
                                <th class="p-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700 text-sm">
                            @forelse($rates as $rate)
                                @php
                                    $color = strtolower($rate->vehicle_type) == 'motorcycle' ? 'orange' : 'blue';
                                    $icon = strtolower($rate->vehicle_type) == 'motorcycle' ? 'motorcycle' : 'car';
                                @endphp
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-{{ $color }}-50 text-{{ $color }}-600 flex items-center justify-center border border-{{ $color }}-100 shrink-0">
                                                <i class="fa-solid fa-{{ $icon }}"></i>
                                            </div>
                                            <span class="font-bold text-slate-800">{{ $rate->vehicle_type }}</span>
                                        </div>
                                    </td>
                                    <td class="p-4 text-xs font-mono text-slate-500">
                                        {{ $rate->min_km }} KM - {{ $rate->max_km ? $rate->max_km . ' KM' : 'Max' }}
                                    </td>
                                    <td class="p-4">
                                        <form action="{{ route('manager.mileage_rates.update', $rate->id) }}" method="POST" class="flex items-center justify-end gap-2" x-data="{ loading: false }" @submit="loading = true">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="vehicle_type" value="{{ $rate->vehicle_type }}">
                                            <input type="hidden" name="min_km" value="{{ $rate->min_km }}">
                                            <input type="hidden" name="max_km" value="{{ $rate->max_km }}">
                                            
                                            <div class="relative w-28 text-xs shrink-0">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">RM</span>
                                                <input type="number" step="0.01" name="rate" value="{{ number_format($rate->rate, 2) }}" required class="w-full pl-9 pr-2 py-2 bg-slate-50 border border-slate-200 focus:border-blue-400 rounded-lg outline-none font-bold font-mono text-slate-800 text-right">
                                            </div>
                                            
                                            <button type="submit" :disabled="loading" class="px-3 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-[10px] font-bold transition-all uppercase tracking-wider shrink-0 disabled:opacity-50 flex items-center justify-center gap-1.5 cursor-pointer w-[90px]">
                                                <template x-if="loading"><i class="fa-solid fa-spinner fa-spin"></i></template>
                                                <span x-text="loading ? 'Saving' : 'Update'"></span>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="p-4 text-center">
                                        <button type="button" @click="deleteModalOpen = true; deleteActionUrl = '{{ route('manager.mileage_rates.destroy', $rate->id) }}'" class="w-8 h-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-500 flex items-center justify-center transition-colors border border-rose-100 cursor-pointer mx-auto shadow-sm">
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-12 text-center text-slate-400 font-medium">
                                        <div class="flex flex-col items-center justify-center space-y-2">
                                            <i class="fa-solid fa-folder-open text-4xl text-slate-200 mb-2"></i>
                                            <span class="text-sm text-slate-500 font-bold">No mileage rates configured</span>
                                            <span class="text-xs text-slate-400">Add a new allowance rate to proceed.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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

    <!-- Create Mileage Rate Modal -->
    <div x-show="isCreateModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 transition-opacity">
        <div class="bg-white rounded-3xl shadow-xl w-full max-w-md overflow-hidden" @click.away="isCreateModalOpen = false">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-black text-slate-800 tracking-tight">Create New Mileage Rate</h3>
                <button type="button" @click="isCreateModalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form action="{{ route('manager.mileage_rates.store') }}" method="POST" class="p-5 space-y-4" x-data="{ loading: false }" @submit="loading = true">
                @csrf
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700">Vehicle Type</label>
                    <select name="vehicle_type" required class="w-full border border-slate-200 rounded-xl bg-white px-4 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 text-sm font-medium outline-none">
                        <option value="">Select Vehicle Type</option>
                        <option value="Car">Car</option>
                        <option value="Motorcycle">Motorcycle</option>
                        <option value="Van">Van</option>
                        <option value="Lorry">Lorry</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Min KM (Optional)</label>
                        <input type="number" name="min_km" value="0" min="0" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm font-medium">
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Max KM (Optional)</label>
                        <input type="number" name="max_km" min="0" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-sm font-medium">
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700">Multiplier Rate (RM / KM)</label>
                    <div class="relative text-sm">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">RM</span>
                        <input type="number" step="0.01" name="rate" required class="w-full pl-9 pr-16 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 font-bold font-mono text-slate-800">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">/ KM</span>
                    </div>
                </div>
                <div class="pt-2">
                    <button type="submit" :disabled="loading" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all uppercase tracking-wider shadow-md cursor-pointer disabled:opacity-70 flex items-center justify-center gap-1.5">
                        <i x-show="loading" class="fa-solid fa-spinner fa-spin"></i>
                        <i x-show="!loading" class="fa-solid fa-check"></i> 
                        <span x-text="loading ? 'Creating...' : 'Create Rate'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Delete Confirmation Modal -->
    <div x-show="deleteModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 transition-opacity">
        <div class="bg-white rounded-3xl shadow-xl w-full max-w-sm overflow-hidden" @click.away="deleteModalOpen = false">
            <div class="p-6 text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto shadow-sm border border-rose-200">
                    <i class="fa-solid fa-triangle-exclamation text-2xl"></i>
                </div>
                <h3 class="text-lg font-black text-slate-800 tracking-tight">Delete Mileage Rate</h3>
                <p class="text-sm text-slate-500">Are you sure you want to delete this mileage allowance rate? This action cannot be undone.</p>
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
                        <span x-text="loading ? 'Deleting' : 'Delete Rate'"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>
