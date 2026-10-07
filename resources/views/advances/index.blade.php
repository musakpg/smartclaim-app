@extends('layouts.staff')

@section('title', 'SmartClaim - Cash Advances & Float')

@push('styles')
    <script src="https://unpkg.com/imask"></script>
@endpush

@section('content')
<div x-data="advancesForm()" class="space-y-6">
            <div class="space-y-6">

                <!-- Header Actions -->
                <div
                    class="flex flex-col sm:flex-row sm:items-center justify-between pb-5 border-b border-slate-200 gap-4">
                    <div>
                        <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Cash Advance
                            Requisition Desk</h1>
                        <p class="text-xs md:text-sm text-slate-500">Request upfront corporate float and settle against
                            future receipt claims.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="hidden lg:flex items-center gap-3">
                            <x-system-clock />
                            @include('layouts.partials.notification-bell')
                        </div>

                        <button type="button" @click="isModalOpen = true"
                            class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs cursor-pointer flex items-center gap-2">
                            <i class="fa-solid fa-hand-holding-dollar"></i> Request Float Advance
                        </button>
                    </div>
                </div>

                @if(session('success'))
                    <div
                        class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-2xl flex items-center gap-2 shadow-3xs">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                <!-- Active Float Summary Card -->
                <div
                    class="p-5 bg-gradient-to-r from-slate-900 to-slate-800 text-white rounded-3xl shadow-md flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Outstanding
                            Unsettled Float</span>
                        <h2 class="text-2xl md:text-3xl font-black mt-1 font-mono text-emerald-400">RM
                            {{ number_format($totalActiveAdvance ?? 0, 2) }}</h2>
                    </div>
                    <div
                        class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-xl text-emerald-400">
                        <i class="fa-solid fa-vault"></i>
                    </div>
                </div>

                <!-- Advances Table -->
                <div class="bg-white rounded-3xl border border-slate-200/60 shadow-xs overflow-hidden min-h-[420px] flex flex-col justify-between">
                    <div class="overflow-x-auto flex-1">
                        <table class="w-full text-left text-xs min-w-[650px]">
                            <thead
                                class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="p-3.5">Requisition Title</th>
                                    <th class="p-3.5">Required Date</th>
                                    <th class="p-3.5 text-right">Requested</th>
                                    <th class="p-3.5 text-right">Unsettled Balance</th>
                                    <th class="p-3.5 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                @forelse($advances as $adv)
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="p-3.5 font-bold text-slate-900">
                                            #ADV-{{ $adv->advance_id }} — {{ $adv->title }}
                                            <span
                                                class="block text-[10px] text-slate-400 font-normal italic">{{ Str::limit($adv->purpose, 50) }}</span>
                                        </td>
                                        <td class="p-3.5 text-slate-600 font-mono">
                                            {{ $adv->required_date->format('d/m/Y') }}</td>
                                        <td class="p-3.5 text-right font-black text-slate-900">RM
                                            {{ number_format($adv->requested_amount, 2) }}</td>
                                        <td class="p-3.5 text-right font-bold text-emerald-700 font-mono">RM
                                            {{ number_format($adv->remaining_balance, 2) }}</td>
                                        <td class="p-3.5 text-center">
                                            <span
                                                class="px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase tracking-wider
                                                    {{ $adv->status === 'Approved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '' }}
                                                    {{ $adv->status === 'Pending' ? 'bg-amber-50 text-amber-700 border border-amber-200' : '' }}
                                                    {{ $adv->status === 'Settled' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : '' }}
                                                    {{ $adv->status === 'Rejected' ? 'bg-rose-50 text-rose-700 border border-rose-200' : '' }}">
                                                {{ $adv->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-12 text-center">
                                            <div class="flex flex-col items-center justify-center max-w-sm mx-auto">
                                                <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 mb-3 border border-slate-200/60 shadow-inner">
                                                    <i class="fa-solid fa-hand-holding-dollar text-2xl"></i>
                                                </div>
                                                <h4 class="text-sm font-bold text-slate-800">No Cash Advances Logged</h4>
                                                <p class="text-xs text-slate-400 mt-1 mb-4 text-center">You have not requested any upfront corporate floats for this period.</p>
                                                <button type="button" @click="isModalOpen = true" class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold py-2 px-4 rounded-xl shadow-xs transition">
                                                    <i class="fa-solid fa-plus text-[10px]"></i> Request Advance
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(method_exists($advances, 'links'))
                        <div class="p-4 border-t border-slate-100">
                            {{ $advances->links() }}
                        </div>
                    @endif
                </div>

            </div>

    <!-- New Advance Modal -->
    <div x-show="isModalOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl" @click.away="isModalOpen = false">
            <h3 class="text-base font-bold text-slate-900">New Cash Advance Request</h3>
            <form action="{{ route('advances.store') }}" method="POST" class="space-y-3 text-xs" @submit="isSubmitting = true">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Requisition Title / Event</label>
                    <input type="text" name="title" required placeholder="e.g. Aero Art Site Survey Johor"
                        class="w-full p-2.5 border rounded-xl bg-slate-50">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Requested Amount (RM)</label>
                        <input type="text" id="amount_input" required placeholder="500.00"
                            class="w-full p-2.5 border rounded-xl bg-slate-50 font-mono">
                        <input type="hidden" name="requested_amount" x-model="amount">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Required By Date</label>
                        <input type="date" name="required_date" required
                            class="w-full p-2.5 border rounded-xl bg-slate-50">
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Business Purpose Justification</label>
                    <textarea name="purpose" rows="3" required
                        placeholder="State exact institutional operational need..."
                        class="w-full p-2.5 border rounded-xl bg-slate-50"></textarea>
                </div>
                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="isModalOpen = false"
                        class="px-4 py-2 border rounded-xl font-bold text-slate-600 cursor-pointer">Cancel</button>
                    <button type="submit" :disabled="isSubmitting"
                        :class="isSubmitting ? 'bg-slate-300 text-slate-500 cursor-not-allowed' : 'bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer'"
                        class="px-4 py-2 font-bold rounded-xl flex items-center justify-center gap-2">
                        <template x-if="isSubmitting"><i class="fa-solid fa-spinner fa-spin"></i></template>
                        <span x-text="isSubmitting ? 'Submitting...' : 'Submit Request'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        function advancesForm() {
            return {
                isMobileSidebarOpen: false,
                isModalOpen: false,
                isSubmitting: false,
                amount: '',
                currencyMask: null,
                init() {
                    const amountInput = document.getElementById('amount_input');
                    if (amountInput) {
                        this.currencyMask = IMask(amountInput, {
                            mask: Number,
                            scale: 2,
                            signed: false,
                            thousandsSeparator: ',',
                            padFractionalZeros: true,
                            normalizeZeros: true,
                            radix: '.',
                            mapToRadix: ['.']
                        });
                        this.currencyMask.on('accept', () => {
                            this.amount = this.currencyMask.unmaskedValue;
                        });
                    }
                }
            };
        }
    </script>
@endpush
@endsection
