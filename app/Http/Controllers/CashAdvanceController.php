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
            ->where('status', 'Approved')
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
            'status' => 'Pending',
        ]);

        NotificationService::notifyManagers(
            'New Cash Advance Requisition',
            "Staff submitted Advance Requisition #ADV-{$advance->advance_id} for RM " . number_format($amount, 2),
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
        $pendingCount = CashAdvance::where('status', 'Pending')->count();
        $totalDisbursed = CashAdvance::where('status', 'Approved')->sum('requested_amount');

        return view('manager.advances', compact('advances', 'pendingCount', 'totalDisbursed'));
    }

    /**
     * Manager Desk: Approve or Reject cash advance.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Approved,Rejected',
            'manager_remarks' => 'nullable|string',
        ]);

        $advance = CashAdvance::findOrFail($id);
        $advance->status = $request->status;
        $advance->manager_remarks = $request->manager_remarks;
        $advance->save();

        $notifType = $request->status === 'Approved' ? 'success' : 'danger';
        NotificationService::send(
            $advance->user_id,
            "Advance Requisition {$request->status}",
            "Your cash advance requisition #ADV-{$advance->advance_id} for RM " . number_format($advance->requested_amount, 2) . " has been {$request->status}.",
            $notifType,
            route('advances.index')
        );

        return redirect()->back()->with('success', "Requisition status updated to {$request->status}.");
    }
}