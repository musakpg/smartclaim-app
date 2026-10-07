@extends('layouts.finance')

@section('title', 'SmartClaim - Cash Advance Reconciliation')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1">
            <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <i class="fa-solid fa-wallet text-blue-600"></i> Cash Advance Reconciliation
            </h1>
            <p class="text-xs md:text-sm text-slate-500 font-medium">
                Monitor active floats and reconcile outstanding staff advances.
            </p>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-3xl p-5 border border-slate-200/60 shadow-2xs relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-50 rounded-full blur-2xl group-hover:bg-blue-100 transition-colors"></div>
            <div class="flex items-center gap-4 relative z-10">
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-xl font-bold shadow-inner">
                    <i class="fa-solid fa-money-bill-wave"></i>
                </div>
                <div>
                    <p class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider">Total Float Issued</p>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-xl md:text-2xl font-black text-slate-900 font-mono">RM {{ number_format($totalFloatIssued, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3xl p-5 border border-slate-200/60 shadow-2xs relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-50 rounded-full blur-2xl group-hover:bg-emerald-100 transition-colors"></div>
            <div class="flex items-center gap-4 relative z-10">
                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl font-bold shadow-inner">
                    <i class="fa-solid fa-check-double"></i>
                </div>
                <div>
                    <p class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider">Total Reconciled</p>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-xl md:text-2xl font-black text-slate-900 font-mono">RM {{ number_format($totalReconciled, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3xl p-5 border border-slate-200/60 shadow-2xs relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-50 rounded-full blur-2xl group-hover:bg-amber-100 transition-colors"></div>
            <div class="flex items-center gap-4 relative z-10">
                <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center text-xl font-bold shadow-inner">
                    <i class="fa-solid fa-scale-unbalanced"></i>
                </div>
                <div>
                    <p class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider">Outstanding Balance</p>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-xl md:text-2xl font-black text-slate-900 font-mono">RM {{ number_format($outstandingBalance, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Advances Table -->
    <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-2xs min-h-[420px] flex flex-col justify-between">
        <div class="space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="space-y-0.5">
                    <h3 class="text-xs md:text-sm font-bold text-slate-800 tracking-tight flex items-center gap-2">
                        <i class="fa-solid fa-table-list text-slate-400"></i> Active Cash Advances
                    </h3>
                </div>
            </div>

            <div class="overflow-x-auto -mx-4 px-4 md:mx-0 md:px-0">
                <table class="w-full text-left border-collapse text-xs min-w-[800px] sm:min-w-full">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-bold tracking-wide uppercase bg-slate-50/50 rounded-xl">
                            <th class="py-3 px-3 md:px-4">Advance ID</th>
                            <th class="py-3 px-3 md:px-4">Staff Name</th>
                            <th class="py-3 px-3 md:px-4">Purpose</th>
                            <th class="py-3 px-3 md:px-4 text-right">Approved Amount</th>
                            <th class="py-3 px-3 md:px-4 text-right">Remaining Balance</th>
                            <th class="py-3 px-3 md:px-4 text-center">Status</th>
                            <th class="py-3 px-3 md:px-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-slate-700 font-medium">
                        @forelse($advances as $adv)
                            <tr class="hover:bg-slate-50/60 transition-all">
                                <td class="py-3.5 px-3 md:px-4 font-mono font-bold text-slate-900">
                                    ADV-{{ $adv->advance_id }}
                                </td>
                                <td class="py-3.5 px-3 md:px-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-[10px]">
                                            {{ substr($adv->user->name ?? 'U', 0, 1) }}
                                        </div>
                                        <span class="font-bold text-slate-800">{{ $adv->user->name ?? 'Unknown' }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 md:px-4">
                                    <span class="truncate block max-w-[150px]" title="{{ $adv->purpose }}">{{ $adv->purpose }}</span>
                                </td>
                                <td class="py-3.5 px-3 md:px-4 text-right font-bold text-slate-900 font-mono">
                                    RM {{ number_format($adv->requested_amount, 2) }}
                                </td>
                                <td class="py-3.5 px-3 md:px-4 text-right font-bold {{ $adv->remaining_balance > 0 ? 'text-amber-600' : 'text-emerald-600' }} font-mono">
                                    RM {{ number_format($adv->remaining_balance, 2) }}
                                </td>
                                <td class="py-3.5 px-3 md:px-4 text-center">
                                    @if($adv->status === 'CLEARED')
                                        <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-md font-bold text-[10px]">
                                            <i class="fa-solid fa-check"></i> CLEARED
                                        </span>
                                    @elseif($adv->status === 'PARTIALLY_RECONCILED')
                                        <span class="px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-md font-bold text-[10px]">
                                            PARTIAL
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-md font-bold text-[10px]">
                                            ACTIVE
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-3 md:px-4 text-center">
                                    <button type="button" class="inline-flex items-center gap-1.5 px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-[10px] font-bold transition">
                                        <i class="fa-solid fa-link"></i> Linked Claims
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400 font-sans">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <i class="fa-solid fa-check-circle text-4xl text-slate-300 mb-2"></i>
                                        <span class="text-sm text-slate-500 font-bold">No Active Advances</span>
                                        <span class="text-xs text-slate-400">All cash advances are fully reconciled.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100/50 mt-4">
            {{ $advances->links() }}
        </div>
    </div>
</div>
@endsection
