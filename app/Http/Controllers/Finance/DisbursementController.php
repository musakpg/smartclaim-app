<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Claim;
use App\Services\NotificationService;
use App\Services\ReceiptOcrService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DisbursementController extends Controller
{
    /**
     * Display unified payment disbursement desk.
     */
    public function index(Request $request)
    {
        $isDemo = (bool) (auth()->user()->is_demo ?? false);
        $tab = $request->input('tab', 'pending');

        $pendingQuery = Claim::with(['user.activeCashAdvance', 'items'])
            ->where('status', 'Approved');
        $settledQuery = Claim::with(['user.activeCashAdvance', 'items'])
            ->where('status', 'Reimbursed');

        if ($isDemo) {
            $pendingQuery->where('is_demo', true);
            $settledQuery->where('is_demo', true);
        } else {
            $pendingQuery->where(function ($q) {
                $q->where('is_demo', false)->orWhereNull('is_demo');
            });
            $settledQuery->where(function ($q) {
                $q->where('is_demo', false)->orWhereNull('is_demo');
            });
        }

        $pendingDisbursements = $pendingQuery->orderBy('updated_at', 'desc')
            ->paginate(10, ['*'], 'pending_page')
            ->withQueryString();

        $settledDisbursements = $settledQuery->orderBy('paid_at', 'desc')
            ->paginate(10, ['*'], 'settled_page')
            ->withQueryString();

        $totalPendingAmount = (clone $pendingQuery)->sum('amount');
        $totalSettledAmount = (clone $settledQuery)->sum('amount');

        return view('finance.disbursement', compact(
            'pendingDisbursements',
            'settledDisbursements',
            'totalPendingAmount',
            'totalSettledAmount',
            'tab'
        ));
    }

    /**
     * Process single voucher payout settlement with proof slip or contra reconciliation.
     */
    public function processDisbursement(Request $request, $id)
    {
        $userRole = Auth::check() ? Auth::user()->role : '';
        $normalizedRole = strtolower(trim($userRole));
        if (!in_array($normalizedRole, ['finance', 'fin'])) {
            abort(403, 'Unauthorized action: Only Finance Officers can process disbursements.');
        }

        $request->validate([
            'settlement_method' => 'nullable|in:bank_transfer,contra',
            'payment_reference' => 'required_without:settlement_method|required_if:settlement_method,bank_transfer|string|max:100|nullable',
            'payment_proof' => 'required_without:settlement_method|required_if:settlement_method,bank_transfer|file|mimes:jpeg,png,jpg,pdf|max:10240|nullable',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $claim = Claim::with('user.activeCashAdvance')->lockForUpdate()->findOrFail($id);

            if ($claim->status !== 'Approved') {
                return redirect()->back()->withErrors(['error' => 'This voucher is not eligible for disbursement.']);
            }

            $settlementMethod = $request->input('settlement_method', 'bank_transfer');
            $proofPath = null;
            $cleanRef = null;

            if ($settlementMethod === 'contra') {
                $advance = $claim->user->activeCashAdvance;
                if (!$advance) {
                    return redirect()->back()->withErrors(['error' => 'No active cash advance found for contra settlement.']);
                }

                $deduction = min($claim->amount, $advance->remaining_balance);
                $originalStatus = $advance->status;
                $originalBalance = $advance->remaining_balance;
                $advance->remaining_balance -= $deduction;

                if ($advance->remaining_balance <= 0) {
                    $advance->status = 'CLEARED';
                } else {
                    $advance->status = 'PARTIALLY_RECONCILED';
                }
                $advance->save();

                if (class_exists(AuditLog::class)) {
                    AuditLog::log(
                        'CONTRA_ADJUSTMENT',
                        $claim->claim_id,
                        ['remaining_balance' => $originalBalance, 'status' => $originalStatus],
                        ['remaining_balance' => $advance->remaining_balance, 'status' => $advance->status, 'deducted_amount' => $deduction],
                        auth()->id(),
                        'Cash Advance',
                        'CashAdvance',
                        $advance->advance_id
                    );
                }

                $cleanRef = 'CONTRA-' . $advance->advance_id;

                if ($claim->amount > $deduction) {
                    $request->validate([
                        'payment_reference' => 'required|string|max:100',
                        'payment_proof' => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240',
                    ], [
                        'payment_reference.required' => 'Bank reference is required to pay the difference.',
                        'payment_proof.required' => 'Payment slip is required to pay the difference.'
                    ]);

                    if ($request->hasFile('payment_proof')) {
                        $proofPath = $request->file('payment_proof')->store('payment_proofs', 'private');
                    }
                    $cleanRef = 'CONTRA-' . $advance->advance_id . ' & ' . strtoupper(trim($request->input('payment_reference')));
                }
            } else {
                if ($request->hasFile('payment_proof')) {
                    $proofPath = $request->file('payment_proof')->store('payment_proofs', 'private');
                }
                $cleanRef = strtoupper(trim($request->input('payment_reference')));
            }

            $claim->status = 'Reimbursed';
            $claim->payment_reference = $cleanRef;
            $claim->paid_at = Carbon::now();
            if ($proofPath) {
                $claim->payment_proof_path = $proofPath;
            }
            $claim->save();

            if (class_exists(AuditLog::class)) {
                AuditLog::log(
                    'PAYMENT_DISBURSED',
                    $claim->claim_id,
                    ['status' => 'Approved', 'payment_reference' => null],
                    [
                        'status' => 'Reimbursed',
                        'amount' => $claim->amount,
                        'payment_reference' => $claim->payment_reference,
                        'proof_file' => $proofPath,
                        'disbursed_by' => Auth::user()->name ?? 'Finance Officer',
                        'settlement_method' => $settlementMethod
                    ]
                );
            }

            NotificationService::send(
                $claim->user_id,
                'Payment Reimbursed: Claim Settled',
                "Your claim voucher #CLM-{$claim->claim_id} (RM " . number_format($claim->amount, 2) . ") has been paid. Reference: {$cleanRef}",
                'success',
                route('dashboard')
            );

            return redirect()->route('finance.disbursement', ['tab' => 'settled'])
                ->with('success', "Claim voucher #CLM-{$claim->claim_id} has been marked as Reimbursed.");
        });
    }

    /**
     * Process batch disbursement for multiple approved vouchers.
     */
    public function processBatchDisbursement(Request $request)
    {
        $userRole = Auth::check() ? Auth::user()->role : '';
        $normalizedRole = strtolower(trim($userRole));
        if (!in_array($normalizedRole, ['finance', 'fin'])) {
            abort(403, 'Unauthorized action: Only Finance Officers can process disbursements.');
        }

        $request->validate([
            'claim_ids' => 'required|array|min:1',
            'claim_ids.*' => 'required|integer|exists:claims,claim_id',
            'payment_reference' => 'required|string|max:100',
            'payment_proof' => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240',
        ]);

        try {
            $proofPath = $request->file('payment_proof')->store('payment_proofs', 'private');
            $now = Carbon::now();
            $paymentRef = strtoupper(trim($request->input('payment_reference')));
            $claimIds = $request->input('claim_ids');

            DB::transaction(function () use ($claimIds, $paymentRef, $proofPath, $now) {
                $claims = Claim::whereIn('claim_id', $claimIds)->where('status', 'Approved')->lockForUpdate()->get();

                foreach ($claims as $claim) {
                    $claim->update([
                        'status' => 'Reimbursed',
                        'payment_reference' => $paymentRef,
                        'payment_proof_path' => $proofPath,
                        'paid_at' => $now,
                    ]);

                    if (class_exists(AuditLog::class)) {
                        AuditLog::log(
                            'PAYMENT_DISBURSED_BATCH',
                            $claim->claim_id,
                            ['status' => 'Approved', 'payment_reference' => null],
                            [
                                'status' => 'Reimbursed',
                                'amount' => $claim->amount,
                                'batch_reference' => $paymentRef,
                                'disbursed_by' => Auth::user()->name ?? 'Finance Officer'
                            ]
                        );
                    }

                    try {
                        NotificationService::send(
                            $claim->user_id,
                            '💳 Payment Reimbursed: #CLM-' . $claim->claim_id,
                            "Your claim #CLM-{$claim->claim_id} of RM " . number_format($claim->amount, 2) . " has been paid via batch reference: {$paymentRef}.",
                            'success',
                            route('dashboard')
                        );
                    } catch (\Throwable $th) {
                        // Suppress individual device notification drops
                    }
                }
            });

            return redirect()->route('finance.disbursement', ['tab' => 'settled'])
                ->with('success', 'Batch disbursement successfully processed for ' . count($claimIds) . ' voucher(s).');

        } catch (\Throwable $e) {
            Log::error('Batch Disbursement Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to process batch disbursement: ' . $e->getMessage()]);
        }
    }

    /**
     * Bank slip & PDF OCR extraction via ReceiptOcrService.
     */
    public function asyncScanBankSlip(Request $request, ReceiptOcrService $ocrService)
    {
        if (!$request->hasFile('payment_proof')) {
            return response()->json(['success' => false, 'message' => 'No payment slip detected.']);
        }

        $result = $ocrService->scanBankSlip($request->file('payment_proof'));

        return response()->json($result);
    }
}
