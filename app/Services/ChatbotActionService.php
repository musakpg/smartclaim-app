<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Claim;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatbotActionService
{
    /**
     * Execute the designated tool action with strict role-based access control.
     */
    public function executeAction(string $name, array $args, ?array $sessionReceipt = null): array
    {
        $user = Auth::user();
        if (!$user) {
            return [
                'status' => 'error',
                'message' => 'Unauthenticated session. Please log in to proceed.',
            ];
        }

        return match ($name) {
            'submit_claim' => $this->submitClaim($args, $sessionReceipt, $user),
            'check_claim_status' => $this->checkClaimStatus($args, $user),
            'pre_approve_claim' => $this->preApproveClaim($args, $user),
            'final_approve_claim' => $this->finalApproveClaim($args, $user),
            default => [
                'status' => 'error',
                'message' => "Unknown tool or action '{$name}' requested.",
            ],
        };
    }

    /**
     * Submit an expense claim voucher on behalf of the authenticated staff member.
     */
    protected function submitClaim(array $args, ?array $sessionReceipt, User $user): array
    {
        $amount = (float) ($args['amount'] ?? 0);
        if ($amount <= 0) {
            return [
                'status' => 'error',
                'message' => 'The claim amount must be a positive number greater than RM 0.00.',
            ];
        }

        $merchantName = trim($args['merchant_name'] ?? '');
        if (empty($merchantName)) {
            $merchantName = 'Unknown Merchant';
        }

        $categoryInput = trim($args['category'] ?? 'Office Supplies');
        $categoryRecord = Category::where('name', $categoryInput)
            ->orWhere('code', $categoryInput)
            ->orWhere('name', 'like', "%{$categoryInput}%")
            ->first();

        $categoryName = $categoryRecord ? $categoryRecord->name : $categoryInput;
        $categoryId = $categoryRecord ? $categoryRecord->id : null;

        $rawDate = $args['receipt_date'] ?? null;
        try {
            $targetDate = $rawDate ? Carbon::parse($rawDate)->format('Y-m-d') : Carbon::today()->format('Y-m-d');
        } catch (\Throwable $e) {
            $targetDate = Carbon::today()->format('Y-m-d');
        }

        $description = trim($args['description'] ?? 'Expense submitted via SmartClaim AI Assistant');

        // Manage receipt image attachment and SHA-256 binary hash validation
        $imagePath = 'receipts/default.png';
        $imageHash = null;

        if (!empty($sessionReceipt['path']) && !empty($sessionReceipt['hash'])) {
            $imagePath = $sessionReceipt['path'];
            $imageHash = $sessionReceipt['hash'];

            // Physical Image Hash Duplicate Check against historical database entries
            $duplicateClaim = Claim::where('receipt_image_hash', $imageHash)->first();
            if ($duplicateClaim) {
                return [
                    'status' => 'error',
                    'message' => "Fraud Prevention Alert: This exact physical receipt was already submitted under voucher #CLM-{$duplicateClaim->claim_id}. Please upload an authentic receipt.",
                    'duplicate_claim_id' => $duplicateClaim->claim_id,
                ];
            }
        } else {
            $imageHash = hash('sha256', ($user->user_id ?? $user->id) . '|' . $merchantName . '|' . $amount . '|' . microtime(true));
        }

        // Fuel & Fleet Logistics Vehicle Compliance Handling
        $currentUserId = (int) ($user->user_id ?? $user->id);
        $vehiclePlate = trim($args['vehicle_plate'] ?? $args['vehicle_plate_number'] ?? '');
        $vehicleId = !empty($args['vehicle_id']) ? (int) $args['vehicle_id'] : null;

        $isFuelOrAutomotive = in_array(strtolower($categoryName), [
            'fuel / automotive',
            'fuel',
            'automotive',
            'petrol',
            'fuel & fleet logistics (corporate fleet / petrol)',
            'fuel & fleet logistics',
        ])
            || str_contains(strtolower($categoryName), 'fuel')
            || str_contains(strtolower($categoryName), 'petrol');

        if (!empty($vehiclePlate) && !$vehicleId) {
            $cleanPlate = str_replace(' ', '', strtoupper($vehiclePlate));

            // Prefer company fleet vehicle match first
            $matchedVehicle = Vehicle::where('status', 'Active')
                ->where('ownership_type', 'company')
                ->whereRaw("REPLACE(UPPER(plate_number), ' ', '') = ?", [$cleanPlate])
                ->first();

            // Fallback to any active vehicle (e.g. personal vehicle explicitly provided)
            if (!$matchedVehicle) {
                $matchedVehicle = Vehicle::where('status', 'Active')
                    ->whereRaw("REPLACE(UPPER(plate_number), ' ', '') = ?", [$cleanPlate])
                    ->first();
            }

            if ($matchedVehicle) {
                $vehicleId = $matchedVehicle->vehicle_id;
                $vehiclePlate = $matchedVehicle->plate_number;
            }
        } elseif ($vehicleId && empty($vehiclePlate)) {
            $matchedVehicle = Vehicle::where('status', 'Active')->find($vehicleId);
            if ($matchedVehicle) {
                $vehiclePlate = $matchedVehicle->plate_number;
            }
        }

        // If fuel claim and vehicle plate is missing, enforce compliance
        if ($isFuelOrAutomotive && empty($vehiclePlate) && empty($vehicleId)) {
            $companyFleet = Vehicle::companyFleet()->get();
            $userVehicles = Vehicle::where('user_id', $currentUserId)->where('status', 'Active')->get();

            if ($companyFleet->isNotEmpty()) {
                $fleetPlates = $companyFleet->pluck('plate_number')->implode(', ');
                return [
                    'status' => 'error',
                    'message' => "Vehicle Compliance Required: Fuel & Fleet Logistics claims must specify which company fleet vehicle was used ({$fleetPlates}).",
                ];
            } elseif ($userVehicles->isNotEmpty()) {
                $platesList = $userVehicles->pluck('plate_number')->implode(', ');
                return [
                    'status' => 'error',
                    'message' => "Vehicle Compliance Required: Fuel claims must specify which registered vehicle plate was used ({$platesList}).",
                ];
            }
        }

        // Policy & Budget Cap Verification
        $policyCheck = BudgetEnforcementService::checkPolicy(
            $currentUserId,
            $categoryName,
            $amount
        );

        // Atomic Database Transaction for Claim Persistence
        $claim = DB::transaction(function () use ($user, $currentUserId, $merchantName, $targetDate, $amount, $categoryName, $categoryId, $description, $imagePath, $imageHash, $policyCheck, $vehicleId, $vehiclePlate) {
            $claim = Claim::create([
                'user_id' => $currentUserId,
                'vehicle_id' => $vehicleId,
                'vehicle_plate_number' => $vehiclePlate ?: null,
                'claim_type' => 'Receipt',
                'title' => 'Claim at ' . $merchantName,
                'merchant_name' => $merchantName,
                'location_address' => 'Captured via AI Assistant',
                'receipt_invoice_no' => 'AI-' . strtoupper(Str::random(6)),
                'transaction_date' => $targetDate,
                'amount' => $amount,
                'payment_method' => 'Cash',
                'predicted_category' => $categoryName,
                'category_id' => $categoryId,
                'business_purpose' => $description,
                'receipt_image_path' => $imagePath,
                'receipt_image_hash' => $imageHash,
                'risk_score' => $policyCheck['is_violation'] ? 45 : 10,
                'fraud_flags' => $policyCheck['is_violation']
                    ? [['flag_type' => 'POLICY_BREACH', 'severity' => 'HIGH', 'description' => $policyCheck['reason']]]
                    : [],
                'status' => 'Pending',
                'is_policy_violation' => (bool) $policyCheck['is_violation'],
                'policy_violation_reason' => $policyCheck['reason'] ?? null,
                'estimated_payout_date' => Carbon::now()->addWeekdays(5)->toDateString(),
                'is_demo' => (bool) ($user->is_demo ?? false),
            ]);

            AuditLog::log(
                'CLAIM_SUBMITTED',
                $claim->claim_id,
                null,
                [
                    'type' => 'Receipt',
                    'amount' => $amount,
                    'merchant' => $merchantName,
                    'vehicle_plate' => $vehiclePlate ?: null,
                    'submission_mode' => 'AI Chatbot Assistant',
                    'is_violation' => $policyCheck['is_violation'],
                ],
                $currentUserId
            );

            NotificationService::send(
                $currentUserId,
                'Claim Submitted via AI',
                "Your claim voucher #CLM-{$claim->claim_id} for RM " . number_format($amount, 2) . " at {$merchantName} has been submitted for audit.",
                'info',
                route('claims.history')
            );

            return $claim;
        });

        return [
            'status' => 'success',
            'action' => 'submit_claim',
            'claim_id' => $claim->claim_id,
            'voucher_no' => "CLM-{$claim->claim_id}",
            'merchant_name' => $claim->merchant_name,
            'amount' => (float) $claim->amount,
            'category' => $claim->predicted_category,
            'transaction_date' => $claim->transaction_date ? $claim->transaction_date->format('Y-m-d') : $targetDate,
            'vehicle_plate' => $claim->vehicle_plate_number,
            'vehicle_id' => $claim->vehicle_id,
            'is_policy_violation' => (bool) $claim->is_policy_violation,
            'policy_reason' => $claim->policy_violation_reason,
            'receipt_path' => $claim->receipt_image_path,
            'receipt_preview' => !empty($sessionReceipt['base64_data']) ? "data:{$sessionReceipt['mime_type']};base64,{$sessionReceipt['base64_data']}" : null,
            'message' => "Claim voucher #CLM-{$claim->claim_id} for RM " . number_format($amount, 2) . " at {$merchantName}" . ($claim->vehicle_plate_number ? " (Vehicle: {$claim->vehicle_plate_number})" : "") . " has been successfully created and submitted for audit.",
        ];
    }

    /**
     * Check claim status with role-based scoping (Staff vs Finance vs Manager).
     */
    protected function checkClaimStatus(array $args, User $user): array
    {
        $role = strtolower(trim($user->role ?? 'staff'));
        $rawClaimId = $args['claim_id'] ?? null;
        $claimId = null;

        if (!empty($rawClaimId)) {
            if (preg_match('/(\d+)/', (string) $rawClaimId, $matches)) {
                $claimId = (int) $matches[1];
            }
        }

        $userId = (int) ($user->user_id ?? $user->id);

        // Staff claimant scope: strictly restrict visibility to their own records
        if (!in_array($role, ['finance', 'fin', 'manager', 'admin'])) {
            if ($claimId) {
                $claim = Claim::where('user_id', $userId)->where('claim_id', $claimId)->first();
                if (!$claim) {
                    return [
                        'status' => 'not_found',
                        'message' => "Claim #CLM-{$claimId} was not found or does not belong to your account.",
                    ];
                }

                return [
                    'status' => 'success',
                    'action' => 'check_claim_status',
                    'claim' => [
                        'claim_id' => $claim->claim_id,
                        'voucher_no' => "CLM-{$claim->claim_id}",
                        'merchant_name' => $claim->merchant_name,
                        'amount' => (float) $claim->amount,
                        'status' => $claim->status,
                        'category' => $claim->predicted_category,
                        'transaction_date' => $claim->transaction_date ? $claim->transaction_date->format('Y-m-d') : null,
                        'created_at' => $claim->created_at ? $claim->created_at->format('Y-m-d H:i') : null,
                        'remarks' => $claim->remarks ?? $claim->revision_reason ?? $claim->rejection_reason,
                    ],
                ];
            }

            $claims = Claim::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(fn($c) => [
                    'claim_id' => $c->claim_id,
                    'voucher_no' => "CLM-{$c->claim_id}",
                    'merchant_name' => $c->merchant_name,
                    'amount' => (float) $c->amount,
                    'status' => $c->status,
                    'category' => $c->predicted_category,
                    'date' => $c->transaction_date ? $c->transaction_date->format('Y-m-d') : null,
                ])
                ->toArray();

            return [
                'status' => 'success',
                'action' => 'check_claim_status',
                'total_found' => count($claims),
                'claims' => $claims,
                'message' => count($claims) > 0 ? "Retrieved your " . count($claims) . " most recent claim submissions." : "You have not submitted any claims yet.",
            ];
        }

        // Finance and Manager scope: system-wide inspection allowed
        if ($claimId) {
            $claim = Claim::with('user')->find($claimId);
            if (!$claim) {
                return [
                    'status' => 'not_found',
                    'message' => "Claim #CLM-{$claimId} was not found in the system.",
                ];
            }

            return [
                'status' => 'success',
                'action' => 'check_claim_status',
                'claim' => [
                    'claim_id' => $claim->claim_id,
                    'voucher_no' => "CLM-{$claim->claim_id}",
                    'claimant_name' => $claim->user->name ?? 'Staff User',
                    'merchant_name' => $claim->merchant_name,
                    'amount' => (float) $claim->amount,
                    'status' => $claim->status,
                    'category' => $claim->predicted_category,
                    'is_policy_violation' => (bool) $claim->is_policy_violation,
                    'policy_violation_reason' => $claim->policy_violation_reason,
                    'risk_score' => $claim->risk_score,
                    'transaction_date' => $claim->transaction_date ? $claim->transaction_date->format('Y-m-d') : null,
                    'created_at' => $claim->created_at ? $claim->created_at->format('Y-m-d H:i') : null,
                    'remarks' => $claim->remarks ?? $claim->revision_reason ?? $claim->rejection_reason,
                ],
            ];
        }

        // If no claim_id specified, list pending claims requiring review
        $pendingClaims = Claim::with('user')
            ->whereIn('status', ['Pending', 'Pre-Approved', 'Pending Manager'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($c) => [
                'claim_id' => $c->claim_id,
                'voucher_no' => "CLM-{$c->claim_id}",
                'claimant_name' => $c->user->name ?? 'Staff User',
                'merchant_name' => $c->merchant_name,
                'amount' => (float) $c->amount,
                'status' => $c->status,
                'category' => $c->predicted_category,
                'risk_score' => $c->risk_score,
            ])
            ->toArray();

        return [
            'status' => 'success',
            'action' => 'check_claim_status',
            'total_found' => count($pendingClaims),
            'claims' => $pendingClaims,
            'message' => "Retrieved " . count($pendingClaims) . " recent claims awaiting review or action.",
        ];
    }

    /**
     * Conduct Finance role pre-approval audit with pessimistic row locking.
     */
    protected function preApproveClaim(array $args, User $user): array
    {
        $role = strtolower(trim($user->role ?? ''));
        if (!in_array($role, ['finance', 'fin'])) {
            return [
                'status' => 'forbidden',
                'message' => 'Access Denied: Only Finance officers are authorized to pre-approve claims.',
            ];
        }

        $rawClaimId = $args['claim_id'] ?? null;
        $claimId = null;
        if (!empty($rawClaimId) && preg_match('/(\d+)/', (string) $rawClaimId, $matches)) {
            $claimId = (int) $matches[1];
        }

        if (!$claimId) {
            return [
                'status' => 'error',
                'message' => 'A valid numeric claim ID is required for pre-approval.',
            ];
        }

        $currentUserId = (int) ($user->user_id ?? $user->id);

        return DB::transaction(function () use ($claimId, $args, $user, $currentUserId) {
            $claim = Claim::lockForUpdate()->find($claimId);

            if (!$claim) {
                return [
                    'status' => 'not_found',
                    'message' => "Claim #CLM-{$claimId} was not found.",
                ];
            }

            if ($claim->user_id == $currentUserId) {
                return [
                    'status' => 'forbidden',
                    'message' => 'Conflict of Interest: You cannot audit or pre-approve your own claim.',
                ];
            }

            if ($claim->status !== 'Pending') {
                return [
                    'status' => 'invalid_state',
                    'message' => "Claim #CLM-{$claimId} is currently in '{$claim->status}' status (expected 'Pending').",
                ];
            }

            $auditNotes = trim($args['audit_notes'] ?? 'Pre-approved via AI Assistant');
            $oldStatus = $claim->status;
            $claim->status = 'Pre-Approved';
            $claim->remarks = $auditNotes;
            $claim->save();

            AuditLog::log(
                'CLAIM_Pre-Approved',
                $claim->claim_id,
                ['status' => $oldStatus],
                [
                    'status' => 'Pre-Approved',
                    'signed_by' => $user->name ?? 'Finance Officer',
                    'actor_role' => 'Finance',
                    'remarks' => $auditNotes,
                ],
                $currentUserId
            );

            NotificationService::send(
                $claim->user_id,
                'Claim Status: Pre-Approved',
                "Your claim voucher #CLM-{$claim->claim_id} ({$claim->merchant_name}, RM " . number_format($claim->amount, 2) . ") has been audited and pre-approved by Finance.",
                'info',
                route('claims.history')
            );

            NotificationService::notifyManagers(
                "Claim Escalated for Approval: #CLM-{$claim->claim_id}",
                "Finance officer " . ($user->name ?? 'Finance') . " has pre-approved claim #CLM-{$claim->claim_id} ({$claim->merchant_name}, RM " . number_format($claim->amount, 2) . "). Awaiting executive authorization.",
                'info',
                route('manager.verification')
            );

            return [
                'status' => 'success',
                'action' => 'pre_approve_claim',
                'claim_id' => $claim->claim_id,
                'voucher_no' => "CLM-{$claim->claim_id}",
                'merchant_name' => $claim->merchant_name,
                'amount' => (float) $claim->amount,
                'new_status' => 'Pre-Approved',
                'audit_notes' => $auditNotes,
                'message' => "Claim voucher #CLM-{$claim->claim_id} has been successfully pre-approved and escalated to Managers for final authorization.",
            ];
        });
    }

    /**
     * Conduct Manager role executive approval with pessimistic row locking.
     */
    protected function finalApproveClaim(array $args, User $user): array
    {
        $role = strtolower(trim($user->role ?? ''));
        if ($role !== 'manager') {
            return [
                'status' => 'forbidden',
                'message' => 'Access Denied: Only Managers are authorized to grant final executive claim approval.',
            ];
        }

        $rawClaimId = $args['claim_id'] ?? null;
        $claimId = null;
        if (!empty($rawClaimId) && preg_match('/(\d+)/', (string) $rawClaimId, $matches)) {
            $claimId = (int) $matches[1];
        }

        if (!$claimId) {
            return [
                'status' => 'error',
                'message' => 'A valid numeric claim ID is required for final approval.',
            ];
        }

        $currentUserId = (int) ($user->user_id ?? $user->id);

        return DB::transaction(function () use ($claimId, $args, $user, $currentUserId) {
            $claim = Claim::lockForUpdate()->find($claimId);

            if (!$claim) {
                return [
                    'status' => 'not_found',
                    'message' => "Claim #CLM-{$claimId} was not found.",
                ];
            }

            if ($claim->user_id == $currentUserId) {
                return [
                    'status' => 'forbidden',
                    'message' => 'Conflict of Interest: You cannot grant final approval on your own claim.',
                ];
            }

            if (in_array($claim->status, ['Approved', 'Reimbursed', 'Rejected'])) {
                return [
                    'status' => 'invalid_state',
                    'message' => "Claim #CLM-{$claimId} has already been marked as '{$claim->status}'.",
                ];
            }

            $approvalRemarks = trim($args['approval_remarks'] ?? 'Authorized via AI Assistant');
            $oldStatus = $claim->status;
            $claim->status = 'Approved';
            $claim->remarks = $approvalRemarks;
            $claim->save();

            AuditLog::log(
                'CLAIM_Approved',
                $claim->claim_id,
                ['status' => $oldStatus],
                [
                    'status' => 'Approved',
                    'signed_by' => $user->name ?? 'Manager',
                    'actor_role' => 'Manager',
                    'remarks' => $approvalRemarks,
                ],
                $currentUserId
            );

            NotificationService::send(
                $claim->user_id,
                'Claim Status: Approved',
                "Your claim voucher #CLM-{$claim->claim_id} ({$claim->merchant_name}, RM " . number_format($claim->amount, 2) . ") has been officially Approved by Management.",
                'success',
                route('claims.history')
            );

            NotificationService::notifyFinance(
                "Claim Approved for Reimbursement: #CLM-{$claim->claim_id}",
                "Manager " . ($user->name ?? 'Manager') . " has authorized claim #CLM-{$claim->claim_id} ({$claim->merchant_name}, RM " . number_format($claim->amount, 2) . ") for payment disbursement.",
                'success',
                route('finance.disbursement')
            );

            return [
                'status' => 'success',
                'action' => 'final_approve_claim',
                'claim_id' => $claim->claim_id,
                'voucher_no' => "CLM-{$claim->claim_id}",
                'merchant_name' => $claim->merchant_name,
                'amount' => (float) $claim->amount,
                'new_status' => 'Approved',
                'approval_remarks' => $approvalRemarks,
                'message' => "Claim voucher #CLM-{$claim->claim_id} has been officially approved for payment disbursement.",
            ];
        });
    }
}
