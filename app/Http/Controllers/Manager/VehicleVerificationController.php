<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VehicleVerificationController extends Controller
{
    /**
     * Manager action: Approve or Reject a staff's personal vehicle.
     */
    public function verifyStaffVehicle(Request $request, $id)
    {
        $request->validate([
            'decision' => 'required|in:Approved,Rejected',
            'rejection_reason' => 'required_if:decision,Rejected|nullable|string|max:500',
        ]);

        $vehicle = Vehicle::where('vehicle_id', $id)->firstOrFail();
        $managerId = Auth::id();
        if (!$managerId) {
            abort(401, 'Unauthenticated.');
        }
        $managerName = Auth::user()->name ?? 'Executive Manager';

        $vehicle->approval_status = $request->decision;
        $vehicle->approved_by = ($request->decision === 'Approved') ? $managerId : null;
        $vehicle->approved_at = ($request->decision === 'Approved') ? now() : null;
        $vehicle->rejection_reason = ($request->decision === 'Rejected') ? $request->rejection_reason : null;
        $vehicle->roadtax_renewal_status = 'None';
        $vehicle->save();

        DB::table('vehicle_logs')->insert([
            'operator_name' => $managerName,
            'action_event' => 'VEHICLE_VERIFICATION_' . strtoupper($request->decision),
            'plate_index' => $vehicle->plate_number,
            'description' => "Manager {$managerName} marked personal vehicle {$vehicle->plate_number} as {$request->decision}." . ($request->decision === 'Rejected' ? " Reason: {$request->rejection_reason}" : ""),
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($vehicle->user_id) {
            $notifType = $request->decision === 'Approved' ? 'success' : 'danger';
            $msg = $request->decision === 'Approved'
                ? "Your personal vehicle {$vehicle->plate_number} ({$vehicle->brand_model}) has been approved for mileage reimbursement claims."
                : "Your vehicle {$vehicle->plate_number} was rejected by Manager. Reason: " . ($request->rejection_reason ?? 'Document verification incomplete.');
            NotificationService::send(
                $vehicle->user_id,
                "Vehicle Registration: {$request->decision}",
                $msg,
                $notifType,
                route('vehicles.index')
            );
        }

        return redirect()->back()->with('success', "Vehicle {$vehicle->plate_number} successfully marked as {$request->decision}.");
    }

    /**
     * Manager approves a staff personal vehicle via dedicated route.
     */
    public function approve(Request $request, $id)
    {
        $vehicle = Vehicle::where('vehicle_id', $id)->firstOrFail();
        $managerId = Auth::id();
        if (!$managerId) {
            abort(401, 'Unauthenticated.');
        }
        $managerName = Auth::user()->name ?? 'Executive Manager';

        $vehicle->approval_status = 'Approved';
        $vehicle->approved_by = $managerId;
        $vehicle->approved_at = now();
        $vehicle->rejection_reason = null;
        $vehicle->roadtax_renewal_status = 'None';
        $vehicle->save();

        DB::table('vehicle_logs')->insert([
            'operator_name' => $managerName,
            'action_event' => 'VEHICLE_VERIFICATION_APPROVED',
            'plate_index' => $vehicle->plate_number,
            'description' => "Manager {$managerName} approved personal vehicle {$vehicle->plate_number}.",
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($vehicle->user_id) {
            NotificationService::send(
                $vehicle->user_id,
                'Vehicle Registration: Approved',
                "Your personal vehicle {$vehicle->plate_number} ({$vehicle->brand_model}) has been approved for mileage reimbursement claims.",
                'success',
                route('vehicles.index')
            );
        }

        return redirect()->back()->with('success', "Vehicle {$vehicle->plate_number} has been approved successfully.");
    }

    /**
     * Manager rejects a staff personal vehicle via dedicated route.
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $vehicle = Vehicle::where('vehicle_id', $id)->firstOrFail();
        $managerName = Auth::user()->name ?? 'Executive Manager';

        $vehicle->approval_status = 'Rejected';
        $vehicle->approved_by = null;
        $vehicle->approved_at = null;
        $vehicle->rejection_reason = $request->rejection_reason;
        $vehicle->roadtax_renewal_status = 'None';
        $vehicle->save();

        DB::table('vehicle_logs')->insert([
            'operator_name' => $managerName,
            'action_event' => 'VEHICLE_VERIFICATION_REJECTED',
            'plate_index' => $vehicle->plate_number,
            'description' => "Manager {$managerName} rejected personal vehicle {$vehicle->plate_number}. Reason: {$request->rejection_reason}",
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($vehicle->user_id) {
            NotificationService::send(
                $vehicle->user_id,
                'Vehicle Registration: Rejected',
                "Your personal vehicle {$vehicle->plate_number} was rejected by Manager. Reason: " . ($request->rejection_reason ?? 'Document verification incomplete.'),
                'danger',
                route('vehicles.index')
            );
        }

        return redirect()->back()->with('success', "Vehicle {$vehicle->plate_number} has been rejected.");
    }
}
