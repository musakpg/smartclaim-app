<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Claim;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExportController extends Controller
{
    // Comment: Stream and export authoritative claims ledger into CSV spreadsheet format
    public function exportClaimsCsv(Request $request)
    {
        $user = Auth::user();
        $isPrivileged = in_array($user->role ?? '', ['Finance', 'Manager', 'Admin', 'finance', 'manager', 'admin']);

        if (!$isPrivileged) {
            abort(403, 'Unauthorized access to corporate financial export ledger.');
        }

        $year = $request->input('year', date('Y'));
        $status = $request->input('status');

        $query = Claim::with(['user', 'vehicle'])
            ->whereYear('transaction_date', $year);

        if ($status && $status !== 'All') {
            $query->where('status', $status);
        }

        $claims = $query->orderBy('claim_id', 'desc')->get();

        $fileName = 'SmartClaim_Audit_Ledger_' . $year . '_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = [
            'Voucher ID',
            'Staff Employee Name',
            'Staff Email',
            'Claim Type',
            'Category',
            'Merchant / Title',
            'Receipt / Invoice No',
            'Transaction Date',
            'Claim Amount (RM)',
            'Payment Method',
            'Vehicle Plate',
            'Mileage Distance (KM)',
            'Status',
            'Disbursement Payment Ref',
            'Settled Date',
            'Submission Timestamp'
        ];

        $callback = function () use ($claims, $columns) {
            $file = fopen('php://output', 'w');

            // Comment: UTF-8 BOM for clean Excel UTF-8 character recognition
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, $columns);

            foreach ($claims as $claim) {
                fputcsv($file, [
                    '#CLM-' . str_pad($claim->claim_id, 4, '0', STR_PAD_LEFT),
                    $claim->user->name ?? 'Unknown Staff',
                    $claim->user->email ?? 'N/A',
                    $claim->claim_type ?? 'Receipt',
                    $claim->predicted_category ?? 'General',
                    $claim->claim_type === 'Mileage' ? ($claim->title ?? 'Mileage Allowance') : $claim->merchant_name,
                    $claim->receipt_invoice_no ?? 'N/A',
                    $claim->transaction_date ? Carbon::parse($claim->transaction_date)->format('Y-m-d') : 'N/A',
                    number_format($claim->amount, 2, '.', ''),
                    $claim->payment_method ?? 'Cash',
                    $claim->vehicle_plate_number ?? ($claim->vehicle->plate_number ?? 'N/A'),
                    $claim->mileage_km ? number_format($claim->mileage_km, 2, '.', '') : '0.00',
                    $claim->status,
                    $claim->payment_reference ?? 'Pending Payout',
                    $claim->paid_at ? Carbon::parse($claim->paid_at)->format('Y-m-d H:i') : 'Unsettled',
                    $claim->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        // Record Audit Trail
        if (class_exists(AuditLog::class)) {
            AuditLog::log(
                'REPORT_EXPORTED_CSV',
                null,
                null,
                ['fiscal_year' => $year, 'total_records' => count($claims), 'exported_by' => $user->name ?? 'Manager']
            );
        }

        return response()->stream($callback, 200, $headers);
    }
}