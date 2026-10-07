<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Register Vehicle</title>
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

    <div class="flex flex-col lg:flex-row min-h-screen">
        <!-- Reusable Sidebar Partial (Mobile & Desktop) -->
        @include('layouts.partials.staff-sidebar')

        <!-- Main Content Area -->
        <main class="flex-1 flex flex-col min-w-0 overflow-hidden pb-24 md:pb-8">


            <!-- Page Body Content -->
            <div class="flex-1 p-4 md:p-8 max-w-2xl mx-auto w-full pb-24 md:pb-8 overflow-y-auto space-y-6">
                <!-- Page Title & Navigation Back Button -->
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-bold text-slate-900 tracking-tight">Register Personal Vehicle</h1>
                        <p class="text-xs text-slate-500">Provide vehicle specifications and verification documents.</p>
                    </div>
                    <a href="{{ route('vehicles.index') }}"
                        class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition-all">
                        <i class="fa-solid fa-arrow-left mr-1"></i> Back
                    </a>
                </div>

                <!-- Validation Errors Display -->
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

                <!-- Vehicle Registration Form -->
                <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs">
                    <form action="{{ route('vehicles.store') }}" method="POST" enctype="multipart/form-data"
                        class="space-y-5 text-xs">
                        @csrf

                        <!-- Vehicle Specification Input Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="block font-bold text-slate-700">Vehicle Classification *</label>
                                <select name="vehicle_type" required
                                    class="w-full p-2.5 bg-white border border-slate-200 rounded-xl outline-none font-bold">
                                    <option value="Car">Car</option>
                                    <option value="Motorcycle">Motorcycle</option>
                                </select>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block font-bold text-slate-700">Plate Registration Number *</label>
                                <input type="text" name="plate_number" placeholder="e.g. JWA 1234" required
                                    class="w-full p-2.5 border border-slate-200 rounded-xl outline-none font-mono uppercase font-bold"
                                    value="{{ old('plate_number') }}">
                            </div>

                            <div class="space-y-1.5">
                                <label class="block font-bold text-slate-700">Brand & Model *</label>
                                <input type="text" name="brand_model" placeholder="e.g. Perodua Myvi 1.5 AV" required
                                    class="w-full p-2.5 border border-slate-200 rounded-xl outline-none"
                                    value="{{ old('brand_model') }}">
                            </div>

                            <div class="space-y-1.5">
                                <label class="block font-bold text-slate-700">Engine Capacity (cc)</label>
                                <input type="text" id="engine_capacity" name="engine_capacity" placeholder="e.g. 1500"
                                    class="w-full p-2.5 border border-slate-200 rounded-xl outline-none font-mono"
                                    value="{{ old('engine_capacity') }}">
                            </div>

                            <div class="space-y-1.5 md:col-span-2">
                                <label class="block font-bold text-slate-700">Roadtax Expiration Date *</label>
                                <input type="date" name="roadtax_expiry" required
                                    class="w-full p-2.5 border border-slate-200 rounded-xl outline-none font-mono font-bold"
                                    value="{{ old('roadtax_expiry') }}">
                            </div>
                        </div>

                        <!-- Verification Documents Upload Section -->
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-4">
                            <h3
                                class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-1.5">
                                <i class="fa-solid fa-shield-halved text-blue-600"></i> Required Verification Proofs
                                (MyJPJ / Grant)
                            </h3>

                            <div class="space-y-1.5">
                                <label class="block font-bold text-slate-700">1. Vehicle Ownership Proof (Grant / MyJPJ
                                    Certificate) *</label>
                                <input type="file" name="grant_document" accept="image/*" required
                                    class="w-full p-2 bg-white border border-slate-200 rounded-xl">
                            </div>

                            <div class="space-y-1.5">
                                <label class="block font-bold text-slate-700">2. Active Digital Roadtax Screenshot
                                    (MyJPJ App) *</label>
                                <input type="file" name="roadtax_document" accept="image/*" required
                                    class="w-full p-2 bg-white border border-slate-200 rounded-xl">
                            </div>
                        </div>

                        <!-- Submission Action -->
                        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                            <button type="submit"
                                class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold uppercase tracking-wider rounded-xl transition-all shadow-xs">
                                Submit for Verification
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
