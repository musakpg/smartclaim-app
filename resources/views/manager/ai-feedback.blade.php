<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Continuous Active Learning Ledger</title>
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
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Active Learning & Model
                        Tuning Ledger</h1>
                    <p class="text-xs md:text-sm text-slate-500">Continuous human-in-the-loop dictionary adaptation and
                        weight tuning monitor.</p>
                </div>
                <div
                    class="px-4 py-2 bg-purple-50 border border-purple-200 text-purple-700 text-xs font-bold rounded-2xl flex items-center gap-2">
                    <i class="fa-solid fa-brain text-purple-600"></i>
                    <span>Tuned Samples: {{ $totalTunedKeywords }}</span>
                </div>
            </div>

            @if(session('success'))
                <div
                    class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-2xl flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Table Ledger -->
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-xs overflow-hidden">
                <div
                    class="p-4 border-b border-slate-100 font-bold text-xs text-slate-800 flex items-center justify-between">
                    <span>Human Corrections & Dynamic Dictionary Extraction</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs min-w-[700px]">
                        <thead
                            class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="p-3.5">Merchant / Source</th>
                                <th class="p-3.5">AI Prediction</th>
                                <th class="p-3.5">Human Correction</th>
                                <th class="p-3.5">Learned Keywords</th>
                                <th class="p-3.5 text-center">Status</th>
                                <th class="p-3.5 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @forelse($feedbacks as $item)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="p-3.5 font-bold text-slate-900">
                                        {{ $item->merchant_name ?? 'Unknown Merchant' }}
                                        <span class="block text-[10px] text-slate-400 font-normal">By:
                                            {{ $item->user->name ?? 'Staff' }}</span>
                                    </td>
                                    <td class="p-3.5 text-rose-600 font-bold">
                                        <span
                                            class="px-2 py-0.5 bg-rose-50 border border-rose-100 rounded-md">{{ $item->predicted_category }}</span>
                                    </td>
                                    <td class="p-3.5 text-emerald-700 font-bold">
                                        <span
                                            class="px-2 py-0.5 bg-emerald-50 border border-emerald-100 rounded-md">{{ $item->corrected_category }}</span>
                                    </td>
                                    <td class="p-3.5">
                                        <div class="flex flex-wrap gap-1 max-w-xs">
                                            @foreach($item->extracted_keywords ?? [] as $kw)
                                                <span
                                                    class="px-1.5 py-0.5 bg-slate-100 border border-slate-200 rounded text-[9px] font-mono text-slate-700">+{{ $kw }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="p-3.5 text-center">
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $item->is_applied ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-400' }}">
                                            {{ $item->is_applied ? 'Weight Boosted' : 'Disabled' }}
                                        </span>
                                    </td>
                                    <td class="p-3.5 text-center">
                                        <form action="{{ route('manager.ai_feedback.toggle', $item->id) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] rounded-lg transition cursor-pointer">
                                                {{ $item->is_applied ? 'Disable' : 'Enable' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-slate-400">No human correction feedback
                                        records captured yet. AI predictions are accepted as-is.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-100">
                    {{ $feedbacks->links() }}
                </div>
            </div>

        </main>
    </div>

</body>

</html>