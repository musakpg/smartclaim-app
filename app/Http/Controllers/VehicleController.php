<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class VehicleController extends Controller
{
    /**
     * READ: Display the corporate fleet interface with dynamic forensic audit logs.
     */
    public function index()
    {
        $vehicles = DB::table('vehicles')->orderBy('vehicle_id', 'desc')->get();
        
        // Fetch the 5 most recent logistical lifecycle event streams
        $logs = DB::table('vehicle_logs')->orderBy('log_id', 'desc')->take(5)->get();

        return view('manager.vehicles', compact('vehicles', 'logs'));
    }

    /**
     * CREATE: Persist a newly deployed transport asset node and log the event.
     */
    public function store(Request $request)
    {
        $request->validate([
            'plate_number' => [
                'required', 'string', 'unique:vehicles,plate_number',
                'regex:/[A-Za-z]/', 'regex:/[0-9]/',    
            ],
            'model' => [
                'required', 'string', 'min:5', 'regex:/^[A-Za-z0-9\s\-]+$/' 
            ],
            'type' => 'required|in:Car,Motorcycle',
        ], [
            'plate_number.regex' => 'The vehicle plate number must contain a combination of both letters and numbers (e.g., WRA2003).',
            'model.min'          => 'The brand and model description must be at least 5 characters long.',
            'model.regex'        => 'The brand and model name can only contain alphanumeric characters, spaces, or hyphens.',
        ]);

        $cleanPlate = strtoupper(str_replace(' ', '', $request->plate_number));
        $formattedModel = strtoupper(trim($request->model));

        DB::beginTransaction();
        try {
            DB::table('vehicles')->insert([
                'plate_number' => $cleanPlate,
                'brand_model'  => $formattedModel,
                'vehicle_type' => $request->type,
                'status'       => 'Active', 
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // 📝 FORENSIC AUDIT LOG INJECTION
            DB::table('vehicle_logs')->insert([
                'operator_name' => Auth::user()->name ?? 'Executive Manager',
                'action_event'  => 'VEHICLE_CREATE',
                'plate_index'   => $cleanPlate,
                'description'   => "Registered new company asset node: {$formattedModel} ({$request->type}) with deployment status set to Active.",
                'ip_address'    => $request->ip() ?? '127.0.0.1',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            DB::commit();
            return redirect()->route('manager.vehicles')->with('success', 'Corporate fleet vehicle successfully registered into system infrastructure.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Database persistence fault: ' . $e->getMessage()]);
        }
    }

    /**
     * UPDATE: Mutate administrative asset configuration and log the modification parameters.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'model' => ['required', 'string', 'min:5', 'regex:/^[A-Za-z0-9\s\-]+$/'],
            'type'   => 'required|in:Car,Motorcycle',
            'status' => 'required|in:Active,Inactive', 
        ], [
            'model.min'   => 'The brand and model description must be at least 5 characters long.',
            'model.regex' => 'The brand and model name can only contain alphanumeric characters, spaces, or hyphens.',
        ]);

        $formattedModel = strtoupper(trim($request->model));

        DB::beginTransaction();
        try {
            $oldVehicle = DB::table('vehicles')->where('vehicle_id', $id)->first();
            if (!$oldVehicle) throw new \Exception('Target vehicle asset node not found.');

            DB::table('vehicles')->where('vehicle_id', $id)->update([
                'brand_model'  => $formattedModel,
                'vehicle_type' => $request->type,
                'status'       => $request->status,
                'updated_at'   => now(),
            ]);

            // 📝 FORENSIC AUDIT LOG INJECTION
            DB::table('vehicle_logs')->insert([
                'operator_name' => Auth::user()->name ?? 'Executive Manager',
                'action_event'  => 'VEHICLE_UPDATE',
                'plate_index'   => $oldVehicle->plate_number,
                'description'   => "Modified asset metadata configuration. Altered descriptors onto: {$formattedModel} ({$request->type}) | Operational state flipped onto: {$request->status}.",
                'ip_address'    => $request->ip() ?? '127.0.0.1',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            DB::commit();
            return redirect()->route('manager.vehicles')->with('success', 'Asset logistics configuration matrix modified successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Database mutation fault: ' . $e->getMessage()]);
        }
    }

    /**
     * DELETE: Purge a transport asset node and log the hard destructive transaction event.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $vehicle = DB::table('vehicles')->where('vehicle_id', $id)->first();
            if (!$vehicle) throw new \Exception('Target vehicle asset node not found.');

            DB::table('vehicles')->where('vehicle_id', $id)->delete();

            // 📝 FORENSIC AUDIT LOG INJECTION
            DB::table('vehicle_logs')->insert([
                'operator_name' => Auth::user()->name ?? 'Executive Manager',
                'action_event'  => 'VEHICLE_DELETE',
                'plate_index'   => $vehicle->plate_number,
                'description'   => "Hard executed destructive purge on corporate asset: {$vehicle->brand_model}. Record removed permanently from active ledgers.",
                'ip_address'    => request()->ip() ?? '127.0.0.1',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            DB::commit();
            return redirect()->route('manager.vehicles')->with('success', 'Logistics transport asset purged from system database ledger.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Database deletion fault: ' . $e->getMessage()]);
        }
    }
}