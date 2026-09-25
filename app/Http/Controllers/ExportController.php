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
        $isPrivileged = in_array($user->role ?? '', ['Finance', 'Manager', 'finance', 'manager']);

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
            'SST Breakdown (RM)',
            'LHDN Tax Category',
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
                // Determine LHDN Category based on predicted category or claim type
                $lhdnCategory = 'Lain-lain';
                if ($claim->claim_type === 'Mileage' || in_array($claim->predicted_category, ['Fuel', 'Parking', 'Toll', 'Transport'])) {
                    $lhdnCategory = 'Perjalanan & Pengangkutan';
                } elseif (in_array($claim->predicted_category, ['Meals', 'Entertainment', 'Food'])) {
                    $lhdnCategory = 'Keraian & Makanan';
                } elseif (in_array($claim->predicted_category, ['Accommodation', 'Hotel'])) {
                    $lhdnCategory = 'Penginapan';
                } elseif (in_array($claim->predicted_category, ['Office Supplies', 'Equipment'])) {
                    $lhdnCategory = 'Alat Tulis & Pejabat';
                }

                // Simple SST extraction logic (assumes 8% SST is embedded in the gross amount for relevant categories)
                $sstAmount = 0.00;
                if ($claim->claim_type !== 'Mileage') {
                    $sstAmount = $claim->amount - ($claim->amount / 1.08);
                }

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
                    number_format($sstAmount, 2, '.', ''),
                    $lhdnCategory,
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

    // Comment: Generate Forensic Printable Expense Voucher (PDF)
    public function downloadVoucherPdf($id)
    {
        $user = Auth::user();
        $normalizedRole = strtolower(trim($user->role ?? ''));
        $isPrivileged = in_array($normalizedRole, ['finance', 'fin', 'manager']);

        $claim = Claim::with(['user', 'vehicle', 'auditLogs.user'])->findOrFail($id);

        if (!$isPrivileged && $claim->user_id !== $user->user_id) {
            abort(403, 'Unauthorized access to this voucher.');
        }

        if (!in_array($claim->status, ['Approved', 'Reimbursed'])) {
            abort(403, 'Voucher can only be generated for Approved or Reimbursed claims.');
        }

        // Record Audit Trail
        if (class_exists(AuditLog::class)) {
            AuditLog::log(
                'VOUCHER_PDF_DOWNLOADED',
                $claim->claim_id,
                null,
                ['downloaded_by' => $user->name],
                $user->user_id
            );
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.claim_voucher_pdf', compact('claim'));
        $fileName = 'Voucher_CLM-' . str_pad($claim->claim_id, 4, '0', STR_PAD_LEFT) . '_' . date('Ymd') . '.pdf';
        
        return $pdf->download($fileName);
    }
}