<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\CashAdvance;

class CashAdvanceReconciliationController extends Controller
{
    /**
     * Finance Desk: View Cash Advance Reconciliation Hub.
     */
    public function financeIndex()
    {
        $advances = CashAdvance::with('user')->whereIn('status', ['DISBURSED_ACTIVE', 'PARTIALLY_RECONCILED', 'CLEARED'])->latest()->paginate(10)->withQueryString();
        $totalFloatIssued = CashAdvance::whereIn('status', ['DISBURSED_ACTIVE', 'PARTIALLY_RECONCILED', 'CLEARED'])->sum('requested_amount');
        $totalReconciled = CashAdvance::whereIn('status', ['DISBURSED_ACTIVE', 'PARTIALLY_RECONCILED', 'CLEARED'])->sum('settled_amount');
        $outstandingBalance = CashAdvance::whereIn('status', ['DISBURSED_ACTIVE', 'PARTIALLY_RECONCILED'])->sum('remaining_balance');

        return view('finance.cash_advances.index', compact('advances', 'totalFloatIssued', 'totalReconciled', 'outstandingBalance'));
    }
}
