<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\CashAdvance;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class CashAdvanceApprovalController extends Controller
{
    /**
     * Manager Desk: View all organization cash advance requests.
     */
    public function managerIndex()
    {
        $advances = CashAdvance::with('user')->latest()->paginate(10);
        $pendingCount = CashAdvance::where('status', 'PENDING_APPROVAL')->count();
        $totalDisbursed = CashAdvance::whereIn('status', ['DISBURSED_ACTIVE', 'PARTIALLY_RECONCILED', 'CLEARED'])->sum('requested_amount');

        return view('manager.advances', compact('advances', 'pendingCount', 'totalDisbursed'));
    }

    /**
     * Manager Desk: Approve or Reject cash advance requisition.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:DISBURSED_ACTIVE,REJECTED',
            'manager_remarks' => 'nullable|string',
        ]);

        $advance = CashAdvance::with('user')->findOrFail($id);
        $advance->status = $request->status;
        $advance->manager_remarks = $request->manager_remarks;
        $advance->save();

        $statusLabel = $request->status === 'DISBURSED_ACTIVE' ? 'Approved & Disbursed' : 'Rejected';
        $notifType = $request->status === 'DISBURSED_ACTIVE' ? 'success' : 'danger';
        $msg = "Your cash advance requisition #ADV-{$advance->advance_id} for RM " . number_format($advance->requested_amount, 2) . " has been {$statusLabel}.";
        if (!empty($request->manager_remarks)) {
            $msg .= " Manager Remarks: {$request->manager_remarks}";
        }

        NotificationService::send(
            $advance->user_id,
            "Advance Requisition: {$statusLabel}",
            $msg,
            $notifType,
            route('advances.index')
        );

        if ($request->status === 'DISBURSED_ACTIVE') {
            NotificationService::notifyFinance(
                'Cash Advance Approved for Reconciliation',
                "Manager approved Advance Requisition #ADV-{$advance->advance_id} (RM " . number_format($advance->requested_amount, 2) . ") for employee " . ($advance->user->name ?? 'Staff') . ".",
                'info',
                route('finance.cash-advances.index')
            );
        }

        return redirect()->back()->with('success', "Requisition status updated to {$statusLabel}.");
    }
}
