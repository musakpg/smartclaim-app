<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompanyFleetController extends Controller
{
    /**
     * Corporate fleet and vehicle verification overview.
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
     * Register a new corporate fleet vehicle.
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
     * Update corporate fleet asset metadata.
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
     * Remove a corporate fleet vehicle from the system registry.
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
