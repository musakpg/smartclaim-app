<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AiFeedback;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Claim;
use App\Models\ClaimItem;
use App\Models\ExpensePolicy;
use App\Models\MileageRate;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ActiveLearningService;
use App\Services\BudgetEnforcementService;
use App\Services\FraudDetectionService;
use App\Services\NotificationService;
use App\Services\ReceiptOcrService;
use App\Services\SlaTrackingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClaimSubmissionController extends Controller
{
    /**
     * Show claim creation view with active vehicle assets and expense policies.
     */
    public function create()
    {
        $currentUserId = Auth::id();
        if (!$currentUserId) {
            abort(401, 'Unauthenticated.');
        }

        $personalVehicles = Vehicle::where('user_id', $currentUserId)
            ->where('ownership_type', 'personal')
            ->where('approval_status', 'Approved')
            ->whereDate('roadtax_expiry', '>=', Carbon::today())
            ->orderBy('plate_number', 'asc')
            ->get();

        $companyFleet = Vehicle::where('ownership_type', 'company')
            ->where('status', 'Active')
            ->orderBy('plate_number', 'asc')
            ->get();

        $categories = Category::where('is_active', true)->get();

        $expensePolicies = ExpensePolicy::with('category')->where('is_active', true)->get()->map(function ($policy) {
            $policy->category_name = $policy->category->name ?? '';
            return $policy;
        });

        return view('claims.create', compact('personalVehicles', 'companyFleet', 'categories', 'expensePolicies'));
    }

    /**
     * Handle expenditure persistence with physical duplicate prevention, fleet compliance and budget enforcement.
     */
    public function store(Request $request)
    {
        $isMileage = $request->input('claim_type') === 'Mileage';

        $currentUser = Auth::user();
        if (!$currentUser) {
            abort(401, 'Unauthenticated.');
        }
        $currentUserId = $currentUser->id ?? $currentUser->user_id;

        $vehicleId = null;
        $vehiclePlateNumber = null;
        $vehicleType = null;
        $imagePath = null;

        if ($isMileage) {
            $request->validate([
                'title' => 'required|string|max:255',
                'vehicle_id' => 'required|exists:vehicles,vehicle_id',
                'mileage_km' => 'required|numeric|min:0.1',
                'start_location' => 'required|string',
                'destination_location' => 'required|string',
                'transaction_date' => 'nullable|date',
                'mileage_document' => 'required|image|max:5120',
                'business_purpose' => 'required|string',
            ]);

            $selectedVehicle = Vehicle::findOrFail($request->input('vehicle_id'));

            // Mileage claim is strictly for personal vehicle usage
            if ($selectedVehicle->ownership_type !== 'personal') {
                return redirect()->back()
                    ->withErrors(['vehicle_id' => 'Policy Violation: Company fleet assets are prohibited from claiming mileage allowance. Use Fuel Receipt claims instead.'])
                    ->withInput();
            }

            if ($selectedVehicle->user_id != $currentUserId) {
                return redirect()->back()
                    ->withErrors(['vehicle_id' => 'Security Interception: You are not authorized to claim mileage using this vehicle.'])
                    ->withInput();
            }

            if ($selectedVehicle->approval_status !== 'Approved') {
                return redirect()->back()
                    ->withErrors(['vehicle_id' => "Compliance Violation: Vehicle {$selectedVehicle->plate_number} is pending manager verification or has been rejected."])
                    ->withInput();
            }

            if ($selectedVehicle->roadtax_expiry && Carbon::parse($selectedVehicle->roadtax_expiry)->endOfDay()->isPast()) {
                return redirect()->back()
                    ->withErrors(['vehicle_id' => "Submission Denied: Road tax for vehicle {$selectedVehicle->plate_number} expired on " . Carbon::parse($selectedVehicle->roadtax_expiry)->format('d/m/Y') . ". Mileage claims are strictly prohibited for non-compliant vehicles."])
                    ->withInput();
            }

            $vehicleId = $selectedVehicle->vehicle_id;
            $vehiclePlateNumber = $selectedVehicle->plate_number;
            $vehicleType = ucfirst(strtolower($selectedVehicle->vehicle_type));

            // Dynamic rate per KM lookup
            $rateRecord = MileageRate::whereRaw('LOWER(vehicle_type) = ?', [strtolower($vehicleType)])->latest()->first();
            $ratePerKm = $rateRecord ? (float) $rateRecord->rate : ($vehicleType === 'Motorcycle' ? 0.30 : 0.60);

            $km = (float) $request->input('mileage_km');
            // Authoritative server-calculated amount
            $calculatedAmount = round($km * $ratePerKm, 2);

            if ($request->hasFile('mileage_document')) {
                $imagePath = $request->file('mileage_document')->store('receipts', 'private');
            }

            $merchantName = "Aero Art Mileage ({$vehicleType})";
            $predictedCategory = 'Travel';
            $paymentMethod = 'Allowance';

            $rawDate = $request->input('transaction_date');
            $targetTransactionDate = ($rawDate && $rawDate !== '')
                ? Carbon::parse($rawDate)->format('Y-m-d')
                : now()->format('Y-m-d');

        } else {
            $request->validate([
                'receipt' => 'required|image|max:5120',
                'merchant_name' => 'required|string',
                'amount' => 'required|numeric|min:0.01',
                'category' => 'required|string',
                'transaction_date' => 'nullable|date',
                'business_purpose' => 'required|string',
            ]);

            $calculatedAmount = (float) $request->input('amount');
            $merchantName = $request->input('merchant_name');
            $predictedCategory = $request->input('category') ?? 'Office Supplies';
            $paymentMethod = $request->input('payment_method') ?? 'Cash';
            $targetTransactionDate = Carbon::parse($request->input('transaction_date'))->format('Y-m-d');
            $vehiclePlateNumber = $request->input('vehicle_plate_number');

            if (in_array($predictedCategory, ['Fuel & Fleet Logistics', 'Fuel / Automotive', 'Fuel'])) {
                $request->validate([
                    'vehicle_plate_number' => 'required|string|exists:vehicles,plate_number',
                ]);

                $fleetVehicle = Vehicle::where('plate_number', $vehiclePlateNumber)
                    ->where('ownership_type', 'company')
                    ->first();

                if (!$fleetVehicle || $fleetVehicle->status !== 'Active') {
                    return redirect()->back()
                        ->withErrors(['vehicle_plate_number' => 'Compliance Violation: Selected plate number is not an active corporate fleet asset.'])
                        ->withInput();
                }

                $vehicleId = $fleetVehicle->vehicle_id;
            }

            // Physical Image Hash Duplicate Check
            $uploadedReceiptFile = $request->file('receipt');
            if ($uploadedReceiptFile) {
                $imageHashToCheck = hash('sha256', file_get_contents($uploadedReceiptFile->getRealPath()));
                $duplicateClaim = Claim::where('receipt_image_hash', $imageHashToCheck)->first();

                if ($duplicateClaim) {
                    return redirect()->back()
                        ->withErrors(['duplicate' => "Security Violation: This exact physical receipt was already submitted in voucher #CLM-{$duplicateClaim->claim_id}. Please upload an authentic receipt."])
                        ->withInput();
                }
            }

            // Invoice No & Amount Duplicate Check
            $inputInvoiceNo = strtoupper(trim($request->input('receipt_invoice_no', '')));
            if (!empty($inputInvoiceNo) && $inputInvoiceNo !== 'NOT FOUND') {
                $isDatabaseDuplicate = Claim::where('receipt_invoice_no', $inputInvoiceNo)
                    ->where('amount', $calculatedAmount)
                    ->exists();

                if ($isDatabaseDuplicate) {
                    return redirect()->back()
                        ->withErrors(['duplicate' => "Security Interception: A voucher record with identical Invoice No ({$inputInvoiceNo}) and Amount already exists in the system."])
                        ->withInput();
                }
            }

            if ($request->hasFile('receipt')) {
                $imagePath = $request->file('receipt')->store('receipts', 'private');
            }
        }

        // Budget Enforcement Service
        $policyCheck = BudgetEnforcementService::checkPolicy(
            (int) $currentUserId,
            $predictedCategory,
            $calculatedAmount
        );

        // Fraud Scoring Service
        $uploadedFile = $request->file('receipt') ?? $request->file('mileage_document');
        $rawOcrAmount = (float) $request->input('raw_ocr_amount', 0.00);

        $fraudEvaluation = FraudDetectionService::evaluateClaimRisk(
            $uploadedFile,
            $calculatedAmount,
            $rawOcrAmount > 0 ? $rawOcrAmount : null,
            $targetTransactionDate,
            null,
            $request->input('merchant_name'),
            $request->input('receipt_invoice_no'),
            $request->input('start_location'),
            $request->input('destination_location'),
            $request->input('mileage_km') ? (float) $request->input('mileage_km') : null
        );

        // Active Learning Feedback
        $initialAiPrediction = $request->input('ai_raw_prediction') ?? $request->input('predicted_category');
        $finalSelectedCategory = $predictedCategory;

        if (!empty($initialAiPrediction) && !empty($finalSelectedCategory) && $initialAiPrediction !== $finalSelectedCategory) {
            ActiveLearningService::processFeedback(
                (int) $currentUserId,
                $merchantName,
                $initialAiPrediction,
                $finalSelectedCategory,
                $request->input('extracted_raw_text')
            );
        }

        $categoryRecord = Category::where('name', $predictedCategory)
            ->orWhere('code', $predictedCategory)
            ->first();
        $categoryId = $categoryRecord ? $categoryRecord->id : null;

        DB::beginTransaction();
        try {
            $claim = Claim::create([
                'user_id' => $currentUserId,
                'vehicle_id' => $vehicleId,
                'claim_type' => $request->input('claim_type') ?? 'Receipt',
                'title' => $request->input('title') ?? ('Claim at ' . $merchantName),
                'merchant_name' => $merchantName,
                'location_address' => $request->input('location_address') ?? ($isMileage ? $request->input('start_location') : 'Unknown Address'),
                'receipt_invoice_no' => $request->input('receipt_invoice_no') ?? 'NOT FOUND',
                'transaction_date' => $targetTransactionDate,
                'amount' => $calculatedAmount,
                'mileage_km' => $isMileage ? $km : null,
                'vehicle_type' => $vehicleType,
                'start_location' => $isMileage ? $request->input('start_location') : null,
                'destination_location' => $isMileage ? $request->input('destination_location') : null,
                'payment_method' => $paymentMethod,
                'predicted_category' => $predictedCategory,
                'category_id' => $categoryId,
                'business_purpose' => $request->input('business_purpose') ?? 'Business expense',
                'receipt_image_path' => $imagePath ?? 'receipts/default.png',
                'receipt_image_hash' => $fraudEvaluation['image_hash'],
                'risk_score' => $fraudEvaluation['score'],
                'fraud_flags' => $fraudEvaluation['flags'],
                'exif_date_taken' => $fraudEvaluation['exif_date'],
                'extracted_raw_text' => trim($request->input('extracted_raw_text', '')),
                'vehicle_plate_number' => $vehiclePlateNumber,
                'status' => 'Pending',
                'is_policy_violation' => $policyCheck['is_violation'],
                'policy_violation_reason' => $policyCheck['reason'],
                'estimated_payout_date' => Carbon::now()->addWeekdays(5)->toDateString(),
                'is_demo' => (bool) ($currentUser->is_demo ?? false),
            ]);

            $itemsData = $request->input('items', []);
            if (is_string($itemsData)) {
                $itemsData = json_decode($itemsData, true) ?? [];
            }

            if (!empty($itemsData)) {
                foreach ($itemsData as $item) {
                    if (!empty($item['item_name']) && !empty($item['subtotal'])) {
                        ClaimItem::create([
                            'claim_id' => $claim->claim_id,
                            'item_name' => trim($item['item_name']),
                            'quantity' => (int) ($item['quantity'] ?? 1),
                            'unit_price' => (float) ($item['unit_price'] ?? $item['subtotal']),
                            'subtotal' => (float) $item['subtotal'],
                        ]);
                    }
                }
            }

            // Record Discrepancy Entries in ai_feedback for Continuous Learning Feedback Loop
            $initialAiCat = $request->input('ai_predicted_category') ?? $request->input('ai_raw_prediction');
            if (!empty($initialAiCat) && $initialAiCat !== $predictedCategory) {
                AiFeedback::recordDiscrepancy(
                    $claim->claim_id,
                    $claim->receipt_invoice_no,
                    (int) $currentUserId,
                    'category',
                    $initialAiCat,
                    $predictedCategory,
                    88.50,
                    $claim->extracted_raw_text
                );
            }

            $initialAiAmt = $request->input('ai_predicted_amount') ?? ($rawOcrAmount > 0 ? (string)$rawOcrAmount : null);
            if (!empty($initialAiAmt) && abs((float)$initialAiAmt - (float)$calculatedAmount) > 0.01) {
                AiFeedback::recordDiscrepancy(
                    $claim->claim_id,
                    $claim->receipt_invoice_no,
                    (int) $currentUserId,
                    'amount',
                    (string)$initialAiAmt,
                    (string)$calculatedAmount,
                    92.00,
                    $claim->extracted_raw_text
                );
            }

            $initialAiMerch = $request->input('ai_predicted_merchant');
            if (!empty($initialAiMerch) && !empty($merchantName) && strtolower(trim($initialAiMerch)) !== strtolower(trim($merchantName))) {
                AiFeedback::recordDiscrepancy(
                    $claim->claim_id,
                    $claim->receipt_invoice_no,
                    (int) $currentUserId,
                    'merchant',
                    $initialAiMerch,
                    $merchantName,
                    85.00,
                    $claim->extracted_raw_text
                );
            }

            $initialAiDt = $request->input('ai_predicted_date');
            if (!empty($initialAiDt) && !empty($targetTransactionDate) && $initialAiDt !== $targetTransactionDate) {
                AiFeedback::recordDiscrepancy(
                    $claim->claim_id,
                    $claim->receipt_invoice_no,
                    (int) $currentUserId,
                    'date',
                    $initialAiDt,
                    $targetTransactionDate,
                    86.00,
                    $claim->extracted_raw_text
                );
            }

            AuditLog::log(
                'CLAIM_SUBMITTED',
                $claim->claim_id,
                null,
                [
                    'type' => $claim->claim_type,
                    'amount' => $calculatedAmount,
                    'merchant' => $merchantName,
                    'submission_mode' => $isMileage ? 'Mileage Allowance' : 'OCR Receipt',
                    'is_violation' => $policyCheck['is_violation']
                ]
            );

            // Log the fraud analysis result as a separate forensic audit node
            AuditLog::log(
                'FRAUD_ANALYZED',
                $claim->claim_id,
                null,
                [
                    'risk_score' => $fraudEvaluation['score'],
                    'flag_count' => count($fraudEvaluation['flags']),
                    'top_flag' => !empty($fraudEvaluation['flags']) ? $fraudEvaluation['flags'][0]['flag_type'] : null,
                    'top_severity' => !empty($fraudEvaluation['flags']) ? $fraudEvaluation['flags'][0]['severity'] : null,
                    'policy_violation' => $policyCheck['is_violation'],
                    'policy_reason' => $policyCheck['reason'] ?? null
                ]
            );

            DB::commit();

            NotificationService::send(
                $currentUserId,
                'Claim Submitted',
                "Your claim voucher #CLM-{$claim->claim_id} for RM " . number_format($calculatedAmount, 2) . " has been submitted for audit.",
                'info',
                route('claims.history')
            );

            if ($fraudEvaluation['score'] >= 50 || $policyCheck['is_violation']) {
                NotificationService::notifyManagers(
                    'Audit Alert: Flagged Claim',
                    "Staff {$currentUser->name} submitted claim #CLM-{$claim->claim_id} flagged with " . ($policyCheck['is_violation'] ? 'Policy Breach' : "High Risk ({$fraudEvaluation['score']}%)"),
                    'danger',
                    route('manager.verification') . '?status=Pre-Approved'
                );
            } else {
                NotificationService::notifyManagers(
                    'New Claim Submitted for Review',
                    "Staff {$currentUser->name} submitted claim #CLM-{$claim->claim_id} for RM " . number_format($calculatedAmount, 2) . " awaiting verification.",
                    'info',
                    route('manager.verification')
                );
            }

            return redirect()->route('dashboard')->with('success', 'Claim voucher submitted successfully for audit.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Claim Submission Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Database Error: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Render employee claim history log.
     */
    public function history(Request $request)
    {
        $currentUserId = Auth::id();
        if (!$currentUserId) {
            abort(401, 'Unauthenticated.');
        }

        $isDemo = (bool) (Auth::user()->is_demo ?? false);
        $claimsQuery = Claim::with(['items', 'auditLogs.user'])->where('user_id', $currentUserId);

        if ($isDemo) {
            $claimsQuery->where('is_demo', true);
        } else {
            $claimsQuery->where(function ($q) {
                $q->where('is_demo', false)->orWhereNull('is_demo');
            });
        }

        $claimsQuery->latest();

        if ($request->wantsJson() || $request->ajax()) {
            $claims = $claimsQuery->get()->map(function ($c) {
                return array_merge($c->toArray(), [
                    'items' => $c->items->toArray(),
                    'created_at_formatted' => $c->created_at->format('Y-m-d H:i'),
                    'audit_logs' => $c->auditLogs->map(fn($log) => array_merge($log->toArray(), [
                        'user_name' => $log->user->name ?? 'System',
                        'user_role' => $log->user->role ?? 'System',
                    ]))->values()->toArray(),
                    'forensic_hash' => hash('sha256', $c->claim_id . $c->created_at . $c->amount),
                    'estimated_completion_at' => $c->estimated_completion_at ? $c->estimated_completion_at->format('M d, Y') : null,
                    'sla_status' => $c->sla_status,
                    'time_remaining_human' => $c->time_remaining_human,
                ]);
            });
            return response()->json($claims);
        }

        $slaTrackingService = app(SlaTrackingService::class);
        $avgManagerTat = $slaTrackingService->getAverageManagerTurnaroundTime();
        $avgFinanceTat = $slaTrackingService->getAverageFinanceSettlementTime();
        $claims = $claimsQuery->paginate(10)->withQueryString();

        return view('claims.history', compact('avgManagerTat', 'avgFinanceTat', 'claims'));
    }

    /**
     * Render Staff Edit & Resubmit Form for claims in REVISION_REQUIRED.
     */
    public function edit($id)
    {
        $claim = Claim::with(['items', 'vehicle'])->findOrFail($id);
        $currentUserId = Auth::id();
        if (!$currentUserId) {
            abort(401, 'Unauthenticated.');
        }

        if ($claim->user_id !== $currentUserId) {
            abort(403, 'Unauthorized: You can only edit your own claims.');
        }

        if ($claim->status !== 'REVISION_REQUIRED') {
            return redirect()->route('claims.history')
                ->withErrors(['error' => 'This claim cannot be edited because it is not flagged for revision. Current status: ' . $claim->status]);
        }

        $personalVehicles = Vehicle::where('user_id', $currentUserId)
            ->where('ownership_type', 'personal')
            ->where('approval_status', 'Approved')
            ->whereDate('roadtax_expiry', '>=', Carbon::today())
            ->orderBy('plate_number', 'asc')
            ->get();

        $companyFleet = Vehicle::where('ownership_type', 'company')
            ->where('status', 'Active')
            ->orderBy('plate_number', 'asc')
            ->get();

        $categories = Category::where('is_active', true)->get();

        $expensePolicies = ExpensePolicy::with('category')->where('is_active', true)->get()->map(function ($policy) {
            $policy->category_name = $policy->category->name ?? '';
            return $policy;
        });

        return view('claims.edit', compact('claim', 'personalVehicles', 'companyFleet', 'categories', 'expensePolicies'));
    }

    /**
     * Handle Staff Resubmission of Claim after addressing clarification.
     */
    public function resubmit(Request $request, $id)
    {
        $claim = Claim::with('items')->findOrFail($id);
        $currentUserId = Auth::id();
        if (!$currentUserId) {
            abort(401, 'Unauthenticated.');
        }

        if ($claim->user_id !== $currentUserId) {
            abort(403, 'Unauthorized: You can only resubmit your own claims.');
        }

        if ($claim->status !== 'REVISION_REQUIRED') {
            return redirect()->route('claims.history')
                ->withErrors(['error' => 'This claim is not currently marked for revision.']);
        }

        $isMileage = $claim->claim_type === 'Mileage';

        if ($isMileage) {
            $request->validate([
                'title' => 'required|string|max:255',
                'vehicle_id' => 'required|exists:vehicles,vehicle_id',
                'mileage_km' => 'required|numeric|min:0.1',
                'start_location' => 'required|string',
                'destination_location' => 'required|string',
                'transaction_date' => 'nullable|date',
                'mileage_document' => 'nullable|image|max:5120',
                'business_purpose' => 'required|string',
            ]);
        } else {
            $request->validate([
                'title' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0.01',
                'receipt_invoice_no' => 'required|string|max:100',
                'transaction_date' => 'required|date',
                'category_id' => 'required|exists:categories,id',
                'payment_method' => 'required|string',
                'business_purpose' => 'required|string',
                'receipt_image' => 'nullable|image|max:5120',
            ]);
        }

        return DB::transaction(function () use ($request, $claim, $isMileage) {
            $oldStatus = $claim->status;

            if ($isMileage) {
                $vehicle = Vehicle::findOrFail($request->input('vehicle_id'));
                $claim->title = $request->input('title');
                $claim->vehicle_id = $vehicle->vehicle_id;
                $claim->vehicle_plate_number = $vehicle->plate_number;
                $claim->vehicle_type = $vehicle->vehicle_type;
                $claim->mileage_km = (float) $request->input('mileage_km');
                $claim->start_location = $request->input('start_location');
                $claim->destination_location = $request->input('destination_location');
                $claim->business_purpose = $request->input('business_purpose');
                if ($request->input('transaction_date')) {
                    $claim->transaction_date = $request->input('transaction_date');
                }

                $vehicleType = ucfirst(strtolower($vehicle->vehicle_type));
                $mileageRate = MileageRate::whereRaw('LOWER(vehicle_type) = ?', [strtolower($vehicleType)])->latest()->first();
                $ratePerKm = $mileageRate ? (float) $mileageRate->rate : ($vehicleType === 'Motorcycle' ? 0.30 : 0.60);
                $claim->amount = round($claim->mileage_km * $ratePerKm, 2);

                if ($request->hasFile('mileage_document')) {
                    $claim->receipt_image_path = $request->file('mileage_document')->store('receipts', 'private');
                }
            } else {
                $claim->title = $request->input('title');
                $claim->amount = (float) $request->input('amount');
                $claim->receipt_invoice_no = $request->input('receipt_invoice_no');
                $claim->transaction_date = $request->input('transaction_date');
                $claim->category_id = $request->input('category_id');
                $claim->payment_method = $request->input('payment_method');
                $claim->business_purpose = $request->input('business_purpose');

                $category = Category::find($request->input('category_id'));
                if ($category) {
                    $claim->predicted_category = $category->name;
                }

                if ($request->hasFile('receipt_image')) {
                    $claim->receipt_image_path = $request->file('receipt_image')->store('receipts', 'private');
                    $claim->receipt_image_hash = hash('sha256', file_get_contents($request->file('receipt_image')->getRealPath()));
                }
            }

            // Revert status to Submitted/Pending for Auditor re-verification
            $claim->status = 'Pending';
            $claim->save();

            // Record event in AuditLog
            AuditLog::log(
                "CLAIM_RESUBMITTED",
                $claim->claim_id,
                ['status' => $oldStatus],
                [
                    'status' => 'Pending',
                    'action_summary' => 'Claim resubmitted by employee after addressing audit clarification',
                    'resubmitted_by' => Auth::user()->name ?? 'Staff User',
                    'remarks' => $request->input('resubmission_notes', 'Addressed auditor feedback.')
                ]
            );

            // Notify Finance/Manager that clarification has been provided
            $financeUsers = User::whereIn('role', ['Finance', 'Manager', 'finance', 'manager'])->get();
            foreach ($financeUsers as $fUser) {
                NotificationService::send(
                    $fUser->user_id ?? $fUser->id,
                    "Claim Clarification Provided: #CLM-{$claim->claim_id}",
                    "Employee " . (Auth::user()->name ?? 'Staff') . " has updated and resubmitted claim #CLM-{$claim->claim_id} ({$claim->title}).",
                    'info',
                    route('finance.auditing')
                );
            }

            return redirect()->route('claims.history')
                ->with('success', "Claim #CLM-{$claim->claim_id} has been resubmitted successfully for review.");
        });
    }

    /**
     * Staff Lifecycle - Claim Withdrawal / Deletion Governance.
     */
    public function withdraw(Request $request, $id)
    {
        $claim = Claim::findOrFail($id);
        $currentUserId = Auth::id();
        if (!$currentUserId) {
            abort(401, 'Unauthenticated.');
        }

        if ($claim->user_id !== $currentUserId) {
            abort(403, 'Unauthorized: You can only cancel or withdraw your own claims.');
        }

        // Permitted: SUBMITTED (Pending) and REVISION_REQUIRED
        // Forbidden / Locked: PRE_APPROVED, APPROVED, DISBURSED/REIMBURSED
        $lockedStatuses = ['Pre-Approved', 'Approved', 'Disbursed', 'Reimbursed'];
        if (in_array($claim->status, $lockedStatuses)) {
            return redirect()->back()->withErrors([
                'error' => "Action Locked: Claim #CLM-{$claim->claim_id} cannot be withdrawn because it is already under corporate governance ({$claim->status})."
            ]);
        }

        return DB::transaction(function () use ($claim) {
            $oldStatus = $claim->status;

            // Audit Trail Record before physical purge
            AuditLog::log(
                'CLAIM_WITHDRAWN',
                $claim->claim_id,
                ['status' => $oldStatus],
                [
                    'status' => 'CANCELLED',
                    'action_summary' => "Claimant retracted voucher #CLM-{$claim->claim_id} before final corporate authorization.",
                    'actor_name' => Auth::user()->name ?? 'Staff Claimant',
                    'revocation_timestamp' => now()->toIso8601String()
                ]
            );

            // Delete associated claim items
            $claim->items()->delete();

            // Physically delete claim record
            $claim->delete();

            NotificationService::send(
                $claim->user_id,
                'Claim Retracted',
                "Voucher #CLM-{$claim->claim_id} has been successfully withdrawn and removed from corporate review.",
                'warning',
                route('claims.history')
            );

            return redirect()->route('claims.history')
                ->with('success', "Voucher #CLM-{$claim->claim_id} was successfully withdrawn and purged from the review queue.");
        });
    }

    /**
     * Check duplicate invoice / amount rapidly.
     */
    public function checkDuplicate(Request $request)
    {
        $exists = Claim::where('receipt_invoice_no', trim($request->invoice_no))
            ->where('amount', $request->amount)
            ->exists();

        return response()->json(['duplicate' => $exists]);
    }

    /**
     * Trigger asynchronous Receipt OCR extraction via ReceiptOcrService.
     */
    public function asyncScan(Request $request, ReceiptOcrService $ocrService)
    {
        if (!$request->hasFile('receipt')) {
            return response()->json(['success' => false, 'message' => 'No receipt file upload detected.']);
        }

        $result = $ocrService->scanReceipt($request->file('receipt'));

        return response()->json($result);
    }

    /**
     * Render employee reimbursement ledger.
     */
    public function reimbursementIndex()
    {
        $currentUserId = Auth::id();
        if (!$currentUserId) {
            abort(401, 'Unauthenticated.');
        }

        $approvedClaims = Claim::where('user_id', $currentUserId)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->orderBy('updated_at', 'desc')
            ->get();

        $approvedTotal = $approvedClaims->sum('amount');
        $paidTotal = $approvedClaims->where('status', 'Reimbursed')->sum('amount');
        $processingTotal = $approvedClaims->where('status', 'Approved')->sum('amount');

        return view('reimbursement.index', compact('approvedClaims', 'approvedTotal', 'paidTotal', 'processingTotal'));
    }
}
