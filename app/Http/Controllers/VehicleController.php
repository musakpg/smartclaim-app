<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Services\NotificationService;

class VehicleController extends Controller
{
    /* =========================================================================
     * SECTION 1: STAFF PERSONAL VEHICLE PORTAL
     * ========================================================================= */

    /**
     * Display authenticated staff's personal registered vehicles.
     */
    public function staffIndex()
    {
        $currentUserId = Auth::id();
        if (!$currentUserId) {
            abort(401, 'Unauthenticated.');
        }

        $vehicles = Vehicle::where('user_id', $currentUserId)
            ->where('ownership_type', 'personal')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('vehicles.index', compact('vehicles'));
    }

    /**
     * Show form for staff to register a new personal vehicle.
     */
    public function staffCreate()
    {
        return view('vehicles.create');
    }

    /**
     * Store new personal vehicle application with MyJPJ / grant uploads.
     */
    public function staffStore(Request $request)
    {
        $currentUserId = Auth::id();
        if (!$currentUserId) {
            abort(401, 'Unauthenticated.');
        }

        $request->validate([
            'plate_number' => [
                'required',
                'string',
                'max:20',
                'unique:vehicles,plate_number',
                'regex:/[A-Za-z]/',
                'regex:/[0-9]/',
            ],
            'brand_model' => 'required|string|min:3|max:255',
            'vehicle_type' => 'required|in:Car,Motorcycle',
            'engine_capacity' => 'nullable|numeric',
            'roadtax_expiry' => 'required|date|after_or_equal:today',
            'grant_document' => 'required|image|max:5120',
            'roadtax_document' => 'required|image|max:5120',
        ], [
            'plate_number.regex' => 'The vehicle plate number must contain letters and numbers (e.g. JWA 1234).',
        ]);

        $cleanPlate = strtoupper(trim($request->plate_number));
        $grantPath = $request->file('grant_document')->store('vehicles/grants', 'private');
        $roadtaxPath = $request->file('roadtax_document')->store('vehicles/roadtax', 'private');

        DB::beginTransaction();
        try {
            $vehicle = Vehicle::create([
                'user_id' => $currentUserId,
                'plate_number' => $cleanPlate,
                'brand_model' => strtoupper(trim($request->brand_model)),
                'vehicle_type' => $request->vehicle_type,
                'engine_capacity' => $request->engine_capacity,
                'ownership_type' => 'personal',
                'roadtax_expiry' => $request->roadtax_expiry,
                'grant_document_path' => $grantPath,
                'roadtax_document_path' => $roadtaxPath,
                'status' => 'Active',
                'approval_status' => 'Pending',
                'roadtax_renewal_status' => 'None',
            ]);

            DB::table('vehicle_logs')->insert([
                'operator_name' => Auth::user()->name ?? 'Staff Employee',
                'action_event' => 'STAFF_VEHICLE_SUBMIT',
                'plate_index' => $cleanPlate,
                'description' => "Submitted personal vehicle application: {$vehicle->brand_model} ({$vehicle->vehicle_type}). Awaiting Manager approval.",
                'ip_address' => $request->ip() ?? '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            // Dispatch notification to Staff and Managers
            try {
                NotificationService::send(
                    $currentUserId,
                    'Vehicle Registration Submitted',
                    "Your personal vehicle {$cleanPlate} ({$vehicle->brand_model}) has been registered and is awaiting Manager verification.",
                    'info',
                    route('vehicles.index')
                );

                $staffName = Auth::user()->name ?? 'Staff Employee';
                NotificationService::notifyManagers(
                    'New Vehicle Submitted for Approval',
                    "Staff {$staffName} submitted vehicle {$cleanPlate} ({$vehicle->brand_model}) for verification.",
                    'info',
                    route('manager.vehicles')
                );
            } catch (\Throwable $e) {
                // Non-blocking notification dispatch
            }

            return redirect()->route('vehicles.index')->with('success', 'Vehicle application submitted successfully and queued for Manager verification.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Application submission fault: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Show form for staff to edit details while Pending or Rejected.
     */
    public function staffEdit($id)
    {
        $currentUserId = Auth::id();
        if (!$currentUserId) {
            abort(401, 'Unauthenticated.');
        }
        $vehicle = Vehicle::where('vehicle_id', $id)
            ->where('user_id', $currentUserId)
            ->firstOrFail();

        if (!$vehicle->can_be_edited) {
            return redirect()->route('vehicles.index')->withErrors(['error' => 'Approved vehicles cannot be modified directly.']);
        }

        return view('vehicles.edit', compact('vehicle'));
    }

    /**
     * Update personal vehicle details & optionally replace documents.
     */
    public function staffUpdate(Request $request, $id)
    {
        $currentUserId = Auth::id();
        if (!$currentUserId) {
            abort(401, 'Unauthenticated.');
        }
        $vehicle = Vehicle::where('vehicle_id', $id)
            ->where('user_id', $currentUserId)
            ->firstOrFail();

        if (!$vehicle->can_be_edited) {
            return redirect()->route('vehicles.index')->withErrors(['error' => 'Approved vehicles cannot be modified.']);
        }

        $request->validate([
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number,' . $vehicle->vehicle_id . ',vehicle_id',
            'brand_model' => 'required|string|min:3|max:255',
            'vehicle_type' => 'required|in:Car,Motorcycle',
            'engine_capacity' => 'nullable|numeric',
            'roadtax_expiry' => 'required|date|after_or_equal:today',
            'grant_document' => 'nullable|image|max:5120',
            'roadtax_document' => 'nullable|image|max:5120',
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('grant_document')) {
                if ($vehicle->grant_document_path) {
                    if (Storage::disk('private')->exists($vehicle->grant_document_path)) {
                        Storage::disk('private')->delete($vehicle->grant_document_path);
                    } elseif (Storage::disk('public')->exists($vehicle->grant_document_path)) {
                        Storage::disk('public')->delete($vehicle->grant_document_path);
                    }
                }
                $vehicle->grant_document_path = $request->file('grant_document')->store('vehicles/grants', 'private');
            }

            if ($request->hasFile('roadtax_document')) {
                if ($vehicle->roadtax_document_path) {
                    if (Storage::disk('private')->exists($vehicle->roadtax_document_path)) {
                        Storage::disk('private')->delete($vehicle->roadtax_document_path);
                    } elseif (Storage::disk('public')->exists($vehicle->roadtax_document_path)) {
                        Storage::disk('public')->delete($vehicle->roadtax_document_path);
                    }
                }
                $vehicle->roadtax_document_path = $request->file('roadtax_document')->store('vehicles/roadtax', 'private');
            }

            $cleanPlate = strtoupper(trim($request->plate_number));
            $vehicle->plate_number = $cleanPlate;
            $vehicle->brand_model = strtoupper(trim($request->brand_model));
            $vehicle->vehicle_type = $request->vehicle_type;
            $vehicle->engine_capacity = $request->engine_capacity;
            $vehicle->roadtax_expiry = $request->roadtax_expiry;
            $vehicle->approval_status = 'Pending';
            $vehicle->rejection_reason = null;
            $vehicle->save();

            DB::table('vehicle_logs')->insert([
                'operator_name' => Auth::user()->name ?? 'Staff Employee',
                'action_event' => 'STAFF_VEHICLE_UPDATE',
                'plate_index' => $cleanPlate,
                'description' => "Updated personal vehicle application parameters. Status reset to Pending review.",
                'ip_address' => $request->ip() ?? '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return redirect()->route('vehicles.index')->with('success', 'Vehicle application updated and resubmitted for verification.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Update failure: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Submit roadtax renewal certificate (Window <= 30 days).
     */
    public function renewRoadtax(Request $request, $id)
    {
        $currentUserId = Auth::id();
        if (!$currentUserId) {
            abort(401, 'Unauthenticated.');
        }
        $vehicle = Vehicle::where('vehicle_id', $id)
            ->where('user_id', $currentUserId)
            ->firstOrFail();

        $request->validate([
            'new_roadtax_expiry' => 'required|date|after:today',
            'new_roadtax_document' => 'required|image|max:5120',
        ]);

        $roadtaxPath = $request->file('new_roadtax_document')->store('vehicles/roadtax', 'private');

        $vehicle->roadtax_expiry = $request->new_roadtax_expiry;
        $vehicle->roadtax_document_path = $roadtaxPath;
        $vehicle->roadtax_renewal_status = 'Pending_Review';
        $vehicle->save();

        DB::table('vehicle_logs')->insert([
            'operator_name' => Auth::user()->name ?? 'Staff Employee',
            'action_event' => 'ROADTAX_RENEWAL_SUBMIT',
            'plate_index' => $vehicle->plate_number,
            'description' => "Uploaded updated roadtax certificate expiring on {$request->new_roadtax_expiry}. Queued for Manager approval.",
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('vehicles.index')->with('success', 'Roadtax renewal proof submitted for Manager verification.');
    }

    /**
     * Cancel or delete personal vehicle registration (only while Pending/Rejected).
     */
    public function staffDestroy($id)
    {
        $currentUserId = Auth::id();
        if (!$currentUserId) {
            abort(401, 'Unauthenticated.');
        }
        $vehicle = Vehicle::where('vehicle_id', $id)
            ->where('user_id', $currentUserId)
            ->firstOrFail();

        if (!$vehicle->can_be_edited) {
            return redirect()->route('vehicles.index')->withErrors(['error' => 'Active approved vehicles cannot be deleted if referenced in historical ledgers.']);
        }

        if ($vehicle->grant_document_path) {
            if (Storage::disk('private')->exists($vehicle->grant_document_path)) {
                Storage::disk('private')->delete($vehicle->grant_document_path);
            } elseif (Storage::disk('public')->exists($vehicle->grant_document_path)) {
                Storage::disk('public')->delete($vehicle->grant_document_path);
            }
        }
        if ($vehicle->roadtax_document_path) {
            if (Storage::disk('private')->exists($vehicle->roadtax_document_path)) {
                Storage::disk('private')->delete($vehicle->roadtax_document_path);
            } elseif (Storage::disk('public')->exists($vehicle->roadtax_document_path)) {
                Storage::disk('public')->delete($vehicle->roadtax_document_path);
            }
        }

        $plate = $vehicle->plate_number;
        $vehicle->delete();

        DB::table('vehicle_logs')->insert([
            'operator_name' => Auth::user()->name ?? 'Staff Employee',
            'action_event' => 'STAFF_VEHICLE_DELETE',
            'plate_index' => $plate,
            'description' => "Cancelled personal vehicle application for plate {$plate}.",
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('vehicles.index')->with('success', 'Vehicle application removed successfully.');
    }


    /* =========================================================================
     * SECTION 2: MANAGER FLEET & VERIFICATION DESK
     * ========================================================================= */

    /**
     * Manager view: Company fleet and personal vehicle verification requests.
     */
    public function managerIndex()
    {
        $companyVehicles = Vehicle::where('ownership_type', 'company')->orderBy('vehicle_id', 'desc')->get();
        $pendingStaffVehicles = Vehicle::with('owner')->where('ownership_type', 'personal')->where('approval_status', 'Pending')->orderBy('created_at', 'asc')->get();
        $approvedStaffVehicles = Vehicle::with('owner')->where('ownership_type', 'personal')->where('approval_status', 'Approved')->orderBy('updated_at', 'desc')->get();
        $rejectedStaffVehicles = Vehicle::with('owner')->where('ownership_type', 'personal')->where('approval_status', 'Rejected')->orderBy('updated_at', 'desc')->get();

        $logs = DB::table('vehicle_logs')->orderBy('log_id', 'desc')->take(10)->get();

        return view('manager.vehicles', compact(
            'companyVehicles',
            'pendingStaffVehicles',
            'approvedStaffVehicles',
            'rejectedStaffVehicles',
            'logs'
        ));
    }

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
     * Manager approves a staff personal vehicle via the dedicated /approve route.
     */
    public function approve(Request $request, $id)
    {
        $vehicle = Vehicle::where('vehicle_id', $id)->firstOrFail();
        $managerId = Auth::id();
        if (!$managerId) {
            abort(401, 'Unauthenticated.');
        }
        $managerName = Auth::user()->name ?? 'Executive Manager';

        $vehicle->approval_status       = 'Approved';
        $vehicle->approved_by           = $managerId;
        $vehicle->approved_at           = now();
        $vehicle->rejection_reason      = null;
        $vehicle->roadtax_renewal_status = 'None';
        $vehicle->save();

        DB::table('vehicle_logs')->insert([
            'operator_name' => $managerName,
            'action_event'  => 'VEHICLE_VERIFICATION_APPROVED',
            'plate_index'   => $vehicle->plate_number,
            'description'   => "Manager {$managerName} approved personal vehicle {$vehicle->plate_number}.",
            'ip_address'    => $request->ip() ?? '127.0.0.1',
            'created_at'    => now(),
            'updated_at'    => now(),
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
     * Manager rejects a staff personal vehicle via the dedicated /reject route.
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $vehicle = Vehicle::where('vehicle_id', $id)->firstOrFail();
        $managerName = Auth::user()->name ?? 'Executive Manager';

        $vehicle->approval_status       = 'Rejected';
        $vehicle->approved_by           = null;
        $vehicle->approved_at           = null;
        $vehicle->rejection_reason      = $request->rejection_reason;
        $vehicle->roadtax_renewal_status = 'None';
        $vehicle->save();

        DB::table('vehicle_logs')->insert([
            'operator_name' => $managerName,
            'action_event'  => 'VEHICLE_VERIFICATION_REJECTED',
            'plate_index'   => $vehicle->plate_number,
            'description'   => "Manager {$managerName} rejected personal vehicle {$vehicle->plate_number}. Reason: {$request->rejection_reason}",
            'ip_address'    => $request->ip() ?? '127.0.0.1',
            'created_at'    => now(),
            'updated_at'    => now(),
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

    /**
     * Manager creates a company fleet asset.
     */
    public function storeCompanyFleet(Request $request)
    {
        $request->validate([
            'plate_number' => [
                'required',
                'string',
                'unique:vehicles,plate_number',
                'regex:/[A-Za-z]/',
                'regex:/[0-9]/',
            ],
            'model' => ['required', 'string', 'min:3', 'regex:/^[A-Za-z0-9\s\-]+$/'],
            'type' => 'required|in:Car,Motorcycle',
            'roadtax_expiry' => 'nullable|date',
        ]);

        $cleanPlate = strtoupper(str_replace(' ', '', $request->plate_number));
        $formattedModel = strtoupper(trim($request->model));

        $managerId = Auth::id();
        if (!$managerId) {
            abort(401, 'Unauthenticated.');
        }

        DB::beginTransaction();
        try {
            Vehicle::create([
                'user_id' => null,
                'plate_number' => $cleanPlate,
                'brand_model' => $formattedModel,
                'vehicle_type' => $request->type,
                'ownership_type' => 'company',
                'roadtax_expiry' => $request->roadtax_expiry ?? now()->addYear(),
                'status' => 'Active',
                'approval_status' => 'Approved',
                'approved_by' => $managerId,
                'approved_at' => now(),
            ]);

            DB::table('vehicle_logs')->insert([
                'operator_name' => Auth::user()->name ?? 'Executive Manager',
                'action_event' => 'COMPANY_FLEET_CREATE',
                'plate_index' => $cleanPlate,
                'description' => "Registered new corporate fleet vehicle: {$formattedModel} ({$request->type}).",
                'ip_address' => $request->ip() ?? '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return redirect()->route('manager.vehicles')->with('success', 'Corporate fleet vehicle successfully registered.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    /**
     * Manager updates company fleet metadata.
     */
    public function updateCompanyFleet(Request $request, $id)
    {
        $request->validate([
            'model' => ['required', 'string', 'min:3', 'regex:/^[A-Za-z0-9\s\-]+$/'],
            'type' => 'required|in:Car,Motorcycle',
            'status' => 'required|in:Active,Inactive',
            'roadtax_expiry' => 'nullable|date',
        ]);

        $vehicle = Vehicle::where('vehicle_id', $id)->firstOrFail();
        $formattedModel = strtoupper(trim($request->model));

        DB::beginTransaction();
        try {
            $vehicle->brand_model = $formattedModel;
            $vehicle->vehicle_type = $request->type;
            $vehicle->status = $request->status;
            if ($request->filled('roadtax_expiry')) {
                $vehicle->roadtax_expiry = $request->roadtax_expiry;
            }
            $vehicle->save();

            DB::table('vehicle_logs')->insert([
                'operator_name' => Auth::user()->name ?? 'Executive Manager',
                'action_event' => 'COMPANY_FLEET_UPDATE',
                'plate_index' => $vehicle->plate_number,
                'description' => "Updated corporate asset: {$formattedModel} ({$request->type}) | Status: {$request->status}.",
                'ip_address' => $request->ip() ?? '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return redirect()->route('manager.vehicles')->with('success', 'Corporate asset configuration updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Update failure: ' . $e->getMessage()]);
        }
    }

    /**
     * Manager deletes a company fleet asset.
     */
    public function destroyCompanyFleet($id)
    {
        $vehicle = Vehicle::where('vehicle_id', $id)->firstOrFail();
        $plate = $vehicle->plate_number;

        DB::beginTransaction();
        try {
            $vehicle->delete();

            DB::table('vehicle_logs')->insert([
                'operator_name' => Auth::user()->name ?? 'Executive Manager',
                'action_event' => 'COMPANY_FLEET_DELETE',
                'plate_index' => $plate,
                'description' => "Purged corporate asset {$plate} from system registry.",
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return redirect()->route('manager.vehicles')->with('success', 'Corporate asset removed from registry.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Deletion error: ' . $e->getMessage()]);
        }
    }
}