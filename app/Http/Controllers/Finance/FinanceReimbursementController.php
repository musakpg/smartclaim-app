<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinanceReimbursementController extends Controller
{
    /**
     * Display the finance reimbursement settlement ledger.
     */
    public function index(Request $request)
    {
        // Tab 1: Ready for Payout (Approved by Manager)
        $readyClaims = Claim::with(['user', 'vehicle'])
            ->where('status', 'Approved')
            ->latest('updated_at')
            ->get();

        // Tab 2: Settled History (Reimbursed)
        $settledClaims = Claim::with(['user', 'vehicle', 'reimburser'])
            ->where('status', 'Reimbursed')
            ->latest('reimbursed_at')
            ->paginate(15);

        $pendingPayoutTotal = $readyClaims->sum('amount');
        $settledTotal = Claim::where('status', 'Reimbursed')->sum('amount');

        return view('finance.reimbursement', compact(
            'readyClaims',
            'settledClaims',
            'pendingPayoutTotal',
            'settledTotal'
        ));
    }

    /**
     * Settle single claim payout.
     */
    public function settleSingle(Request $request, Claim $claim)
    {
        $validated = $request->validate([
            'payment_reference' => 'required|string|max:100',
        ]);

        if ($claim->status !== 'Approved') {
            return redirect()->back()->withErrors(['error' => 'Voucher is not approved for settlement.']);
        }

        $claim->update([
            'status' => 'Reimbursed',
            'payment_reference' => strtoupper(trim($validated['payment_reference'])),
            'reimbursed_at' => now(),
            'reimbursed_by' => Auth::id() ?? 1,
        ]);

        return redirect()->route('finance.reimbursement.index')
            ->with('success', "Payment voucher CLM-" . str_pad($claim->claim_id, 4, '0', STR_PAD_LEFT) . " successfully marked as Reimbursed.");
    }

    /**
     * Batch settle multiple approved claims under a single batch transaction reference.
     */
    public function settleBatch(Request $request)
    {
        $validated = $request->validate([
            'claim_ids' => 'required|array|min:1',
            'claim_ids.*' => 'exists:claims,claim_id',
            'batch_payment_reference' => 'required|string|max:100',
        ]);

        DB::beginTransaction();
        try {
            Claim::whereIn('claim_id', $validated['claim_ids'])
                ->where('status', 'Approved')
                ->update([
                    'status' => 'Reimbursed',
                    'payment_reference' => strtoupper(trim($validated['batch_payment_reference'])),
                    'reimbursed_at' => now(),
                    'reimbursed_by' => Auth::id() ?? 1,
                ]);

            DB::commit();
            return redirect()->route('finance.reimbursement.index')
                ->with('success', count($validated['claim_ids']) . " claims successfully disbursed and settled.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Disbursement failed: ' . $e->getMessage()]);
        }
    }
}