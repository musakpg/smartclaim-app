<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - My Registered Vehicles</title>
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
        renewModalOpen: false, 
        selectedVehicle: null, 
        previewModalOpen: false, 
        previewImage: '' 
    }">

    <div class="flex flex-col lg:flex-row min-h-screen">
        <!-- Reusable Sidebar Partial (Mobile & Desktop) -->
        @include('layouts.partials.staff-sidebar')

        <!-- Main Content Area -->
        <main class="flex-1 flex flex-col min-w-0 overflow-hidden">


            <!-- Page Body Content -->
            <div class="flex-1 w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6 pb-24 overflow-y-auto">
                <!-- Page Title & Header Actions -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Personal Vehicle
                            Registry</h1>
                        <p class="text-xs md:text-sm text-slate-500">Manage registered personal vehicles and roadtax
                            compliance for mileage allowance claims.</p>
                    </div>
                    <a href="{{ route('vehicles.create') }}"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-xs">
                        <i class="fa-solid fa-plus"></i> Register New Vehicle
                    </a>
                </div>

                <!-- Session Flash Messages -->
                @if(session('success'))
                    <div
                        class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-2xl flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if($errors->any())
                    <div
                        class="p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold rounded-2xl space-y-1">
                        @foreach($errors->all() as $error)
                            <p class="flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-rose-500"></i>
                                {{ $error }}
                            </p>
                        @endforeach
                    </div>
                @endif

                <!-- Vehicle Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    @forelse($vehicles as $vehicle)
                        <div
                            class="bg-white border border-slate-200 rounded-3xl p-5 shadow-xs space-y-4 relative flex flex-col justify-between">
                            <div class="space-y-3">
                                <!-- Top Status Badges -->
                                <div class="flex items-center justify-between">
                                    <span
                                        class="px-2.5 py-1 rounded-lg text-[10px] font-bold font-mono tracking-wider uppercase {{ $vehicle->vehicle_type === 'Car' ? 'bg-indigo-50 text-indigo-600 border border-indigo-100' : 'bg-amber-50 text-amber-600 border border-amber-100' }}">
                                        <i
                                            class="fa-solid {{ $vehicle->vehicle_type === 'Car' ? 'fa-car-side' : 'fa-motorcycle' }} mr-1"></i>
                                        {{ $vehicle->vehicle_type }}
                                    </span>

                                    <!-- Manager Approval Status Indicator -->
                                    @if($vehicle->approval_status === 'Approved')
                                        <span
                                            class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1">
                                            <i class="fa-solid fa-circle-check"></i> Approved
                                        </span>
                                    @elseif($vehicle->approval_status === 'Pending')
                                        <span
                                            class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 flex items-center gap-1">
                                            <i class="fa-solid fa-clock animate-spin"></i> Pending Verification
                                        </span>
                                    @else
                                        <span
                                            class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 flex items-center gap-1">
                                            <i class="fa-solid fa-circle-xmark"></i> Rejected
                                        </span>
                                    @endif
                                </div>

                                <!-- Plate Number and Specification Details -->
                                <div>
                                    <h3 class="text-lg font-black text-slate-900 font-mono tracking-tight">
                                        {{ $vehicle->plate_number }}
                                    </h3>
                                    <p class="text-xs font-semibold text-slate-500">{{ $vehicle->brand_model }}
                                        ({{ $vehicle->engine_capacity ? $vehicle->engine_capacity . ' cc' : 'Standard' }})
                                    </p>
                                </div>

                                <!-- Roadtax Expiry Status Card -->
                                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100 space-y-1 text-xs">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Roadtax
                                            Expiry</span>
                                        @if($vehicle->is_expired)
                                            <span
                                                class="px-2 py-0.5 rounded text-[9px] font-bold bg-rose-100 text-rose-700">EXPIRED</span>
                                        @elseif($vehicle->is_expiring_soon)
                                            <span
                                                class="px-2 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-700 animate-pulse">EXPIRING
                                                SOON</span>
                                        @else
                                            <span
                                                class="px-2 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-700">VALID</span>
                                        @endif
                                    </div>
                                    <p class="font-mono font-bold text-slate-800">
                                        {{ $vehicle->roadtax_expiry ? $vehicle->roadtax_expiry->format('d M Y') : 'N/A' }}
                                    </p>
                                </div>

                                <!-- Rejection Reason Box (Visible Only When Rejected by Manager) -->
                                @if($vehicle->approval_status === 'Rejected' && $vehicle->rejection_reason)
                                    <div
                                        class="p-3 bg-rose-50/70 border border-rose-200 rounded-2xl text-[11px] text-rose-800 space-y-1">
                                        <p class="font-bold flex items-center gap-1">
                                            <i class="fa-solid fa-triangle-exclamation"></i> Rejection Reason:
                                        </p>
                                        <p class="text-rose-700 leading-relaxed">{{ $vehicle->rejection_reason }}</p>
                                    </div>
                                @endif

                                <!-- Document Preview Action Buttons -->
                                <div class="flex items-center gap-2 pt-1 text-xs">
                                    @if($vehicle->grant_document_path)
                                        <button type="button"
                                            @click="previewImage = '{{ asset('storage/' . $vehicle->grant_document_path) }}'; previewModalOpen = true;"
                                            class="flex-1 py-1.5 px-2 bg-white hover:bg-slate-100 border border-slate-200 rounded-xl font-bold text-[10px] text-slate-600 transition-all flex items-center justify-center gap-1">
                                            <i class="fa-solid fa-file-lines text-slate-400"></i> Grant Proof
                                        </button>
                                    @endif
                                    @if($vehicle->roadtax_document_path)
                                        <button type="button"
                                            @click="previewImage = '{{ asset('storage/' . $vehicle->roadtax_document_path) }}'; previewModalOpen = true;"
                                            class="flex-1 py-1.5 px-2 bg-white hover:bg-slate-100 border border-slate-200 rounded-xl font-bold text-[10px] text-slate-600 transition-all flex items-center justify-center gap-1">
                                            <i class="fa-solid fa-certificate text-slate-400"></i> MyJPJ Proof
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Dynamic Action Footer Based on Approval and Expiry Status -->
                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                                @if($vehicle->can_be_edited)
                                    <a href="{{ route('vehicles.edit', $vehicle->vehicle_id) }}"
                                        class="flex-1 py-2 text-center bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                                        {{ $vehicle->approval_status === 'Rejected' ? 'Edit & Resubmit' : 'Edit' }}
                                    </a>
                                    <form action="{{ route('vehicles.destroy', $vehicle->vehicle_id) }}" method="POST"
                                        onsubmit="return confirm('Are you sure you want to cancel this vehicle registration?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-bold rounded-xl transition-all">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                @elseif($vehicle->approval_status === 'Approved' && ($vehicle->is_expiring_soon || $vehicle->is_expired))
                                    <button type="button"
                                        @click="selectedVehicle = {{ json_encode($vehicle) }}; renewModalOpen = true;"
                                        class="w-full py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-xs flex items-center justify-center gap-1.5">
                                        <i class="fa-solid fa-arrows-rotate"></i> Update Roadtax Proof
                                    </button>
                                @else
                                    <span class="text-[11px] font-semibold text-slate-400 flex items-center gap-1">
                                        <i class="fa-solid fa-lock text-slate-300"></i> Verified & Active
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full bg-white border border-slate-200 rounded-3xl p-12 text-center space-y-3">
                            <div
                                class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto text-slate-400 text-xl">
                                <i class="fa-solid fa-car"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-800">No Personal Vehicles Registered</h3>
                            <p class="text-xs text-slate-500 max-w-sm mx-auto">Register your personal car or motorcycle with
                                MyJPJ ownership proofs to claim mileage allowance.</p>
                            <a href="{{ route('vehicles.create') }}"
                                class="inline-block mt-2 px-4 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl uppercase tracking-wider">Register
                                Now</a>
                        </div>
                    @endforelse
                </div>
            </div>
        </main>
    </div>

    <!-- Image Verification Preview Modal -->
    <div x-show="previewModalOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl p-4 max-w-2xl w-full space-y-3 shadow-2xl"
            @click.away="previewModalOpen = false">
            <div class="flex justify-between items-center pb-2 border-b border-slate-100">
                <span class="text-xs font-bold text-slate-600 uppercase tracking-wide">Document Verification
                    Asset</span>
                <button @click="previewModalOpen = false" class="text-slate-400 hover:text-rose-600 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="max-h-[75vh] overflow-y-auto flex items-center justify-center bg-slate-50 rounded-2xl p-2">
                <img :src="previewImage" class="max-w-full max-h-[70vh] object-contain rounded-xl">
            </div>
        </div>
    </div>

    <!-- Roadtax Renewal Submission Modal -->
    <div x-show="renewModalOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl p-6 max-w-md w-full space-y-4 shadow-2xl" @click.away="renewModalOpen = false">
            <div class="flex justify-between items-center pb-2 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900">Renew Roadtax Certificate</h3>
                <button @click="renewModalOpen = false" class="text-slate-400 hover:text-rose-600 text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <template x-if="selectedVehicle">
                <form :action="'/vehicles/' + selectedVehicle.vehicle_id + '/renew'" method="POST"
                    enctype="multipart/form-data" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">New Roadtax Expiration Date *</label>
                        <input type="date" name="new_roadtax_expiry" required
                            class="w-full p-2.5 border border-slate-200 rounded-xl outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">New MyJPJ Digital Roadtax Screenshot
                            *</label>
                        <input type="file" name="new_roadtax_document" accept="image/*" required
                            class="w-full p-2 border border-slate-200 rounded-xl">
                    </div>
                    <button type="submit"
                        class="w-full py-2.5 bg-blue-600 text-white font-bold rounded-xl uppercase tracking-wider">
                        Submit Renewal Proof
                    </button>
                </form>
            </template>
        </div>
    </div>

</body>

</html>
