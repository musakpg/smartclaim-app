<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Claim;
use App\Models\ClaimAuditReason;
use App\Services\NotificationService;
use App\Services\SlaTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClaimVerificationController extends Controller
{
    /**
     * Display Manager claims verification desk.
     */
    public function index(Request $request)
    {
        $currentTab = $request->query('tab', 'pending');

        $claimsQuery = Claim::with(['items', 'user', 'auditLogs.user'])->orderBy('updated_at', 'desc');

        if ($currentTab === 'pending') {
            $claimsQuery->whereIn('status', ['Pending', 'Pre-Approved', 'Pending Manager']);
        } elseif ($currentTab === 'approved') {
            $claimsQuery->where('status', 'Approved');
        } elseif ($currentTab === 'rejected') {
            $claimsQuery->where('status', 'Rejected');
        } else {
            $claimsQuery->whereIn('status', ['Pending', 'Pre-Approved', 'Pending Manager']);
        }

        $claims = $claimsQuery->paginate(10)->withQueryString();
        $claims->getCollection()->transform(function ($c) {
            $c->user_name = $c->user->name ?? 'Staff User';
            return $c;
        });

        $preApprovedCount = Claim::whereIn('status', ['Pending', 'Pre-Approved', 'Pending Manager'])->count();
        $approvedCount = Claim::where('status', 'Approved')->count();
        $rejectedCount = Claim::where('status', 'Rejected')->count();
        $totalReviewCount = Claim::count();

        $revisionReasons = ClaimAuditReason::revisions()->get();
        $rejectionReasons = ClaimAuditReason::rejections()->get();

        return view('manager.verification', compact(
            'claims',
            'currentTab',
            'preApprovedCount',
            'approvedCount',
            'rejectedCount',
            'totalReviewCount',
            'revisionReasons',
            'rejectionReasons'
        ));
    }

    /**
     * Multi-Level Approval State Interception Engine for Manager and Finance roles.
     */
    public function updateStatus(Request $request, $id)
    {
        $targetStatus = $request->status;

        $rules = [
            'status' => 'required|in:Approved,Rejected,Pre-Approved,REVISION_REQUIRED',
        ];

        // Conditional Validation Engine: Revision vs Rejection
        if ($targetStatus === 'REVISION_REQUIRED') {
            $rules['revision_reason'] = 'required|string|max:255';

            $chosenReason = ClaimAuditReason::where('type', 'REVISION')
                ->where(function ($q) use ($request) {
                    $q->where('title', $request->revision_reason)
                        ->orWhere('code', $request->revision_reason);
                })->first();

            $isRemarksMandatory = ($chosenReason && $chosenReason->requires_remarks)
                || stripos($request->revision_reason ?? '', 'Other') !== false;

            if ($isRemarksMandatory) {
                $rules['remarks'] = 'required|string|min:5|max:1000';
            } else {
                $rules['remarks'] = 'nullable|string|max:1000';
            }
        } elseif ($targetStatus === 'Rejected') {
            $rules['rejection_reason'] = 'required|string|max:255';

            $chosenReason = ClaimAuditReason::where('type', 'REJECTION')
                ->where(function ($q) use ($request) {
                    $q->where('title', $request->rejection_reason)
                        ->orWhere('code', $request->rejection_reason);
                })->first();

            $isRemarksMandatory = ($chosenReason && $chosenReason->requires_remarks)
                || stripos($request->rejection_reason ?? '', 'Other') !== false;

            if ($isRemarksMandatory) {
                $rules['remarks'] = 'required|string|min:5|max:1000';
            } else {
                $rules['remarks'] = 'nullable|string|max:1000';
            }
        } else {
            $rules['remarks'] = 'nullable|string|max:1000';
        }

        $request->validate($rules);

        return DB::transaction(function () use ($request, $id, $targetStatus) {
            $claim = Claim::lockForUpdate()->findOrFail($id);

            $userRole = Auth::check() ? Auth::user()->role : 'Finance';

            $normalizedRole = strtolower(trim($userRole));
            if (!in_array($normalizedRole, ['manager', 'finance', 'fin'])) {
                abort(403, 'Unauthorized action: Only Managers and Finance Officers can update claim status.');
            }

            if (Auth::id() === $claim->user_id) {
                abort(403, 'Unauthorized action: You cannot approve or reject your own claim.');
            }

            $finalStatus = $targetStatus;

            if ($targetStatus === 'Approved') {
                if ($userRole === 'Finance') {
                    $finalStatus = 'Pre-Approved';
                    $message = 'Claim successfully verified and escalated to Manager desk.';
                } else {
                    $finalStatus = 'Approved';
                    $message = 'Claim officially authorized and finalized for payment settlement.';
                }
            } elseif ($targetStatus === 'REVISION_REQUIRED') {
                $claim->revision_reason = $request->input('revision_reason');
                $claim->remarks = $request->input('remarks');
                $message = "Claim successfully returned to staff for revision.";
            } elseif ($targetStatus === 'Rejected') {
                $claim->rejection_reason = $request->input('rejection_reason');
                $claim->remarks = $request->input('remarks');
                $message = "Claim status successfully marked as Rejected.";
            } else {
                $message = "Claim status successfully marked as {$finalStatus}.";
            }

            $claim->status = $finalStatus;
            $claim->save();

            AuditLog::log(
                "CLAIM_{$finalStatus}",
                $claim->claim_id,
                ['status' => $claim->getOriginal('status')],
                [
                    'status' => $finalStatus,
                    'signed_by' => Auth::user()->name ?? 'System',
                    'actor_role' => Auth::user()->role ?? 'System',
                    'revision_reason' => $claim->revision_reason,
                    'rejection_reason' => $claim->rejection_reason,
                    'remarks' => $claim->remarks ?? $request->input('remarks', null)
                ]
            );

            $notifType = $finalStatus === 'Approved' ? 'success' : ($finalStatus === 'Rejected' ? 'danger' : ($finalStatus === 'REVISION_REQUIRED' ? 'warning' : 'info'));

            $staffMsg = "Your claim voucher #CLM-{$claim->claim_id} ({$claim->merchant_name}, RM " . number_format($claim->amount, 2) . ") has been marked as {$finalStatus}.";
            if ($finalStatus === 'REVISION_REQUIRED' && !empty($claim->revision_reason)) {
                $staffMsg .= " Reason: {$claim->revision_reason}" . (!empty($claim->remarks) ? " ({$claim->remarks})" : "");
            } elseif ($finalStatus === 'Rejected' && !empty($claim->rejection_reason)) {
                $staffMsg .= " Reason: {$claim->rejection_reason}" . (!empty($claim->remarks) ? " ({$claim->remarks})" : "");
            }

            NotificationService::send(
                $claim->user_id,
                "Claim Status: {$finalStatus}",
                $staffMsg,
                $notifType,
                route('claims.history')
            );

            // Cross-department notification routing between Manager and Finance
            if ($finalStatus === 'Pre-Approved') {
                NotificationService::notifyManagers(
                    "Claim Escalated for Approval: #CLM-{$claim->claim_id}",
                    "Finance officer " . (Auth::user()->name ?? 'Finance') . " has audited claim #CLM-{$claim->claim_id} ({$claim->merchant_name}, RM " . number_format($claim->amount, 2) . "). Awaiting executive authorization.",
                    'info',
                    route('manager.verification')
                );
            } elseif ($finalStatus === 'Approved') {
                NotificationService::notifyFinance(
                    "Claim Approved for Reimbursement: #CLM-{$claim->claim_id}",
                    "Manager " . (Auth::user()->name ?? 'Manager') . " has authorized claim #CLM-{$claim->claim_id} ({$claim->merchant_name}, RM " . number_format($claim->amount, 2) . ") for payment disbursement.",
                    'success',
                    route('finance.disbursement')
                );
            }

            return redirect()->back()->with('success', $message);
        });
    }

    /**
     * Display turnaround time and bottleneck performance analytics.
     */
    public function slaAnalytics(SlaTrackingService $slaTrackingService)
    {
        $avgManagerTat = $slaTrackingService->getAverageManagerTurnaroundTime();
        $avgFinanceTat = $slaTrackingService->getAverageFinanceSettlementTime();
        $bottlenecks = $slaTrackingService->getBottleneckQueue();

        return view('manager.sla_analytics', compact('avgManagerTat', 'avgFinanceTat', 'bottlenecks'));
    }
}
