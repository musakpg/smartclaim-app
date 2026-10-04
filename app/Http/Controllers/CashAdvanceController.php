<?php

namespace App\Http\Controllers;

use App\Models\CashAdvance;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashAdvanceController extends Controller
{
    /**
     * Display the employee cash advance management index.
     */
    public function index()
    {
        $userId = Auth::id() ?? 1;
        $advances = CashAdvance::where('user_id', $userId)->latest()->paginate(10);
        $totalActiveAdvance = CashAdvance::where('user_id', $userId)
            ->whereIn('status', ['DISBURSED_ACTIVE', 'PARTIALLY_RECONCILED'])
            ->sum('remaining_balance');

        return view('advances.index', compact('advances', 'totalActiveAdvance'));
    }

    /**
     * Store new cash advance requisition request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'requested_amount' => 'required|numeric|min:10',
            'required_date' => 'required|date|after_or_equal:today',
            'purpose' => 'required|string',
        ]);

        $userId = Auth::id() ?? 1;
        $amount = (float) $request->requested_amount;

        $advance = CashAdvance::create([
            'user_id' => $userId,
            'title' => $request->title,
            'purpose' => $request->purpose,
            'requested_amount' => $amount,
            'settled_amount' => 0.00,
            'remaining_balance' => $amount,
            'required_date' => $request->required_date,
            'status' => 'PENDING_APPROVAL',
        ]);

        NotificationService::send(
            $userId,
            'Cash Advance Submitted',
            "Your cash advance requisition #ADV-{$advance->advance_id} for RM " . number_format($amount, 2) . " has been submitted for Manager approval.",
            'info',
            route('advances.index')
        );

        $staffName = Auth::user()->name ?? 'Staff Employee';
        NotificationService::notifyManagers(
            'New Cash Advance Requisition',
            "Staff {$staffName} submitted Advance Requisition #ADV-{$advance->advance_id} for RM " . number_format($amount, 2) . ".",
            'info',
            route('manager.advances.index')
        );

        return redirect()->back()->with('success', 'Cash advance request submitted successfully for Manager approval.');
    }

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
     * Manager Desk: Approve or Reject cash advance.
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

    /**
     * Finance Desk: View Cash Advance Reconciliation Hub
     */
    public function financeIndex()
    {
        $advances = CashAdvance::with('user')->whereIn('status', ['DISBURSED_ACTIVE', 'PARTIALLY_RECONCILED', 'CLEARED'])->latest()->paginate(10);
        $totalFloatIssued = CashAdvance::whereIn('status', ['DISBURSED_ACTIVE', 'PARTIALLY_RECONCILED', 'CLEARED'])->sum('requested_amount');
        $totalReconciled = CashAdvance::whereIn('status', ['DISBURSED_ACTIVE', 'PARTIALLY_RECONCILED', 'CLEARED'])->sum('settled_amount');
        $outstandingBalance = CashAdvance::whereIn('status', ['DISBURSED_ACTIVE', 'PARTIALLY_RECONCILED'])->sum('remaining_balance');

        return view('finance.cash_advances.index', compact('advances', 'totalFloatIssued', 'totalReconciled', 'outstandingBalance'));
    }
}