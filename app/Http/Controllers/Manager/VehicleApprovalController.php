<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VehicleApprovalController extends Controller
{
    /**
     * Display manager vehicle verification desk with tab filtering.
     */
    public function index(Request $request)
    {
        $status = $request->query('tab', 'pending');

        // Fetch vehicle datasets based on verification lifecycle
        $pendingVehicles = Vehicle::with('user')
            ->where('approval_status', 'Pending')
            ->orWhere('roadtax_renewal_status', 'Pending_Review')
            ->latest()
            ->get();

        $approvedVehicles = Vehicle::with(['user', 'approver'])
            ->where('approval_status', 'Approved')
            ->where(function ($query) {
                $query->whereNull('roadtax_renewal_status')
                    ->orWhere('roadtax_renewal_status', 'None');
            })
            ->latest('approved_at')
            ->get();

        $rejectedVehicles = Vehicle::with(['user', 'approver'])
            ->where('approval_status', 'Rejected')
            ->latest('updated_at')
            ->get();

        return view('manager.vehicles', compact('pendingVehicles', 'approvedVehicles', 'rejectedVehicles', 'status'));
    }

    /**
     * Approve vehicle registration or roadtax renewal.
     */
    public function approve(Request $request, Vehicle $vehicle)
    {
        // Handle renewal approval vs initial registration approval
        if ($vehicle->roadtax_renewal_status === 'Pending_Review') {
            $vehicle->update([
                'roadtax_renewal_status' => 'None',
                'rejection_reason' => null,
            ]);
            $message = "Vehicle {$vehicle->plate_number} roadtax renewal has been approved.";
        } else {
            $vehicle->update([
                'approval_status' => 'Approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);
            $message = "Vehicle {$vehicle->plate_number} has been approved for mileage claims.";
        }

        return redirect()->route('manager.vehicles.index', ['tab' => 'pending'])
            ->with('success', $message);
    }

    /**
     * Reject vehicle registration or roadtax renewal with mandatory reason.
     */
    public function reject(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:5|max:500',
        ]);

        $vehicle->update([
            'approval_status' => 'Rejected',
            'roadtax_renewal_status' => 'None',
            'rejection_reason' => $validated['rejection_reason'],
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('manager.vehicles.index', ['tab' => 'pending'])
            ->with('success', "Vehicle {$vehicle->plate_number} registration has been rejected.");
    }
}