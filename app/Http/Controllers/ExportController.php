<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Generate and download single claim formal forensic PDF voucher.
     */
    public function downloadVoucherPdf($id)
    {
        $claim = Claim::with(['user', 'vehicle', 'items'])->findOrFail($id);

        $pdf = Pdf::loadView('exports.claim-voucher-pdf', compact('claim'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("VOUCHER_CLM-{$claim->claim_id}.pdf");
    }

    /**
     * Stream CSV dataset export for approved disbursement records.
     */
    public function exportClaimsCsv(Request $request): StreamedResponse
    {
        $status = $request->input('status', 'Approved');
        $fileName = "SmartClaim_Disbursement_Export_" . date('Ymd_His') . ".csv";

        $claims = Claim::with('user')
            ->when($status !== 'All', fn($q) => $q->where('status', $status))
            ->orderBy('transaction_date', 'desc')
            ->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = [
            'Claim ID',
            'Employee Name',
            'Type',
            'Merchant / Title',
            'Invoice No',
            'Category',
            'Transaction Date',
            'Amount (MYR)',
            'Payment Method',
            'Status',
            'Fraud Risk Score (%)',
            'Policy Violation Status'
        ];

        $callback = function () use ($claims, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($claims as $claim) {
                fputcsv($file, [
                    "CLM-{$claim->claim_id}",
                    $claim->user->name ?? 'Staff User',
                    $claim->claim_type,
                    $claim->claim_type === 'Mileage' ? $claim->title : $claim->merchant_name,
                    $claim->receipt_invoice_no,
                    $claim->predicted_category,
                    $claim->transaction_date ? date('d/m/Y', strtotime($claim->transaction_date)) : 'N/A',
                    number_format($claim->amount, 2, '.', ''),
                    $claim->payment_method,
                    $claim->status,
                    $claim->risk_score ?? 0,
                    $claim->is_policy_violation ? 'Yes' : 'No'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}