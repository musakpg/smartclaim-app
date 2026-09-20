<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VehicleUsageHistoryController extends Controller
{
    /**
     * Display the vehicle usage and trip audit ledger with multi-variable filters.
     */
    public function index(Request $request)
    {
        $query = Claim::with(['user', 'vehicle'])
            ->whereNotNull('vehicle_id');

        // Filter by Ownership Type (Personal vs Company)
        if ($request->filled('ownership_type')) {
            $query->whereHas('vehicle', function ($q) use ($request) {
                $q->where('ownership_type', $request->ownership_type);
            });
        }

        // Filter by Specific Vehicle (Plate Number)
        if ($request->filled('plate_number')) {
            $query->where(function ($q) use ($request) {
                $q->where('vehicle_plate_number', $request->plate_number)
                    ->orWhereHas('vehicle', function ($v) use ($request) {
                        $v->where('plate_number', $request->plate_number);
                    });
            });
        }

        // Filter by Claim Type (Mileage vs Receipt/Fuel)
        if ($request->filled('claim_type')) {
            $query->where('claim_type', $request->claim_type);
        }

        // Filter by Date Range
        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $logs = $query->latest('transaction_date')->paginate(15)->withQueryString();

        // Calculate analytical summary metrics
        $metricsQuery = clone $query;
        $totalKm = (clone $metricsQuery)->where('claim_type', 'Mileage')->sum('mileage_km');
        $totalMileagePayout = (clone $metricsQuery)->where('claim_type', 'Mileage')->sum('amount');
        $totalFuelExpense = (clone $metricsQuery)->where('claim_type', '!=', 'Mileage')->sum('amount');

        $allRegisteredPlates = Vehicle::orderBy('plate_number', 'asc')->pluck('plate_number')->unique();

        return view('manager.vehicle-usage-history', compact(
            'logs',
            'totalKm',
            'totalMileagePayout',
            'totalFuelExpense',
            'allRegisteredPlates'
        ));
    }

    /**
     * Export the filtered audit logs to a clean CSV spreadsheet.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $fileName = 'vehicle_usage_audit_' . date('Ymd_His') . '.csv';

        $query = Claim::with(['user', 'vehicle'])->whereNotNull('vehicle_id');

        if ($request->filled('ownership_type')) {
            $query->whereHas('vehicle', function ($q) use ($request) {
                $q->where('ownership_type', $request->ownership_type);
            });
        }
        if ($request->filled('plate_number')) {
            $query->where(function ($q) use ($request) {
                $q->where('vehicle_plate_number', $request->plate_number)
                    ->orWhereHas('vehicle', function ($v) use ($request) {
                        $v->where('plate_number', $request->plate_number);
                    });
            });
        }
        if ($request->filled('claim_type')) {
            $query->where('claim_type', $request->claim_type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $records = $query->latest('transaction_date')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');

            // CSV Header Row
            fputcsv($handle, [
                'Voucher Ref',
                'Date',
                'Driver / Staff',
                'Ownership',
                'Plate Number',
                'Vehicle Model',
                'Claim Type',
                'Route / Merchant',
                'Distance (KM)',
                'Total Amount (RM)',
                'Approval Status'
            ]);

            foreach ($records as $row) {
                fputcsv($handle, [
                    'CLM-' . str_pad($row->claim_id, 4, '0', STR_PAD_LEFT),
                    $row->transaction_date,
                    $row->user->name ?? 'N/A',
                    strtoupper($row->vehicle->ownership_type ?? 'PERSONAL'),
                    $row->vehicle_plate_number ?? ($row->vehicle->plate_number ?? 'N/A'),
                    $row->vehicle->brand_model ?? 'N/A',
                    $row->claim_type,
                    $row->claim_type === 'Mileage' ? "{$row->start_location} -> {$row->destination_location}" : $row->merchant_name,
                    $row->mileage_km ?? 0,
                    number_format($row->amount, 2, '.', ''),
                    $row->status
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}