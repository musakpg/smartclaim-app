<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VehicleController extends Controller
{
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
     * Submit roadtax renewal certificate.
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
     * Cancel or delete personal vehicle registration.
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
}
