<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CashAdvance;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashAdvanceRequestController extends Controller
{
    /**
     * Display the employee cash advance management index.
     */
    public function index()
    {
        $userId = Auth::id();
        if (!$userId) {
            abort(401, 'Unauthenticated.');
        }
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

        $userId = Auth::id();
        if (!$userId) {
            abort(401, 'Unauthenticated.');
        }
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
}
