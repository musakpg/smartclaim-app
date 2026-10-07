<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Manager\ClaimVerificationController;
use App\Models\Claim;
use App\Models\ClaimAuditReason;
use Illuminate\Http\Request;

class ClaimAuditingController extends Controller
{
    /**
     * Display Finance auditing workbench and handle AJAX claims data queries.
     */
    public function index(Request $request)
    {
        $claimsQuery = Claim::with(['items', 'user', 'auditLogs.user'])->orderBy('created_at', 'desc');

        if ($request->wantsJson() || $request->ajax()) {
            $claims = $claimsQuery->get()->map(function ($c) {
                return array_merge($c->toArray(), [
                    'user_name' => $c->user->name ?? 'Unknown Staff',
                    'user_role' => $c->user->role ?? 'Staff',
                    'formatted_time' => $c->created_at->format('Y-m-d H:i'),
                    'items' => $c->items->toArray(),
                    'amount' => (float) $c->amount > 0 ? (float) $c->amount : (float) $c->calculated_amount,
                    'audit_logs' => $c->auditLogs->map(fn($log) => array_merge($log->toArray(), [
                        'user_name' => $log->user->name ?? 'System',
                        'user_role' => $log->user->role ?? 'System',
                    ]))->values()->toArray(),
                    'forensic_hash' => hash('sha256', $c->claim_id . $c->created_at . ((float) $c->amount > 0 ? (float) $c->amount : (float) $c->calculated_amount)),
                ]);
            });
            return response()->json($claims);
        }

        $activeType = $request->query('type', 'receipt');
        if ($activeType === 'mileage') {
            $claimsQuery->where(function ($q) {
                $q->where('claim_type', 'Mileage')
                  ->orWhere('merchant_name', 'like', '%Aero Art Transport%');
            });
        } else {
            $claimsQuery->where('claim_type', '!=', 'Mileage')
                ->where(function ($q) {
                    $q->whereNull('merchant_name')
                      ->orWhere('merchant_name', 'not like', '%Aero Art Transport%');
                });
        }

        $activeStatus = $request->query('status');
        if ($activeStatus && $activeStatus !== 'All') {
            $claimsQuery->where('status', $activeStatus);
        }

        $claims = $claimsQuery->paginate(10)->withQueryString();
        $claims->getCollection()->transform(function ($c) {
            $c->user_name = $c->user->name ?? 'Unknown Staff';
            $c->user_role = $c->user->role ?? 'Staff';
            $c->formatted_time = $c->created_at->format('Y-m-d H:i');
            return $c;
        });

        $pendingCount = Claim::where('status', 'Pending')->count();
        $approvedCount = Claim::where('status', 'Approved')->count();
        $rejectedCount = Claim::where('status', 'Rejected')->count();
        $preApprovedCount = Claim::where('status', 'Pre-Approved')->count();
        $reimbursedCount = Claim::where('status', 'Reimbursed')->count();

        $revisionReasons = ClaimAuditReason::revisions()->get();
        $rejectionReasons = ClaimAuditReason::rejections()->get();

        return view('finance.auditing', compact(
            'claims',
            'activeType',
            'activeStatus',
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'preApprovedCount',
            'reimbursedCount',
            'revisionReasons',
            'rejectionReasons'
        ));
    }

    /**
     * Delegate or execute status audit transitions from the finance desk.
     */
    public function updateStatus(Request $request, $id, ClaimVerificationController $verificationController)
    {
        return $verificationController->updateStatus($request, $id);
    }
}
