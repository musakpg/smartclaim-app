<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Edit Vehicle Registration</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" x-data="vehicleForm()">
    @include('layouts.partials.demo-banner')


    <div class="flex flex-col lg:flex-row min-h-screen">
        <!-- Reusable Sidebar Partial (Mobile & Desktop) -->
        @include('layouts.partials.staff-sidebar')

        <!-- Main Content Area -->
        <main class="flex-1 flex flex-col min-w-0">


            <!-- Page Body Content -->
            <div class="flex-1 w-full px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-6 pb-28 md:pb-8">
                <!-- Page Title & Navigation Back Button -->
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-bold text-slate-900 tracking-tight">Edit Vehicle Application</h1>
                        <p class="text-xs text-slate-500">Update vehicle details or resubmit corrected documents.</p>
                    </div>
                    <a href="{{ route('vehicles.index') }}"
                        class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition-all">
                        <i class="fa-solid fa-arrow-left mr-1"></i> Back
                    </a>
                </div>

                <!-- Update Vehicle Form -->
                <div class="w-full bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8 space-y-6">
                    <form action="{{ route('vehicles.update', $vehicle->vehicle_id) }}" method="POST"
                        enctype="multipart/form-data" class="space-y-5 text-xs">
                        @csrf
                        @method('PUT')

                        <!-- Vehicle Specification Input Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="block font-bold text-slate-700">Vehicle Classification *</label>
                                <select name="vehicle_type" required
                                    class="w-full p-2.5 bg-white border border-slate-200 rounded-xl outline-none font-bold">
                                    <option value="Car" {{ $vehicle->vehicle_type === 'Car' ? 'selected' : '' }}>Car
                                    </option>
                                    <option value="Motorcycle" {{ $vehicle->vehicle_type === 'Motorcycle' ? 'selected' : '' }}>Motorcycle</option>
                                </select>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block font-bold text-slate-700">Plate Registration Number *</label>
                                <input type="text" name="plate_number"
                                    value="{{ old('plate_number', $vehicle->plate_number) }}" required
                                    class="w-full p-2.5 border border-slate-200 rounded-xl outline-none font-mono uppercase font-bold">
                            </div>

                            <div class="space-y-1.5">
                                <label class="block font-bold text-slate-700">Brand & Model *</label>
                                <input type="text" name="brand_model"
                                    value="{{ old('brand_model', $vehicle->brand_model) }}" required
                                    class="w-full p-2.5 border border-slate-200 rounded-xl outline-none">
                            </div>

                            <div class="space-y-1.5">
                                <label class="block font-bold text-slate-700">Engine Capacity (cc)</label>
                                <input type="text" id="engine_capacity" name="engine_capacity"
                                    value="{{ old('engine_capacity', $vehicle->engine_capacity) }}"
                                    class="w-full p-2.5 border border-slate-200 rounded-xl outline-none font-mono">
                            </div>

                            <div class="space-y-1.5 md:col-span-2">
                                <label class="block font-bold text-slate-700">Roadtax Expiration Date *</label>
                                <input type="date" name="roadtax_expiry"
                                    value="{{ old('roadtax_expiry', $vehicle->roadtax_expiry ? $vehicle->roadtax_expiry->format('Y-m-d') : '') }}"
                                    required
                                    class="w-full p-2.5 border border-slate-200 rounded-xl outline-none font-mono font-bold">
                            </div>
                        </div>

                        <!-- Optional Document Replacement Section -->
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-4">
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                                Replace Verification Proofs (Leave empty to keep current)
                            </h3>

                            <div class="space-y-1.5">
                                <label class="block font-bold text-slate-700">1. Update Vehicle Grant / Ownership
                                    Proof</label>
                                <input type="file" name="grant_document" accept="image/*"
                                    class="w-full p-2 bg-white border border-slate-200 rounded-xl">
                            </div>

                            <div class="space-y-1.5">
                                <label class="block font-bold text-slate-700">2. Update MyJPJ Active Roadtax
                                    Screenshot</label>
                                <input type="file" name="roadtax_document" accept="image/*"
                                    class="w-full p-2 bg-white border border-slate-200 rounded-xl">
                            </div>
                        </div>

                        <!-- Form Submission Button -->
                        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                            <button type="submit"
                                class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold uppercase tracking-wider rounded-xl transition-all shadow-xs">
                                Save & Resubmit Application
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/imask"></script>
    <script>
        function vehicleForm() {
            return {
                isMobileSidebarOpen: false,
                engineMask: null,
                init() {
                    const engineInput = document.getElementById('engine_capacity');
                    if (engineInput) {
                        this.engineMask = IMask(engineInput, {
                            mask: Number,
                            scale: 0,
                            signed: false,
                            thousandsSeparator: ''
                        });
                    }
                }
            };
        }
    </script>
    @include('layouts.partials.bottom-nav')
</body>

</html>
