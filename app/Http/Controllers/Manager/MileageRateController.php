<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MileageRate;

class MileageRateController extends Controller
{
    public function index()
    {
        $rates = MileageRate::orderBy('vehicle_type')->get();
        return view('manager.mileage_rates', compact('rates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_type' => 'required|string|max:50|unique:mileage_rates,vehicle_type',
            'min_km' => 'required|integer|min:0',
            'max_km' => 'nullable|integer|gt:min_km',
            'rate' => 'required|numeric|min:0.01'
        ]);

        MileageRate::create([
            'vehicle_type' => $request->input('vehicle_type'),
            'min_km' => $request->input('min_km'),
            'max_km' => $request->input('max_km'),
            'rate' => $request->input('rate'),
        ]);

        return redirect()->route('manager.mileage_rates')->with('success', 'Mileage rate created successfully.');
    }

    public function update(Request $request, $id)
    {
        $mileageRate = MileageRate::findOrFail($id);

        $request->validate([
            'vehicle_type' => 'required|string|max:50|unique:mileage_rates,vehicle_type,' . $id,
            'min_km' => 'required|integer|min:0',
            'max_km' => 'nullable|integer|gt:min_km',
            'rate' => 'required|numeric|min:0.01'
        ]);

        $mileageRate->update([
            'vehicle_type' => $request->input('vehicle_type'),
            'min_km' => $request->input('min_km'),
            'max_km' => $request->input('max_km'),
            'rate' => $request->input('rate'),
        ]);

        return redirect()->route('manager.mileage_rates')->with('success', 'Mileage rate updated successfully.');
    }

    public function destroy($id)
    {
        $mileageRate = MileageRate::findOrFail($id);
        $mileageRate->delete();

        return redirect()->route('manager.mileage_rates')->with('success', 'Mileage rate deleted successfully.');
    }
}
