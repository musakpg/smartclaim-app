<!DOCTYPE html>
<html lang="en" x-data="{
    isMobileSidebarOpen: false,
    isAuditingOpen: false,
    isAdminOpen: true,
    activeSubTab: 'vehicles',
    editingVehicle: null,

    allVehicles: {{ json_encode($vehicles->map(fn($v) => [
    'id' => $v->vehicle_id,
    'plate_number' => $v->plate_number,
    'model' => $v->brand_model,
    'type' => $v->vehicle_type,
    'status' => $v->status,
])) }},
    vPage: 1, vPer: 5,
    get vTotal() { return this.allVehicles.length },
    get vPages() { return Math.max(1, Math.ceil(this.vTotal / this.vPer)) },
    get vPaged() { return this.allVehicles.slice((this.vPage-1)*this.vPer, this.vPage*this.vPer) },

    allLogs: {{ json_encode(collect($logs ?? [])->map(fn($l) => [
    'created_at' => (string) $l->created_at,
    'operator_name' => $l->operator_name,
    'action_event' => $l->action_event,
    'plate_index' => $l->plate_index,
    'description' => $l->description,
    'ip_address' => $l->ip_address,
])) }},
    lPage: 1, lPer: 5,
    get lTotal() { return this.allLogs.length },
    get lPages() { return Math.max(1, Math.ceil(this.lTotal / this.lPer)) },
    get lPaged() { return this.allLogs.slice((this.lPage-1)*this.lPer, this.lPage*this.lPer) }
}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Company Fleet CRUD</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" :class="isMobileSidebarOpen ? 'overflow-hidden' : ''">

    <div class="flex min-h-screen flex-col lg:flex-row">

        <header
            class="lg:hidden bg-[#0f172a] px-4 py-4 flex items-center justify-between sticky top-0 z-40 shadow-sm text-slate-200">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-crown text-amber-400 text-xl"></i>
                <div>
                    <span class="font-black text-sm tracking-tight text-white block leading-tight">SmartClaim</span>
                    <span class="text-[9px] font-bold text-emerald-400 uppercase tracking-wider">Aero Art Manager</span>
                </div>
            </div>
            <button type="button" @click="isMobileSidebarOpen = true"
                class="w-9 h-9 flex items-center justify-center bg-slate-800 rounded-xl text-white cursor-pointer transition-all">
                <i class="fa-solid fa-bars text-base"></i>
            </button>
        </header>

        <div x-show="isMobileSidebarOpen" x-cloak class="lg:hidden fixed inset-0 z-50 flex" role="dialog"
            aria-modal="true">
            <div x-show="isMobileSidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"
                @click="isMobileSidebarOpen = false"></div>

            <div x-show="isMobileSidebarOpen" x-transition:enter="transition ease-in-out duration-300 transform"
                x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in-out duration-300 transform"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                class="relative flex w-full max-w-xs flex-1 flex-col bg-[#0f172a] pt-5 pb-4 text-slate-200">
                <div class="absolute top-4 right-4">
                    <button type="button" @click="isMobileSidebarOpen = false"
                        class="w-8 h-8 flex items-center justify-center bg-slate-800 rounded-lg text-slate-400 cursor-pointer">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="px-6 pb-4 border-b border-slate-800 flex items-center gap-2.5">
                    <i class="fa-solid fa-crown text-amber-400 text-xl"></i>
                    <div>
                        <span class="font-black text-sm tracking-tight text-white block leading-tight">SmartClaim</span>
                        <span class="text-[9px] font-bold text-emerald-400 uppercase tracking-wider">Aero Art Manager
                            Portal</span>
                    </div>
                </div>

                <nav class="mt-4 flex-1 px-4 space-y-1 overflow-y-auto"
                    x-data="{ isAuditingOpenMobile: false, isAdminOpenMobile: true }">
                    <a href="{{ route('manager.dashboard') }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white">
                        <i class="fa-solid fa-chart-pie text-base"></i> Dashboard
                    </a>

                    <div>
                        <button type="button" @click.prevent="isAuditingOpenMobile = !isAuditingOpenMobile"
                            :class="isAuditingOpenMobile ? 'text-white' : 'text-slate-400'"
                            class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-medium transition-all cursor-pointer">
                            <span class="flex items-center gap-3"><i class="fa-solid fa-shield-check text-base"
                                    :class="isAuditingOpenMobile ? 'text-emerald-400' : 'text-slate-400'"></i> Claims
                                Verification</span>
                            <i class="fa-solid text-[10px] transition-transform duration-200"
                                :class="isAuditingOpenMobile ? 'fa-chevron-down rotate-180' : 'fa-chevron-right'"></i>
                        </button>
                        <div x-show="isAuditingOpenMobile" x-cloak
                            class="pl-6 mt-1 space-y-1 py-1 bg-slate-900/40 rounded-xl border border-slate-800">
                            <a href="{{ route('manager.verification') }}?status=Pre-Approved"
                                class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white text-left flex items-center justify-between gap-1 transition-all">
                                <span class="flex items-center gap-2"><i
                                        class="fa-solid fa-hourglass-half text-[11px]"></i> Pending Review</span>
                                @if(($preApprovedCount ?? 0) > 0) <span
                                    class="px-1.5 py-0.5 bg-amber-500 text-slate-950 font-black rounded-sm text-[8px] font-mono animate-pulse">{{ $preApprovedCount }}</span>
                                @endif
                            </a>
                            <a href="{{ route('manager.verification') }}?status=Approved"
                                class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white text-left flex items-center gap-2"><i
                                    class="fa-solid fa-circle-check text-[11px]"></i> Accepted Review</a>
                        </div>
                    </div>

                    <div>
                        <button type="button" @click.prevent="isAdminOpenMobile = !isAdminOpenMobile"
                            :class="isAdminOpenMobile ? 'text-white font-semibold' : 'text-slate-400 hover:text-white'"
                            class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm transition-all cursor-pointer">
                            <span class="flex items-center gap-3">
                                <i class="fa-solid fa-sliders-file text-base"
                                    :class="isAdminOpenMobile || activeSubTab === 'vehicles' ? 'text-emerald-400' : 'text-slate-400'"></i>
                                <span>Administration</span>
                            </span>
                            <i class="fa-solid text-[10px] transition-transform duration-200"
                                :class="isAdminOpenMobile ? 'fa-chevron-down rotate-180 text-white' : 'fa-chevron-right text-slate-400'"></i>
                        </button>
                        <div x-show="isAdminOpenMobile" x-cloak
                            class="pl-6 mt-1 space-y-1 py-1 bg-slate-900/40 rounded-xl border border-slate-800 flex flex-col">
                            <a href="{{ route('manager.mileage_rates') }}"
                                :class="activeSubTab === 'mileage_rates' ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-2"><i
                                    class="fa-solid fa-car-tunnel text-[11px]"></i> Mileage Rates</a>
                            <a href="{{ route('manager.expense_categories') }}"
                                :class="activeSubTab === 'expense_categories' ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-2"><i
                                    class="fa-solid fa-folder-tree text-[11px]"></i> Expense Categories</a>
                            <a href="{{ route('manager.user_management') }}"
                                :class="activeSubTab === 'user_management' ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-2"><i
                                    class="fa-solid fa-users-gear text-[11px]"></i> User Management</a>
                            <a href="{{ route('manager.vehicles') }}"
                                :class="activeSubTab === 'vehicles' ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-2"><i
                                    class="fa-solid fa-car text-[11px]"></i> Company Fleet CRUD</a>
                            <a href="{{ route('manager.audit_logs') }}"
                                :class="activeSubTab === 'audit_logs' ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-2"><i
                                    class="fa-solid fa-scroll text-[11px]"></i> Audit Logs</a>
                        </div>
                    </div>

                    <a href="{{ route('manager.reports') }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white"><i
                            class="fa-solid fa-chart-line text-base"></i> Reports & BI Analytics</a>
                    <a href="{{ route('manager.profile') }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white"><i
                            class="fa-solid fa-user-shield text-base"></i> My Profile</a>
                    <a href="{{ route('logout') }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-rose-950/60 hover:text-rose-400"><i
                            class="fa-solid fa-door-open text-base"></i> Sign Out</a>
                </nav>
            </div>
        </div>

        <aside
            class="hidden lg:flex fixed inset-y-0 left-0 z-50 w-64 bg-[#0f172a] flex-col h-screen sticky top-0 text-slate-200">
            <div class="px-6 py-5 border-b border-slate-800 flex items-center gap-2.5">
                <i class="fa-solid fa-crown text-amber-400 text-2xl"></i>
                <div>
                    <span class="font-black text-base tracking-tight text-white block leading-tight">SmartClaim</span>
                    <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider">Aero Art Manager
                        Portal</span>
                </div>
            </div>
            <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
                <a href="{{ route('manager.dashboard') }}"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white transition-all">
                    <i class="fa-solid fa-chart-pie text-base"></i> Dashboard
                </a>

                <div>
                    <button type="button" @click.prevent="isAuditingOpen = !isAuditingOpen"
                        class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white transition-all cursor-pointer">
                        <span class="flex items-center gap-3 pointer-events-none"><i
                                class="fa-solid fa-shield-check text-base"></i> Claims Verification</span>
                        <i class="fa-solid text-[10px] transition-transform duration-200 pointer-events-none"
                            :class="isAuditingOpen ? 'fa-chevron-down rotate-180' : 'fa-chevron-right'"></i>
                    </button>
                    <div x-show="isAuditingOpen" x-cloak x-transition
                        class="pl-6 mt-1 space-y-1 py-1 bg-slate-900/40 rounded-xl border border-slate-800">
                        <a href="{{ route('manager.verification') }}?status=Pre-Approved"
                            class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white text-left flex items-center justify-between gap-1 transition-all">
                            <span class="flex items-center gap-2"><i class="fa-solid fa-hourglass-half text-[11px]"></i>
                                Pending Review</span>
                            @if(($preApprovedCount ?? 0) > 0) <span
                                class="px-1.5 py-0.5 bg-amber-500 text-slate-950 font-black rounded-sm text-[8px] font-mono animate-pulse">{{ $preApprovedCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('manager.verification') }}?status=Approved"
                            class="w-full px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white text-left flex items-center gap-2"><i
                                class="fa-solid fa-circle-check text-[11px]"></i> Accepted Review</a>
                    </div>
                </div>

                <div>
                    <button type="button" @click.prevent="isAdminOpen = !isAdminOpen"
                        :class="isAdminOpen ? 'bg-slate-900 text-white font-semibold' : 'text-slate-400 hover:bg-slate-900 hover:text-white'"
                        class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-sm transition-all cursor-pointer">
                        <span class="flex items-center gap-3 pointer-events-none">
                            <i class="fa-solid fa-sliders-file text-base"
                                :class="isAdminOpen || activeSubTab === 'vehicles' ? 'text-emerald-400' : 'text-slate-400'"></i>
                            Administration
                        </span>
                        <i class="fa-solid text-[10px] transition-transform duration-200 pointer-events-none"
                            :class="isAdminOpen ? 'fa-chevron-down rotate-180 text-white' : 'fa-chevron-right text-slate-400'"></i>
                    </button>

                    <div x-show="isAdminOpen" x-cloak x-transition
                        class="pl-6 mt-1 space-y-1 py-1 bg-slate-900/40 rounded-xl border border-slate-800 flex flex-col">
                        <a href="{{ route('manager.mileage_rates') }}"
                            :class="activeSubTab === 'mileage_rates' ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs transition-all flex items-center gap-2"><i
                                class="fa-solid fa-car-tunnel text-[11px]"></i> Mileage Rates</a>
                        <a href="{{ route('manager.expense_categories') }}"
                            :class="activeSubTab === 'expense_categories' ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs transition-all flex items-center gap-2"><i
                                class="fa-solid fa-folder-tree text-[11px]"></i> Expense Categories</a>
                        <a href="{{ route('manager.user_management') }}"
                            :class="activeSubTab === 'user_management' ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs transition-all flex items-center gap-2"><i
                                class="fa-solid fa-users-gear text-[11px]"></i> User Management</a>
                        <a href="{{ route('manager.vehicles') }}"
                            :class="activeSubTab === 'vehicles' ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs transition-all flex items-center gap-2"><i
                                class="fa-solid fa-car text-[11px]"></i> Company Fleet CRUD</a>
                        <a href="{{ route('manager.audit_logs') }}"
                            :class="activeSubTab === 'audit_logs' ? 'text-emerald-400 font-bold bg-slate-800' : 'text-slate-400 hover:text-white'"
                            class="w-full text-left px-3 py-2 rounded-lg text-xs transition-all flex items-center gap-2"><i
                                class="fa-solid fa-scroll text-[11px]"></i> Audit Logs</a>
                    </div>
                </div>

                <a href="{{ route('manager.reports') }}"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white transition-all"><i
                        class="fa-solid fa-chart-line text-base"></i> Reports & BI Analytics</a>
                <a href="{{ route('manager.profile') }}"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-slate-900 hover:text-white transition-all"><i
                        class="fa-solid fa-user-shield text-base"></i> My Profile</a>
                <a href="{{ route('logout') }}"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-400 hover:bg-rose-950/60 hover:text-rose-400 transition-all"><i
                        class="fa-solid fa-door-open text-base"></i> Sign Out</a>
            </nav>
        </aside>

        <main class="flex-1 p-4 md:p-8 max-w-5xl mx-auto w-full pb-24 lg:pb-8 overflow-hidden">
    <div class="space-y-6">

        <div class="border-b border-slate-200 pb-5">
            <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Company Fleet Management</h1>
            <p class="text-xs md:text-sm text-slate-500">Log, monitor, and configure corporate-owned transport asset nodes for Aero Art Sdn Bhd logistics workflows.</p>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-semibold flex items-center gap-2.5 shadow-3xs">
                <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-xs font-semibold space-y-1 shadow-3xs">
                @foreach ($errors->all() as $error)
                    <p class="flex items-center gap-2"><i class="fa-solid fa-circle-exclamation text-rose-500"></i> {{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- ROW 1: Register Form + Fleet Register Table --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            {{-- LEFT: Register / Edit Form --}}
            <div class="bg-white p-4 md:p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-4 lg:col-span-1">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-xs md:text-sm font-black text-slate-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-square-plus text-emerald-500" x-show="!editingVehicle"></i>
                        <i class="fa-solid fa-pen-to-square text-blue-500" x-show="editingVehicle" x-cloak></i>
                        <span x-text="editingVehicle ? 'Edit Asset Configuration' : 'Register New Asset Node'"></span>
                    </h3>
                </div>

                <form :action="editingVehicle ? '/manager/vehicles/' + editingVehicle.id : '{{ route('manager.vehicles.store') }}'"
                    method="POST" class="space-y-4">
                    @csrf
                    <template x-if="editingVehicle">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="space-y-1">
                        <label class="block text-[10px] md:text-[11px] font-bold text-slate-600 uppercase tracking-wide">Vehicle Plate Number *</label>
                        <input type="text" name="plate_number" placeholder="e.g. WRA2003" required
                            :disabled="editingVehicle"
                            :value="editingVehicle ? editingVehicle.plate_number : '{{ old('plate_number') }}'"
                            @input="$event.target.value = $event.target.value.toUpperCase();"
                            class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold font-mono text-slate-800 focus:border-slate-900 disabled:opacity-60 disabled:cursor-not-allowed uppercase tracking-wider">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-[10px] md:text-[11px] font-bold text-slate-600 uppercase tracking-wide">Brand & Model Variant *</label>
                        <input type="text" name="model" placeholder="e.g. VOLVO S60" required
                            :value="editingVehicle ? editingVehicle.model : '{{ old('model') }}'"
                            @input="$event.target.value = $event.target.value.toUpperCase();"
                            class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-semibold text-slate-800 focus:border-slate-900 uppercase">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-[10px] md:text-[11px] font-bold text-slate-600 uppercase tracking-wide">Logistics Classification *</label>
                        <select name="type" required
                            :value="editingVehicle ? editingVehicle.type : '{{ old('type', 'Car') }}'"
                            class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-semibold text-slate-800 focus:border-slate-900">
                            <option value="Car">Car</option>
                            <option value="Motorcycle">Motorcycle</option>
                        </select>
                    </div>

                    <div class="space-y-1" x-show="editingVehicle" x-cloak x-transition>
                        <label class="block text-[10px] md:text-[11px] font-bold text-slate-600 uppercase tracking-wide">Operational Lifecycle Status *</label>
                        <select name="status" :value="editingVehicle ? editingVehicle.status : 'Active'"
                            class="w-full px-3 py-2.5 bg-amber-50/50 border border-amber-200 rounded-xl outline-none text-xs font-bold text-slate-800 focus:border-slate-900">
                            <option value="Active">Active (Operational)</option>
                            <option value="Inactive">Inactive (Decommissioned / Maintenance)</option>
                        </select>
                    </div>

                    <div class="pt-2 space-y-2">
                        <button type="submit"
                            class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition-all shadow-xs uppercase tracking-wider cursor-pointer">
                            <i class="fa-solid fa-floppy-disk mr-1"></i> Commit Asset Record
                        </button>
                        <button type="button" x-show="editingVehicle" x-cloak @click="editingVehicle = null"
                            class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition-all uppercase tracking-wider cursor-pointer">
                            Cancel Modification
                        </button>
                    </div>
                </form>
            </div>

            {{-- RIGHT: Authorized Fleet Register (paginated) --}}
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-xs lg:col-span-2 overflow-hidden">

                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <h3 class="text-xs md:text-sm font-bold text-slate-800">
                        <i class="fa-solid fa-list-check text-blue-500 mr-1"></i> Authorized Fleet Register
                    </h3>
                    <span class="px-2.5 py-1 bg-slate-100 text-slate-700 font-bold text-[10px] rounded-lg">
                        {{ count($vehicles) }} {{ count($vehicles) <= 1 ? 'Asset Logged' : 'Assets Logged' }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs min-w-[480px]">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold tracking-wide uppercase bg-slate-50/50">
                                <th class="py-3 px-4 font-mono">Plate Index</th>
                                <th class="py-3 px-4">Particulars</th>
                                <th class="py-3 px-4">Class</th>
                                <th class="py-3 px-4 text-center">Lifecycle</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-slate-700 font-medium">

                            <template x-for="(vehicle, index) in vPaged" :key="vehicle.id">
                                <tr class="hover:bg-slate-50/60 transition-all">
                                    <td class="py-3.5 px-4 font-bold font-mono text-slate-900 bg-slate-50/40 uppercase tracking-wider whitespace-nowrap"
                                        x-text="vehicle.plate_number"></td>
                                    <td class="py-3.5 px-4 font-bold text-slate-800 truncate max-w-[120px]"
                                        x-text="vehicle.model"></td>
                                    <td class="py-3.5 px-4 text-slate-500 whitespace-nowrap">
                                        <template x-if="vehicle.type === 'Car'">
                                            <span><i class="fa-solid fa-car text-blue-500 mr-1"></i> Car</span>
                                        </template>
                                        <template x-if="vehicle.type !== 'Car'">
                                            <span><i class="fa-solid fa-motorcycle text-orange-500 mr-1"></i> Motorcycle</span>
                                        </template>
                                    </td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase tracking-wide"
                                            :class="vehicle.status === 'Active'
                                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                                : 'bg-slate-100 text-slate-400 border border-slate-200'"
                                            x-text="vehicle.status">
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button"
                                                @click="editingVehicle = { id: vehicle.id, plate_number: vehicle.plate_number, model: vehicle.model, type: vehicle.type, status: vehicle.status }"
                                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-all cursor-pointer">
                                                <i class="fa-solid fa-pen-to-square text-sm"></i>
                                            </button>
                                            <form :action="'/manager/vehicles/' + vehicle.id" method="POST" class="inline-block"
                                                @submit.prevent="
                                                    Swal.fire({
                                                        title: 'Are you sure?',
                                                        text: 'Permanently purge vehicle ' + vehicle.plate_number + ' from the corporate database!',
                                                        icon: 'warning',
                                                        showCancelButton: true,
                                                        confirmButtonColor: '#e11d48',
                                                        cancelButtonColor: '#64748b',
                                                        confirmButtonText: 'Yes, purge asset!',
                                                        cancelButtonText: 'Cancel',
                                                        customClass: {
                                                            title: 'text-sm font-black text-slate-800 font-sans',
                                                            htmlContainer: 'text-xs text-slate-500 font-medium font-sans',
                                                            confirmButton: 'text-xs font-bold px-4 py-2 rounded-xl uppercase tracking-wider',
                                                            cancelButton: 'text-xs font-bold px-4 py-2 rounded-xl uppercase tracking-wider'
                                                        }
                                                    }).then((result) => { if (result.isConfirmed) { $el.submit(); } })">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-all cursor-pointer">
                                                    <i class="fa-solid fa-trash-can text-sm"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <template x-if="vTotal === 0">
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-400 font-medium">
                                        <i class="fa-solid fa-car-on block text-2xl mb-2 text-slate-300"></i>
                                        No registered transport asset nodes found.
                                    </td>
                                </tr>
                            </template>

                        </tbody>
                    </table>
                </div>

                <div x-show="vPages > 1" class="px-5 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-bold">
                    <div class="text-slate-400 font-medium">
                        Showing <span class="text-slate-700" x-text="vTotal === 0 ? 0 : ((vPage-1)*vPer)+1"></span>
                        to <span class="text-slate-700" x-text="Math.min(vPage*vPer, vTotal)"></span>
                        of <span class="text-slate-700" x-text="vTotal"></span> assets
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="if(vPage>1) vPage--" :disabled="vPage===1"
                            :class="vPage===1 ? 'text-slate-300 cursor-not-allowed bg-slate-50 border-slate-100' : 'text-slate-700 hover:border-slate-400 bg-white border-slate-200 cursor-pointer'"
                            class="px-3 py-2 border rounded-xl transition-all flex items-center gap-1.5">
                            <i class="fa-solid fa-chevron-left text-[10px]"></i> Previous
                        </button>
                        <template x-for="page in vPages" :key="page">
                            <button type="button" @click="vPage = page"
                                :class="vPage===page ? 'bg-[#0f172a] text-white border-[#0f172a]' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-400 cursor-pointer'"
                                class="w-8 h-8 border rounded-xl transition-all text-xs font-bold" x-text="page"
                                x-show="vPages<=7 || page===1 || page===vPages || Math.abs(page-vPage)<=1">
                            </button>
                        </template>
                        <button type="button" @click="if(vPage<vPages) vPage++" :disabled="vPage===vPages"
                            :class="vPage===vPages ? 'text-slate-300 cursor-not-allowed bg-slate-50 border-slate-100' : 'text-slate-700 hover:border-slate-400 bg-white border-slate-200 cursor-pointer'"
                            class="px-3 py-2 border rounded-xl transition-all flex items-center gap-1.5">
                            Next <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </button>
                    </div>
                </div>

            </div>
        </div>
        {{-- END ROW 1 GRID --}}

        {{-- ROW 2: Audit Trails — full width, OUTSIDE the grid --}}
        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-xs overflow-hidden">

            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <div class="space-y-0.5">
                    <h3 class="text-xs md:text-sm font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-scroll-old text-emerald-500"></i> Local Fleet Asset Audit Trails
                    </h3>
                    <p class="text-[10px] text-slate-400 font-medium">Real-time runtime monitoring stream logging manager modification events.</p>
                </div>
                <span class="px-2.5 py-1 bg-slate-900 text-slate-200 font-bold text-[10px] uppercase font-mono tracking-wider rounded-lg flex items-center gap-1 shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Track Stream Connected
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs min-w-[750px]">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-bold tracking-wide uppercase bg-slate-50/50">
                            <th class="py-3 px-4">Date & Time</th>
                            <th class="py-3 px-4">Manager Name</th>
                            <th class="py-3 px-4">Action</th>
                            <th class="py-3 px-4 font-mono">Plate Number</th>
                            <th class="py-3 px-4">Description</th>
                            <th class="py-3 px-4 text-center">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-slate-700 font-medium font-mono">

                        <template x-for="(log, index) in lPaged" :key="index">
                            <tr class="hover:bg-slate-50/60 transition-all text-[11px]">
                                <td class="py-3.5 px-4 text-slate-500 font-sans font-semibold whitespace-nowrap"
                                    x-text="log.created_at"></td>
                                <td class="py-3.5 px-4 font-sans font-bold text-slate-900 whitespace-nowrap"
                                    x-text="log.operator_name"></td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded font-bold text-[9px] border uppercase tracking-wider"
                                        :class="{
                                            'bg-emerald-50 text-emerald-700 border-emerald-100': log.action_event === 'VEHICLE_CREATE',
                                            'bg-blue-50 text-blue-700 border-blue-100':          log.action_event === 'VEHICLE_UPDATE',
                                            'bg-rose-50 text-rose-700 border-rose-100':          log.action_event === 'VEHICLE_DELETE'
                                        }"
                                        x-text="log.action_event.replace('VEHICLE_', '')">
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-800 uppercase tracking-wide whitespace-nowrap bg-slate-50/30"
                                    x-text="log.plate_index"></td>
                                <td class="py-3.5 px-4 text-slate-500 font-sans font-medium min-w-[320px] leading-relaxed"
                                    x-text="log.description"></td>
                                <td class="py-3.5 px-4 text-center text-slate-400 font-sans whitespace-nowrap"
                                    x-text="log.ip_address"></td>
                            </tr>
                        </template>

                        <template x-if="lTotal === 0">
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400 font-sans font-medium">
                                    <i class="fa-solid fa-timeline block text-2xl mb-2 text-slate-300"></i>
                                    No local transaction activity logged inside the system fleet partition yet.
                                </td>
                            </tr>
                        </template>

                    </tbody>
                </table>
            </div>

            <div x-show="lPages > 1" class="px-5 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-bold">
                <div class="text-slate-400 font-medium">
                    Showing <span class="text-slate-700" x-text="lTotal === 0 ? 0 : ((lPage-1)*lPer)+1"></span>
                    to <span class="text-slate-700" x-text="Math.min(lPage*lPer, lTotal)"></span>
                    of <span class="text-slate-700" x-text="lTotal"></span> log entries
                </div>
                <div class="flex items-center gap-1.5">
                    <button type="button" @click="if(lPage>1) lPage--" :disabled="lPage===1"
                        :class="lPage===1 ? 'text-slate-300 cursor-not-allowed bg-slate-50 border-slate-100' : 'text-slate-700 hover:border-slate-400 bg-white border-slate-200 cursor-pointer'"
                        class="px-3 py-2 border rounded-xl transition-all flex items-center gap-1.5">
                        <i class="fa-solid fa-chevron-left text-[10px]"></i> Previous
                    </button>
                    <template x-for="page in lPages" :key="page">
                        <button type="button" @click="lPage = page"
                            :class="lPage===page ? 'bg-[#0f172a] text-white border-[#0f172a]' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-400 cursor-pointer'"
                            class="w-8 h-8 border rounded-xl transition-all text-xs font-bold" x-text="page"
                            x-show="lPages<=7 || page===1 || page===lPages || Math.abs(page-lPage)<=1">
                        </button>
                    </template>
                    <button type="button" @click="if(lPage<lPages) lPage++" :disabled="lPage===lPages"
                        :class="lPage===lPages ? 'text-slate-300 cursor-not-allowed bg-slate-50 border-slate-100' : 'text-slate-700 hover:border-slate-400 bg-white border-slate-200 cursor-pointer'"
                        class="px-3 py-2 border rounded-xl transition-all flex items-center gap-1.5">
                        Next <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </button>
                </div>
            </div>

        </div>
        {{-- END ROW 2 --}}

    </div>
</main>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            @if(session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Operation Successful',
                    text: "{{ session('success') }}",
                    confirmButtonColor: '#0f172a'
                });
            @endif

                @if($errors->any())
                    let errorLog = "";
                    @foreach($errors->all() as $error)
                        errorLog += "• {{ $error }}\n";
                    @endforeach

                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Discrepancy Detected',
                        text: errorLog,
                        confirmButtonColor: '#e11d48',
                        customClass: {
                            htmlContainer: 'text-left font-sans text-xs whitespace-pre-line'
                        }
                    });
                @endif
        });
    </script>
</body>

</html>