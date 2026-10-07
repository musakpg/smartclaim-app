@extends('layouts.manager')

@section('title', 'SmartClaim - Continuous Active Learning Ledger')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="border-b border-slate-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Active Learning & Model Tuning Ledger</h1>
            <p class="text-xs md:text-sm text-slate-500">Continuous human-in-the-loop dictionary adaptation and weight tuning monitor.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="hidden lg:flex items-center gap-3">
                <x-system-clock />
            </div>
            <div class="px-4 py-2 bg-purple-50 border border-purple-200 text-purple-700 text-xs font-bold rounded-2xl flex items-center gap-2">
                <i class="fa-solid fa-brain text-purple-600"></i>
                <span>Tuned Samples: {{ $totalTunedKeywords }}</span>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-2xl flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Table Ledger -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-xs overflow-hidden min-h-[420px] flex flex-col justify-between">
        <div>
            <div class="p-4 border-b border-slate-100 font-bold text-xs text-slate-800 flex items-center justify-between">
                <span>Human Corrections & Dynamic Dictionary Extraction</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs min-w-[700px]">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
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
                                    <span class="block text-[10px] text-slate-400 font-normal">By: {{ $item->user->name ?? 'Staff' }}</span>
                                </td>
                                <td class="p-3.5 text-rose-600 font-bold">
                                    <span class="px-2 py-0.5 bg-rose-50 border border-rose-100 rounded-md">{{ $item->predicted_category }}</span>
                                </td>
                                <td class="p-3.5 text-emerald-700 font-bold">
                                    <span class="px-2 py-0.5 bg-emerald-50 border border-emerald-100 rounded-md">{{ $item->corrected_category }}</span>
                                </td>
                                <td class="p-3.5">
                                    <div class="flex flex-wrap gap-1 max-w-xs" x-data="{ showAll: false }">
                                        @php
                                            $keywords = is_string($item->extracted_keywords) ? json_decode($item->extracted_keywords, true) : ($item->extracted_keywords ?? []);
                                            $keywords = is_array($keywords) ? $keywords : [];
                                            $firstFew = array_slice($keywords, 0, 3);
                                            $remainingCount = count($keywords) - 3;
                                        @endphp
                                        @foreach($firstFew as $kw)
                                            <span class="px-1.5 py-0.5 bg-slate-100 border border-slate-200 rounded text-[9px] font-mono text-slate-700">+{{ $kw }}</span>
                                        @endforeach
                                        @if($remainingCount > 0)
                                            <span x-show="!showAll" @click="showAll = true" class="px-1.5 py-0.5 bg-blue-50 border border-blue-200 text-blue-600 rounded text-[9px] font-bold cursor-pointer transition hover:bg-blue-100">
                                                +{{ $remainingCount }} more
                                            </span>
                                            <template x-if="showAll">
                                                <div class="contents">
                                                    @foreach(array_slice($keywords, 3) as $kw)
                                                        <span class="px-1.5 py-0.5 bg-slate-100 border border-slate-200 rounded text-[9px] font-mono text-slate-700">+{{ $kw }}</span>
                                                    @endforeach
                                                </div>
                                            </template>
                                        @endif
                                    </div>
                                </td>
                                <td class="p-3.5 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $item->is_applied ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-400' }}">
                                        {{ $item->is_applied ? 'Weight Boosted' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="p-3.5 text-center">
                                    <form action="{{ route('manager.ai_feedback.toggle', $item->id) }}" method="POST" x-data="{ isSubmitting: false }" @submit="isSubmitting = true">
                                        @csrf
                                        <button type="submit" :disabled="isSubmitting"
                                            class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] rounded-lg transition cursor-pointer flex items-center justify-center gap-1 w-full max-w-[80px] mx-auto">
                                            <template x-if="isSubmitting"><i class="fa-solid fa-spinner fa-spin"></i></template>
                                            <span x-text="isSubmitting ? 'Working' : '{{ $item->is_applied ? 'Disable' : 'Enable' }}'"></span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-12 text-center text-slate-400 font-medium">
                                    <div class="flex flex-col items-center justify-center space-y-3">
                                        <div class="w-16 h-16 bg-slate-50 border border-slate-100 rounded-full flex items-center justify-center mb-1">
                                            <i class="fa-solid fa-microchip text-3xl text-slate-300"></i>
                                        </div>
                                        <span class="text-sm text-slate-500 font-bold">No AI Feedback Yet</span>
                                        <span class="text-xs text-slate-400 max-w-xs">AI predictions are accurate. When a user corrects a prediction, it will appear here for tuning.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 mt-auto">
            {{ $feedbacks->links() }}
        </div>
    </div>
</div>
@endsection
