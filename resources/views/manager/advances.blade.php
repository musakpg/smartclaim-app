@extends('layouts.manager')

@section('title', 'SmartClaim - Manager Cash Advances')

@section('content')
<div class="space-y-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                                <i class="fa-solid fa-hand-holding-dollar text-indigo-600"></i> Cash Advance Requisitions Desk
                            </h1>
                            <p class="text-xs md:text-sm text-slate-500 font-medium">
                                Review and approve upfront float requests submitted by staff employees.
                            </p>
                        </div>
                    </div>

                    @if(session('success'))
                        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-2xl flex items-center gap-2 shadow-3xs">
                            <i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    <!-- KPI Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-white rounded-3xl p-5 border border-slate-200/60 shadow-2xs relative overflow-hidden">
                            <div class="flex items-center gap-4 relative z-10">
                                <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center text-xl font-bold shadow-inner">
                                    <i class="fa-solid fa-clock"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider">Pending Requisitions</p>
                                    <h3 class="text-xl md:text-2xl font-black text-slate-900 font-mono">{{ $pendingCount ?? 0 }}</h3>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-3xl p-5 border border-slate-200/60 shadow-2xl relative overflow-hidden">
                            <div class="flex items-center gap-4 relative z-10">
                                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl font-bold shadow-inner">
                                    <i class="fa-solid fa-wallet"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-wider">Total Disbursed Float</p>
                                    <h3 class="text-xl md:text-2xl font-black text-slate-900 font-mono">RM {{ number_format($totalDisbursed ?? 0, 2) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Advance Requisitions Table -->
                    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-2xs overflow-hidden min-h-[420px] flex flex-col justify-between">
                        <div>
                            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                                <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider">Requisition Ledger</h2>
                            </div>
                            <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50/50 text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100">
                                    <tr>
                                        <th class="py-3 px-4">Ref ID</th>
                                        <th class="py-3 px-4">Employee</th>
                                        <th class="py-3 px-4">Requisition Title</th>
                                        <th class="py-3 px-4">Amount</th>
                                        <th class="py-3 px-4">Status</th>
                                        <th class="py-3 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-medium">
                                    @forelse($advances as $advance)
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="py-3 px-4 font-mono font-bold text-slate-700">#ADV-{{ $advance->advance_id }}</td>
                                            <td class="py-3 px-4 text-slate-900 font-bold">{{ $advance->user->name ?? 'Unknown Staff' }}</td>
                                            <td class="py-3 px-4 text-slate-600">{{ $advance->title }}</td>
                                            <td class="py-3 px-4 font-mono font-black text-slate-900">RM {{ number_format($advance->requested_amount, 2) }}</td>
                                            <td class="py-3 px-4">
                                                <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase {{ $advance->status === 'PENDING_APPROVAL' ? 'bg-amber-50 text-amber-700 border border-amber-200' : ($advance->status === 'DISBURSED_ACTIVE' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600') }}">
                                                    {{ str_replace('_', ' ', $advance->status) }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-right">
                                                @if($advance->status === 'PENDING_APPROVAL')
                                                    <div class="inline-flex items-center gap-1.5">
                                                        <form method="POST" action="{{ route('manager.advances.status', $advance->advance_id) }}">
                                                            @csrf
                                                            <input type="hidden" name="status" value="DISBURSED_ACTIVE">
                                                            <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-[10px] shadow-3xs cursor-pointer">
                                                                Approve
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('manager.advances.status', $advance->advance_id) }}">
                                                            @csrf
                                                            <input type="hidden" name="status" value="REJECTED">
                                                            <button type="submit" class="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg text-[10px] shadow-3xs cursor-pointer">
                                                                Reject
                                                            </button>
                                                        </form>
                                                    </div>
                                                @else
                                                    <span class="text-slate-400 text-[11px]">Processed</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="py-12 text-center">
                                                <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                                    <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 mb-3 border border-slate-200/60 shadow-inner">
                                                        <i class="fa-solid fa-hand-holding-dollar text-2xl"></i>
                                                    </div>
                                                    <h4 class="text-sm font-bold text-slate-800">No Cash Advance Requisitions</h4>
                                                    <p class="text-xs text-slate-400 mt-1 text-center">No employee float requests currently require manager review.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Standardized Pagination -->
                    @if($advances->hasPages() || $advances->total() > 0)
                        <div class="px-5 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-bold">
                            {{ $advances->links() }}
                        </div>
                    @endif
                </div>
</div>
@endsection
