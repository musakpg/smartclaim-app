<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Fleet & Vehicle Verification Desk</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" x-data="{
        isMobileSidebarOpen: false,
        mainTab: 'staff_verifications', // Options: 'staff_verifications', 'company_fleet'
        
        // Staff Verification State
        staffSubTab: 'pending',
        reviewModalOpen: false,
        rejectModalOpen: false,
        selectedStaffVehicle: null,
        activeDoc: 'grant',

        // Company Fleet State
        editingVehicle: null,
        allVehicles: {{ json_encode(($companyVehicles ?? $vehicles ?? collect())->map(fn($v) => [
    'id' => $v->vehicle_id,
    'plate_number' => $v->plate_number,
    'model' => $v->brand_model,
    'type' => $v->vehicle_type,
    'status' => $v->status ?? 'Active',
])) }},
        vPage: 1, vPer: 5,
        get vTotal() { return this.allVehicles.length },
        get vPages() { return Math.max(1, Math.ceil(this.vTotal / this.vPer)) },
        get vPaged() { return this.allVehicles.slice((this.vPage-1)*this.vPer, this.vPage*this.vPer) },

        // Fleet Audit Logs State
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
    @include('layouts.partials.demo-banner')

    <div class="flex flex-col lg:flex-row min-h-screen">
        <!-- Reusable Manager Navigation Sidebar -->
        @include('layouts.partials.manager-sidebar')

        <!-- Main Workspace -->
        <main class="flex-1 w-full px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-6 pb-28 md:pb-8">

            <!-- Page Header -->
            <div class="border-b border-slate-200 pb-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                    <div>
                        <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Fleet & Transport
                        Governance</h1>
                    <p class="text-xs md:text-sm text-slate-500">Oversee personal vehicle compliance for mileage claims
                        and manage official Aero Art corporate fleet assets.</p>
                    </div>
                    <div class="hidden lg:flex items-center gap-3">
                        <x-system-clock />
                    </div>
                </div>

                <!-- Primary Workspace Selector Tabs -->
                <div class="flex items-center gap-2 bg-slate-200/70 p-1.5 rounded-2xl w-fit">
                    <button type="button" @click="mainTab = 'staff_verifications'"
                        :class="mainTab === 'staff_verifications' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-600 font-medium hover:text-slate-900'"
                        class="px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-user-check text-blue-600"></i> Staff Vehicle Verification Desk
                        @if(($pendingCount ?? 0) > 0)
                            <span
                                class="px-1.5 py-0.5 bg-rose-500 text-white rounded-full text-[9px] font-bold">{{ $pendingCount }}</span>
                        @endif
                    </button>
                    <button type="button" @click="mainTab = 'company_fleet'"
                        :class="mainTab === 'company_fleet' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-600 font-medium hover:text-slate-900'"
                        class="px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-car-side text-emerald-600"></i> Corporate Fleet Management
                    </button>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 1: STAFF VEHICLE VERIFICATION DESK (FASA 3 CORE)                       -->
            <!-- ========================================================================= -->
            <div x-show="mainTab === 'staff_verifications'" class="space-y-6">
                <!-- Sub-tab Filter Pills -->
                <div class="flex items-center gap-2 border-b border-slate-200 pb-3 overflow-x-auto text-xs font-bold">
                    <button type="button" @click="staffSubTab = 'pending'"
                        :class="staffSubTab === 'pending' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                        class="px-4 py-2.5 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap cursor-pointer">
                        <i class="fa-solid fa-clock-rotate-left text-amber-400"></i> Pending Verifications
                        <span class="px-2 py-0.5 rounded-full text-[10px]"
                            :class="staffSubTab === 'pending' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'">
                            {{ count($pendingStaffVehicles ?? []) }}
                        </span>
                    </button>

                    <button type="button" @click="staffSubTab = 'active'"
                        :class="staffSubTab === 'active' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                        class="px-4 py-2.5 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap cursor-pointer">
                        <i class="fa-solid fa-circle-check text-emerald-400"></i> Active Personal Registry
                        <span class="px-2 py-0.5 rounded-full text-[10px]"
                            :class="staffSubTab === 'active' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'">
                            {{ count($approvedStaffVehicles ?? []) }}
                        </span>
                    </button>

                    <button type="button" @click="staffSubTab = 'rejected'"
                        :class="staffSubTab === 'rejected' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'"
                        class="px-4 py-2.5 rounded-xl transition-all flex items-center gap-2 whitespace-nowrap cursor-pointer">
                        <i class="fa-solid fa-circle-xmark text-rose-400"></i> Rejected Applications
                        <span class="px-2 py-0.5 rounded-full text-[10px]"
                            :class="staffSubTab === 'rejected' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'">
                            {{ count($rejectedStaffVehicles ?? []) }}
                        </span>
                    </button>
                </div>

                <!-- Sub-panel 1: Pending Verifications -->
                <div x-show="staffSubTab === 'pending'" class="space-y-4">
                    <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-xs">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs min-w-[700px]">
                                <thead
                                    class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
                                    <tr>
                                        <th class="p-4">Staff Member</th>
                                        <th class="p-4">Vehicle Specification</th>
                                        <th class="p-4">Roadtax Expiration</th>
                                        <th class="p-4">Review Scope</th>
                                        <th class="p-4 text-right">Verification Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-medium">
                                    @forelse($pendingStaffVehicles ?? [] as $v)
                                        <tr class="hover:bg-slate-50/50 transition">
                                            <td class="p-4">
                                                <p class="font-bold text-slate-900">{{ $v->user->name ?? 'Unknown Staff' }}
                                                </p>
                                                <p class="text-[11px] text-slate-400 font-mono">
                                                    {{ $v->user->email ?? 'N/A' }}</p>
                                            </td>
                                            <td class="p-4">
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="font-mono font-bold text-slate-900 bg-slate-100 px-2 py-1 rounded-md">{{ $v->plate_number }}</span>
                                                    <span class="text-slate-600">{{ $v->brand_model }}
                                                        ({{ $v->engine_capacity ?? 'N/A' }}cc)</span>
                                                </div>
                                            </td>
                                            <td class="p-4 font-mono font-bold text-slate-800">
                                                {{ $v->roadtax_expiry ? \Carbon\Carbon::parse($v->roadtax_expiry)->format('d M Y') : 'N/A' }}
                                            </td>
                                            <td class="p-4">
                                                @if(($v->roadtax_renewal_status ?? '') === 'Pending_Review')
                                                    <span
                                                        class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-lg text-[10px] font-bold">
                                                        Roadtax Renewal
                                                    </span>
                                                @else
                                                    <span
                                                        class="px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded-lg text-[10px] font-bold">
                                                        New Registration
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="p-4 text-right">
                                                <button type="button"
                                                    @click="selectedStaffVehicle = {{ json_encode($v->load('user')) }}; reviewModalOpen = true; activeDoc = 'grant';"
                                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition shadow-xs cursor-pointer">
                                                    Review Documents
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="p-8 text-center text-slate-400">
                                                <i class="fa-solid fa-circle-check block text-2xl mb-2 text-slate-300"></i>
                                                All personal vehicle submissions are verified. No pending tasks.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sub-panel 2: Active Registry -->
                <div x-show="staffSubTab === 'active'" x-cloak class="space-y-4">
                    <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-xs">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs min-w-[700px]">
                                <thead
                                    class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
                                    <tr>
                                        <th class="p-4">Staff Owner</th>
                                        <th class="p-4">Plate & Model</th>
                                        <th class="p-4">Roadtax Status</th>
                                        <th class="p-4">Verified By</th>
                                        <th class="p-4 text-right">Approval Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-medium">
                                    @forelse($approvedStaffVehicles ?? [] as $v)
                                        <tr class="hover:bg-slate-50/50 transition">
                                            <td class="p-4 font-bold text-slate-900">{{ $v->user->name ?? 'Staff' }}</td>
                                            <td class="p-4 font-mono font-bold text-slate-800">
                                                {{ $v->plate_number }} <span
                                                    class="font-sans font-normal text-slate-500">({{ $v->brand_model }})</span>
                                            </td>
                                            <td class="p-4">
                                                @if($v->is_expired)
                                                    <span
                                                        class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">EXPIRED</span>
                                                @elseif($v->is_expiring_soon)
                                                    <span
                                                        class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700">EXPIRING
                                                        SOON</span>
                                                @else
                                                    <span
                                                        class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">VALID</span>
                                                @endif
                                                <span
                                                    class="text-slate-500 ml-1 font-mono">({{ $v->roadtax_expiry ? \Carbon\Carbon::parse($v->roadtax_expiry)->format('d/m/Y') : '-' }})</span>
                                            </td>
                                            <td class="p-4 text-slate-600">{{ $v->approver->name ?? 'Management Desk' }}
                                            </td>
                                            <td class="p-4 text-right text-slate-400 font-mono">
                                                {{ $v->approved_at ? \Carbon\Carbon::parse($v->approved_at)->format('d M Y') : '-' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="p-8 text-center text-slate-400">No active personal
                                                vehicles registered in the database.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sub-panel 3: Rejected Applications -->
                <div x-show="staffSubTab === 'rejected'" x-cloak class="space-y-4">
                    <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-xs">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs min-w-[700px]">
                                <thead
                                    class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
                                    <tr>
                                        <th class="p-4">Staff Owner</th>
                                        <th class="p-4">Plate Number</th>
                                        <th class="p-4">Rejection Justification</th>
                                        <th class="p-4 text-right">Audit Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-medium">
                                    @forelse($rejectedStaffVehicles ?? [] as $v)
                                        <tr class="hover:bg-slate-50/50 transition">
                                            <td class="p-4 font-bold text-slate-900">{{ $v->user->name ?? 'Staff' }}</td>
                                            <td class="p-4 font-mono font-bold text-slate-800">{{ $v->plate_number }}</td>
                                            <td class="p-4 text-rose-600 max-w-sm leading-relaxed">
                                                {{ $v->rejection_reason }}</td>
                                            <td class="p-4 text-right text-slate-400 font-mono">
                                                {{ $v->updated_at->format('d M Y') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="p-8 text-center text-slate-400">No rejected applications
                                                on record.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 2: COMPANY FLEET MANAGEMENT (EXISTING CRUD & LOCAL AUDIT LOGS)        -->
            <!-- ========================================================================= -->
            <div x-show="mainTab === 'company_fleet'" x-cloak class="space-y-6">
                <!-- Fleet CRUD Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

                    <!-- Left: Fleet Register / Edit Form -->
                    <div
                        class="bg-white p-4 md:p-5 rounded-3xl border border-slate-200/60 shadow-xs space-y-4 lg:col-span-1">
                        <div class="border-b border-slate-100 pb-3">
                            <h3 class="text-xs md:text-sm font-black text-slate-800 flex items-center gap-1.5">
                                <i class="fa-solid fa-square-plus text-emerald-500" x-show="!editingVehicle"></i>
                                <i class="fa-solid fa-pen-to-square text-blue-500" x-show="editingVehicle" x-cloak></i>
                                <span
                                    x-text="editingVehicle ? 'Edit Asset Configuration' : 'Register Corporate Vehicle'"></span>
                            </h3>
                        </div>

                        <form
                            :action="editingVehicle ? '/manager/vehicles/' + editingVehicle.id : '{{ route('manager.vehicles.store') }}'"
                            method="POST" class="space-y-4">
                            @csrf
                            <template x-if="editingVehicle">
                                <input type="hidden" name="_method" value="PUT">
                            </template>

                            <div class="space-y-1">
                                <label
                                    class="block text-[10px] md:text-[11px] font-bold text-slate-600 uppercase tracking-wide">Vehicle
                                    Plate Number *</label>
                                <input type="text" name="plate_number" placeholder="e.g. WRA2003" required
                                    :disabled="editingVehicle"
                                    :value="editingVehicle ? editingVehicle.plate_number : '{{ old('plate_number') }}'"
                                    @input="$event.target.value = $event.target.value.toUpperCase();"
                                    class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-bold font-mono text-slate-800 focus:border-slate-900 disabled:opacity-60 disabled:cursor-not-allowed uppercase tracking-wider">
                            </div>

                            <div class="space-y-1">
                                <label
                                    class="block text-[10px] md:text-[11px] font-bold text-slate-600 uppercase tracking-wide">Brand
                                    & Model Variant *</label>
                                <input type="text" name="model" placeholder="e.g. VOLVO S60" required
                                    :value="editingVehicle ? editingVehicle.model : '{{ old('model') }}'"
                                    @input="$event.target.value = $event.target.value.toUpperCase();"
                                    class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-semibold text-slate-800 focus:border-slate-900 uppercase">
                            </div>

                            <div class="space-y-1">
                                <label
                                    class="block text-[10px] md:text-[11px] font-bold text-slate-600 uppercase tracking-wide">Logistics
                                    Classification *</label>
                                <select name="type" required
                                    :value="editingVehicle ? editingVehicle.type : '{{ old('type', 'Car') }}'"
                                    class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none text-xs font-semibold text-slate-800 focus:border-slate-900">
                                    <option value="Car">Car</option>
                                    <option value="Motorcycle">Motorcycle</option>
                                </select>
                            </div>

                            <div class="space-y-1" x-show="editingVehicle" x-cloak x-transition>
                                <label
                                    class="block text-[10px] md:text-[11px] font-bold text-slate-600 uppercase tracking-wide">Operational
                                    Lifecycle Status *</label>
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

                    <!-- Right: Authorized Fleet Register -->
                    <div
                        class="bg-white rounded-3xl border border-slate-200/60 shadow-xs lg:col-span-2 overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                            <h3 class="text-xs md:text-sm font-bold text-slate-800">
                                <i class="fa-solid fa-list-check text-blue-500 mr-1"></i> Corporate Fleet Register
                            </h3>
                            <span class="px-2.5 py-1 bg-slate-100 text-slate-700 font-bold text-[10px] rounded-lg"
                                x-text="vTotal + ' Assets Logged'"></span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs min-w-[480px]">
                                <thead>
                                    <tr
                                        class="border-b border-slate-100 text-slate-400 font-bold tracking-wide uppercase bg-slate-50/50">
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
                                                    <span><i class="fa-solid fa-motorcycle text-orange-500 mr-1"></i>
                                                        Motorcycle</span>
                                                </template>
                                            </td>
                                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                                <span
                                                    class="px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase tracking-wide"
                                                    :class="vehicle.status === 'Active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-400 border border-slate-200'"
                                                    x-text="vehicle.status"></span>
                                            </td>
                                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                                <div class="flex items-center justify-end gap-2">
                                                    <button type="button"
                                                        @click="editingVehicle = { id: vehicle.id, plate_number: vehicle.plate_number, model: vehicle.model, type: vehicle.type, status: vehicle.status }"
                                                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-all cursor-pointer">
                                                        <i class="fa-solid fa-pen-to-square text-sm"></i>
                                                    </button>
                                                    <form :action="'/manager/vehicles/' + vehicle.id" method="POST"
                                                        class="inline-block"
                                                        @submit.prevent="
                                                            Swal.fire({
                                                                title: 'Are you sure?',
                                                                text: 'Purge corporate vehicle ' + vehicle.plate_number + ' from the database?',
                                                                icon: 'warning',
                                                                showCancelButton: true,
                                                                confirmButtonColor: '#e11d48',
                                                                cancelButtonColor: '#64748b',
                                                                confirmButtonText: 'Yes, purge asset!'
                                                            }).then((result) => { if (result.isConfirmed) { $el.submit(); } })">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-all cursor-pointer">
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
                                                No corporate transport assets found.
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Fleet Pagination -->
                        <div x-show="vPages > 1"
                            class="px-5 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-bold">
                            <div class="text-slate-400 font-medium">
                                Showing <span class="text-slate-700"
                                    x-text="vTotal === 0 ? 0 : ((vPage-1)*vPer)+1"></span>
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

                <!-- Local Fleet Audit Logs -->
                <div class="bg-white rounded-3xl border border-slate-200/60 shadow-xs overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                        <div class="space-y-0.5">
                            <h3 class="text-xs md:text-sm font-bold text-slate-800 flex items-center gap-1.5">
                                <i class="fa-solid fa-scroll text-emerald-500"></i> Corporate Fleet Audit Trail
                            </h3>
                            <p class="text-[10px] text-slate-400 font-medium">Transaction activity logging corporate
                                fleet modification events.</p>
                        </div>
                        <span
                            class="px-2.5 py-1 bg-slate-900 text-slate-200 font-bold text-[10px] uppercase font-mono tracking-wider rounded-lg flex items-center gap-1 shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Track Stream
                            Connected
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs min-w-[750px]">
                            <thead>
                                <tr
                                    class="border-b border-slate-100 text-slate-400 font-bold tracking-wide uppercase bg-slate-50/50">
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
                                            <span
                                                class="px-2 py-0.5 rounded font-bold text-[9px] border uppercase tracking-wider"
                                                :class="{
                                                    'bg-emerald-50 text-emerald-700 border-emerald-100': log.action_event === 'VEHICLE_CREATE',
                                                    'bg-blue-50 text-blue-700 border-blue-100': log.action_event === 'VEHICLE_UPDATE',
                                                    'bg-rose-50 text-rose-700 border-rose-100': log.action_event === 'VEHICLE_DELETE'
                                                }" x-text="log.action_event.replace('VEHICLE_', '')">
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
                                            No local transaction activity logged inside the fleet partition yet.
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Logs Pagination -->
                    <div x-show="lPages > 1"
                        class="px-5 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-bold">
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
            </div>

        </main>
        @include('layouts.partials.bottom-nav')
    </div>

    <!-- ========================================================================= -->
    <!-- MODALS FOR STAFF VEHICLE VERIFICATION                                     -->
    <!-- ========================================================================= -->

    <!-- Document Review Modal -->
    <div x-show="reviewModalOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50">
        <div class="bg-white rounded-3xl max-w-4xl w-full p-6 space-y-4 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
            @click.away="reviewModalOpen = false">

            <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Personal Vehicle Application Review</h3>
                    <p class="text-xs text-slate-500">Cross-verify staff submitted particulars against legal
                        documentation.</p>
                </div>
                <button @click="reviewModalOpen = false"
                    class="text-slate-400 hover:text-slate-700 text-lg cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <template x-if="selectedStaffVehicle">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 flex-1 overflow-y-auto pr-1">
                    <!-- Left: Metadata Checklist -->
                    <div class="space-y-4 text-xs">
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-2.5">
                            <h4 class="font-bold text-slate-800 uppercase tracking-wider text-[10px]">Submitted
                                Particulars</h4>
                            <div class="flex justify-between py-1 border-b border-slate-200/60">
                                <span class="text-slate-500">Staff Applicant:</span>
                                <span class="font-bold text-slate-800" x-text="selectedStaffVehicle.user?.name"></span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-200/60">
                                <span class="text-slate-500">Plate Number:</span>
                                <span class="font-mono font-bold text-slate-900"
                                    x-text="selectedStaffVehicle.plate_number"></span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-200/60">
                                <span class="text-slate-500">Classification / Model:</span>
                                <span class="font-bold text-slate-800"
                                    x-text="selectedStaffVehicle.vehicle_type + ' - ' + selectedStaffVehicle.brand_model"></span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-slate-200/60">
                                <span class="text-slate-500">Engine Capacity:</span>
                                <span class="font-mono font-bold text-slate-800"
                                    x-text="selectedStaffVehicle.engine_capacity ? selectedStaffVehicle.engine_capacity + ' cc' : 'Standard'"></span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span class="text-slate-500">Claimed Roadtax Expiry:</span>
                                <span class="font-mono font-bold text-blue-600"
                                    x-text="selectedStaffVehicle.roadtax_expiry"></span>
                            </div>
                        </div>

                        <!-- Document Selector Switcher -->
                        <div class="space-y-2">
                            <label class="block font-bold text-slate-700">Verification Document Selection:</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" @click="activeDoc = 'grant'"
                                    :class="activeDoc === 'grant' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'"
                                    class="py-2 px-3 rounded-xl font-bold transition flex items-center justify-center gap-1.5 cursor-pointer">
                                    <i class="fa-solid fa-file-invoice"></i> Vehicle Grant
                                </button>
                                <button type="button" @click="activeDoc = 'roadtax'"
                                    :class="activeDoc === 'roadtax' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'"
                                    class="py-2 px-3 rounded-xl font-bold transition flex items-center justify-center gap-1.5 cursor-pointer">
                                    <i class="fa-solid fa-certificate"></i> MyJPJ Roadtax
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Document Viewer -->
                    <div
                        class="bg-slate-100 border border-slate-200 rounded-2xl flex items-center justify-center min-h-[250px] p-2 overflow-hidden">
                        <template x-if="activeDoc === 'grant' && selectedStaffVehicle.grant_document_path">
                            <img :src="'/files/' + selectedStaffVehicle.grant_document_path"
                                class="max-h-[350px] max-w-full object-contain rounded-xl shadow-xs">
                        </template>
                        <template x-if="activeDoc === 'roadtax' && selectedStaffVehicle.roadtax_document_path">
                            <img :src="'/files/' + selectedStaffVehicle.roadtax_document_path"
                                class="max-h-[350px] max-w-full object-contain rounded-xl shadow-xs">
                        </template>
                        <template
                            x-if="(activeDoc === 'grant' && !selectedStaffVehicle.grant_document_path) || (activeDoc === 'roadtax' && !selectedStaffVehicle.roadtax_document_path)">
                            <div class="text-center text-slate-400 p-4">
                                <i class="fa-solid fa-file-circle-xmark text-2xl mb-1"></i>
                                <p class="text-xs font-semibold">Document asset not attached.</p>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Actions Footer -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" @click="rejectModalOpen = true"
                    class="px-4 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-bold rounded-xl transition cursor-pointer">
                    Reject Application
                </button>

                <template x-if="selectedStaffVehicle">
                    <form :action="'/manager/vehicles/' + selectedStaffVehicle.vehicle_id + '/approve'" method="POST">
                        @csrf
                        <button type="submit"
                            class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition shadow-xs cursor-pointer">
                            Approve Vehicle
                        </button>
                    </form>
                </template>
            </div>
        </div>
    </div>

    <!-- Rejection Justification Modal -->
    <div x-show="rejectModalOpen" x-cloak
        class="fixed inset-0 z-60 flex items-center justify-center p-4 bg-slate-900/50">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl"
            @click.away="rejectModalOpen = false">
            <div class="flex justify-between items-center pb-2 border-b border-slate-100">
                <h3 class="text-sm font-bold text-rose-600 flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-exclamation"></i> Rejection Justification
                </h3>
                <button @click="rejectModalOpen = false" class="text-slate-400 hover:text-slate-700 cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <template x-if="selectedStaffVehicle">
                <form :action="'/manager/vehicles/' + selectedStaffVehicle.vehicle_id + '/reject'" method="POST"
                    class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">State Reason for Rejection *</label>
                        <textarea name="rejection_reason" rows="4" required
                            placeholder="e.g. MyJPJ screenshot is illegible / Roadtax expiry date does not correspond to uploaded certificate."
                            class="w-full p-3 border border-slate-200 rounded-xl outline-none focus:border-rose-400"></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="rejectModalOpen = false"
                            class="px-4 py-2 bg-slate-100 font-bold rounded-xl text-slate-600 cursor-pointer">Cancel</button>
                        <button type="submit"
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl cursor-pointer">Confirm
                            Rejection</button>
                    </div>
                </form>
            </template>
        </div>
    </div>

    <!-- SweetAlert2 Trigger Notifications -->
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
