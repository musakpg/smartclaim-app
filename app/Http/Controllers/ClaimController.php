<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Finance\ClaimAuditingController;
use App\Http\Controllers\Finance\DisbursementController;
use App\Http\Controllers\Manager\ClaimVerificationController;
use App\Http\Controllers\Staff\ClaimSubmissionController;
use App\Mail\PasswordResetSuccessMail;
use App\Models\AuditLog;
use App\Models\Claim;
use App\Models\ExpensePolicy;
use App\Models\MileageRate;
use App\Models\User;
use App\Services\EmailDeliveryService;
use App\Services\ReceiptOcrService;
use App\Services\SlaTrackingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class ClaimController extends Controller
{
    /**
     * Render regular employee analytics dashboard.
     */
    public function index()
    {
        $currentUserId = Auth::id();
        if (!$currentUserId) {
            abort(401, 'Unauthenticated.');
        }

        $totalSpending = Claim::where('user_id', $currentUserId)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->sum('amount');

        $approvedCount = Claim::where('user_id', $currentUserId)->whereIn('status', ['Approved', 'Reimbursed'])->count();
        $preApprovedCount = Claim::where('user_id', $currentUserId)->where('status', 'Pre-Approved')->count();
        $pendingCount = Claim::where('user_id', $currentUserId)->where('status', 'Pending')->count();
        $rejectedCount = Claim::where('user_id', $currentUserId)->where('status', 'Rejected')->count();
        $totalClaimsCount = Claim::where('user_id', $currentUserId)->count();

        $currentYear = Carbon::now()->year;
        $monthlyExpenses = Claim::where('user_id', $currentUserId)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->whereYear('transaction_date', $currentYear)
            ->selectRaw('MONTH(transaction_date) as month, SUM(amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $sparklineValues = [];
        for ($m = 1; $m <= 12; $m++) {
            $sparklineValues[] = isset($monthlyExpenses[$m]) ? (float) $monthlyExpenses[$m] : 0.0;
        }

        $recentClaims = Claim::where('user_id', $currentUserId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $monthlyBudget = 10000.00;
        $currentMonthTotal = Claim::where('user_id', $currentUserId)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->whereMonth('transaction_date', Carbon::now()->month)
            ->whereYear('transaction_date', $currentYear)
            ->sum('amount');

        $percentageUsed = $monthlyBudget > 0 ? min(100, round(($currentMonthTotal / $monthlyBudget) * 100)) : 0;

        $barValues = [];
        for ($m = 1; $m <= 12; $m++) {
            $barValues[] = isset($monthlyExpenses[$m]) ? (float) $monthlyExpenses[$m] : 0.0;
        }

        return view('dashboard', compact(
            'totalSpending',
            'approvedCount',
            'preApprovedCount',
            'pendingCount',
            'rejectedCount',
            'totalClaimsCount',
            'sparklineValues',
            'recentClaims',
            'monthlyBudget',
            'currentMonthTotal',
            'percentageUsed',
            'barValues'
        ));
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

    /**
     * Delegate claim creation form to ClaimSubmissionController.
     */
    public function create()
    {
        return app(ClaimSubmissionController::class)->create();
    }

    /**
     * Delegate claim submission to ClaimSubmissionController.
     */
    public function store(Request $request)
    {
        return app(ClaimSubmissionController::class)->store($request);
    }

    /**
     * Delegate claim history view to ClaimSubmissionController.
     */
    public function history(Request $request)
    {
        return app(ClaimSubmissionController::class)->history($request);
    }

    /**
     * Delegate duplicate checking to ClaimSubmissionController.
     */
    public function checkDuplicate(Request $request)
    {
        return app(ClaimSubmissionController::class)->checkDuplicate($request);
    }

    /**
     * Delegate asynchronous receipt OCR scan to ClaimSubmissionController.
     */
    public function asyncScan(Request $request)
    {
        return app(ClaimSubmissionController::class)->asyncScan($request, app(ReceiptOcrService::class));
    }

    /**
     * Display Finance Auditor dashboard.
     */
    public function financeIndex()
    {
        $claims = Claim::with(['items', 'user', 'auditLogs.user'])->orderBy('created_at', 'desc')->get();

        $pendingCount = Claim::where('status', 'Pending')->count();
        $preApprovedCount = Claim::where('status', 'Pre-Approved')->count();
        $approvedCount = Claim::where('status', 'Approved')->count();
        $reimbursedCount = Claim::where('status', 'Reimbursed')->count();
        $rejectedCount = Claim::where('status', 'Rejected')->count();

        $totalApprovedFundsCount = Claim::whereIn('status', ['Approved', 'Reimbursed'])->count();
        $totalApprovedFundsSum = Claim::whereIn('status', ['Approved', 'Reimbursed'])->sum('amount');

        $currentYear = Carbon::now()->year;
        $monthlyExpenses = DB::table('claims')
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('SUM(amount) as total_amount'))
            ->whereYear('created_at', $currentYear)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy('month', 'asc')
            ->pluck('total_amount', 'month')
            ->toArray();

        $dashboardLineData = [];
        for ($m = 1; $m <= 12; $m++) {
            $dashboardLineData[] = isset($monthlyExpenses[$m]) ? (float) $monthlyExpenses[$m] : 0.0;
        }

        return view('finance.dashboard', compact(
            'claims',
            'pendingCount',
            'preApprovedCount',
            'approvedCount',
            'reimbursedCount',
            'totalApprovedFundsCount',
            'totalApprovedFundsSum',
            'rejectedCount',
            'dashboardLineData'
        ));
    }

    /**
     * Delegate auditing desk to ClaimAuditingController.
     */
    public function auditingIndex(Request $request)
    {
        return app(ClaimAuditingController::class)->index($request);
    }

    /**
     * Display Executive Manager dashboard.
     */
    public function managerIndex()
    {
        $preApprovedCount = Claim::where('status', 'Pre-Approved')->count();
        $approvedCount = Claim::where('status', 'Approved')->count();
        $rejectedCount = Claim::where('status', 'Rejected')->count();
        $totalReviewCount = Claim::count();

        $monthlyExpenditures = array_fill(1, 12, 0);

        $realClaimsData = Claim::where('status', 'Approved')
            ->whereYear('transaction_date', date('Y'))
            ->selectRaw('MONTH(transaction_date) as month, SUM(amount) as total_amount')
            ->groupBy('month')
            ->pluck('total_amount', 'month')
            ->toArray();

        foreach ($realClaimsData as $monthNum => $totalSum) {
            $monthlyExpenditures[$monthNum] = (float) $totalSum;
        }

        $managerLineData = array_values($monthlyExpenditures);

        return view('manager.dashboard', compact(
            'preApprovedCount',
            'approvedCount',
            'rejectedCount',
            'totalReviewCount',
            'managerLineData'
        ));
    }

    /**
     * Delegate manager verification desk to ClaimVerificationController.
     */
    public function managerVerificationIndex(Request $request)
    {
        return app(ClaimVerificationController::class)->index($request);
    }

    /**
     * Delegate status updates to ClaimVerificationController.
     */
    public function updateStatus(Request $request, $id)
    {
        return app(ClaimVerificationController::class)->updateStatus($request, $id);
    }

    /**
     * View company policies and allowance ceilings.
     */
    public function policyIndex()
    {
        $mileageRates = collect();
        if (class_exists(MileageRate::class)) {
            $carRate = MileageRate::whereRaw('LOWER(vehicle_type) = ?', ['car'])->latest()->first();
            $motorRate = MileageRate::whereRaw('LOWER(vehicle_type) = ?', ['motorcycle'])->latest()->first();

            if ($carRate) {
                $mileageRates->push($carRate);
            }
            if ($motorRate) {
                $mileageRates->push($motorRate);
            }
        }

        $expensePolicies = class_exists(ExpensePolicy::class)
            ? ExpensePolicy::where('is_active', true)->get()
            : collect();

        return view('policy.index', compact('expensePolicies', 'mileageRates'));
    }

    /**
     * Staff profile view.
     */
    public function profileIndex()
    {
        $user = auth()->user();
        return view('profile.index', compact('user'));
    }

    /**
     * Update banking and profile attributes.
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $user->id,
            'bank_name' => 'sometimes|required|string|max:100',
            'bank_account_no' => 'sometimes|required|string|max:50',
            'bank_account_holder' => 'sometimes|required|string|max:150',
            'phone_number' => 'sometimes|nullable|string|max:20',
        ]);

        $user->update($validated);

        return redirect()->back()->with('success', 'Banking and profile particulars successfully updated.');
    }

    /**
     * Update account password.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = auth()->user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        try {
            EmailDeliveryService::sendMailable($user->email, new PasswordResetSuccessMail($user));
        } catch (\Throwable $e) {
            Log::warning("Failed to send password update confirmation email to {$user->email}: " . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Account password successfully updated.');
    }

    /**
     * Mileage rates settings view.
     */
    public function mileageRatesIndex()
    {
        return view('manager.mileage_rates');
    }

    /**
     * Expense categories settings view.
     */
    public function expenseCategoriesIndex()
    {
        return view('manager.expense_categories');
    }

    /**
     * User management index view.
     */
    public function userManagementIndex()
    {
        $users = User::orderBy('name', 'asc')->get();
        return view('manager.user_management', compact('users'));
    }

    /**
     * Toggle active status of corporate user.
     */
    public function toggleUserStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->user_id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot deactivate your own account.');
        }

        $oldStatus = $user->is_active;
        $user->is_active = !$user->is_active;
        $user->save();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $user->is_active ? 'Activated User' : 'Deactivated User',
            'model_type' => 'User',
            'model_id' => $user->user_id,
            'old_values' => json_encode(['is_active' => (bool) $oldStatus]),
            'new_values' => json_encode(['is_active' => (bool) $user->is_active]),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'event_category' => 'USER_MANAGEMENT'
        ]);

        return redirect()->back()->with('success', 'User status updated successfully.');
    }

    /**
     * Finance staff directory view.
     */
    public function financeStaffDirectoryIndex()
    {
        $staffMembers = User::where('role', 'staff')
            ->orWhereNull('role')
            ->orderBy('name', 'asc')
            ->get();

        return view('finance.staff_directory', compact('staffMembers'));
    }

    /**
     * System & financial audit log search index.
     */
    public function auditLogsIndex(Request $request)
    {
        $categoryFilter = $request->input('category');
        $searchQuery = $request->input('search');

        $query = AuditLog::with('user');

        if ($categoryFilter) {
            if ($categoryFilter === 'claims') {
                $query->where(function ($q) {
                    $q->where('event_category', 'CLAIMS')
                        ->orWhere('event_category', 'CLAIM')
                        ->orWhere('model_type', 'like', '%Claim%')
                        ->orWhere('action', 'like', '%Status changed%')
                        ->orWhere('action', 'like', '%FRAUD%');
                });
            } elseif ($categoryFilter === 'system' || $categoryFilter === 'config') {
                $query->where(function ($q) {
                    $q->where('event_category', 'CONFIG')
                        ->orWhereIn('model_type', ['MileageRate', 'Vehicle', 'Category', 'ExpenseCategory'])
                        ->orWhereIn('event_category', ['MILEAGE_CONFIG', 'CATEGORY', 'VEHICLE']);
                });
            } elseif ($categoryFilter === 'security') {
                $query->where(function ($q) {
                    $q->where('event_category', 'SECURITY')
                        ->orWhere('action', 'like', '%LOGIN%')
                        ->orWhere('action', 'like', '%AUTH%')
                        ->orWhere('action', 'like', '%PASSWORD%');
                });
            }
        }

        if ($searchQuery) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('action', 'like', "%{$searchQuery}%")
                    ->orWhere('model_type', 'like', "%{$searchQuery}%")
                    ->orWhereHas('user', function ($userQ) use ($searchQuery) {
                        $userQ->where('name', 'like', "%{$searchQuery}%")
                            ->orWhere('role', 'like', "%{$searchQuery}%");
                    });
            });
        }

        $auditLogs = $query->latest()->paginate(20)->withQueryString();

        return view('manager.audit_logs', compact('auditLogs'));
    }

    /**
     * Finance profile statistics view.
     */
    public function financeProfileIndex()
    {
        $totalBatchesSettled = Claim::where('status', 'Reimbursed')->count();
        $recentVolume = Claim::where('status', 'Reimbursed')
            ->whereMonth('updated_at', now()->month)
            ->sum('amount');

        return view('finance.profile', compact('totalBatchesSettled', 'recentVolume'));
    }

    /**
     * Compute organization-wide transaction records for Finance BI statistics.
     */
    public function financeReportsIndex(Request $request)
    {
        $year = $request->input('year', date('Y'));

        $totalApprovedFundsRM = Claim::whereYear('created_at', $year)->whereIn('status', ['Approved', 'Reimbursed'])->sum('amount');
        $totalPendingFundsRM = Claim::whereYear('created_at', $year)->whereIn('status', ['Pending', 'Pre-Approved'])->sum('amount');
        $totalProcessedCount = Claim::whereYear('created_at', $year)->whereIn('status', ['Approved', 'Reimbursed'])->count();

        $monthlyExpenses = DB::table('claims')
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('SUM(amount) as total_amount'))
            ->whereYear('created_at', $year)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->pluck('total_amount', 'month')
            ->toArray();

        $chartLineData = [];
        for ($m = 1; $m <= 12; $m++) {
            $chartLineData[] = isset($monthlyExpenses[$m]) ? (float) $monthlyExpenses[$m] : 0.0;
        }

        $merchantTraffic = DB::table('claims')
            ->select('merchant_name', DB::raw('count(*) as total_claims'), DB::raw('SUM(amount) as total_spent'))
            ->whereYear('created_at', $year)
            ->whereNotNull('merchant_name')
            ->where('merchant_name', '!=', '')
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->groupBy('merchant_name')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->get();

        $merchantLabels = $merchantTraffic->pluck('merchant_name')->toArray();
        $merchantCounts = $merchantTraffic->pluck('total_spent')->map(fn($val) => (float) $val)->toArray();

        $categoryBreakdown = Claim::whereYear('created_at', $year)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->select('predicted_category', DB::raw('SUM(amount) as total'))
            ->groupBy('predicted_category')
            ->get();

        $categoryLabels = $categoryBreakdown->pluck('predicted_category')->toArray();
        $categoryTotals = $categoryBreakdown->pluck('total')->map(fn($val) => (float) $val)->toArray();

        return view('finance.reports', compact(
            'year',
            'totalApprovedFundsRM',
            'totalPendingFundsRM',
            'totalProcessedCount',
            'chartLineData',
            'merchantLabels',
            'merchantCounts',
            'categoryLabels',
            'categoryTotals'
        ));
    }

    /**
     * Compute organization-wide management reports.
     */
    public function managerReportsIndex(Request $request)
    {
        $year = $request->input('year', date('Y'));

        $pendingCount = Claim::where('status', 'Pending')->count();
        $preApprovedCount = Claim::where('status', 'Pre-Approved')->count();
        $totalClaims = Claim::whereYear('created_at', $year)->count();

        $totalApprovedRM = (float) Claim::whereYear('created_at', $year)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->sum('amount');

        $totalPendingRM = (float) Claim::whereYear('created_at', $year)
            ->whereIn('status', ['Pending', 'Pre-Approved', 'Approved'])
            ->sum('amount');

        $totalApprovedCount = Claim::whereYear('created_at', $year)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->count();

        $avgClaim = Claim::whereYear('created_at', $year)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->avg('amount') ?? 0.0;

        $monthlyClaims = Claim::whereYear('created_at', $year)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->selectRaw('MONTH(created_at) as month, SUM(amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyData[] = (float) ($monthlyClaims[$m] ?? 0.00);
        }

        $categoryBreakdown = Claim::whereYear('created_at', $year)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->select(
                DB::raw('COALESCE(predicted_category, "General") as category_name'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('category_name')
            ->get();

        $categoryLabels = $categoryBreakdown->pluck('category_name')->toArray();
        $categoryTotals = $categoryBreakdown->pluck('total')->map(fn($val) => (float) $val)->toArray();

        if (empty($categoryLabels)) {
            $categoryLabels = ['Meals & Entertainment', 'Fuel / Automotive', 'Office Supplies'];
            $categoryTotals = [0, 0, 0];
        }

        $topStaff = Claim::whereYear('created_at', $year)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->whereNotNull('user_id')
            ->with(['user', 'items'])
            ->select(
                'user_id',
                DB::raw('SUM(amount) as total_spent'),
                DB::raw('COUNT(*) as total_claims')
            )
            ->groupBy('user_id')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get()
            ->map(function ($item) use ($year) {
                $staffClaims = Claim::where('user_id', $item->user_id)
                    ->whereYear('created_at', $year)
                    ->whereIn('status', ['Approved', 'Reimbursed'])
                    ->with('items')
                    ->orderBy('created_at', 'desc')
                    ->get();

                return (object) [
                    'user_id' => $item->user_id,
                    'name' => $item->user->name ?? 'Unknown Staff',
                    'user_role' => $item->user->role ?? 'Staff',
                    'total_spent' => (float) $item->total_spent,
                    'total_claims' => $item->total_claims,
                    'claims_list' => $staffClaims
                ];
            });

        $merchantData = Claim::whereYear('created_at', $year)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->whereNotNull('merchant_name')
            ->select('merchant_name', DB::raw('SUM(amount) as total_spend'))
            ->groupBy('merchant_name')
            ->orderByDesc('total_spend')
            ->limit(6)
            ->get();

        $recentClaims = Claim::with('user')->orderBy('created_at', 'desc')->limit(8)->get();

        return view('manager.reports', compact(
            'year',
            'pendingCount',
            'preApprovedCount',
            'totalClaims',
            'totalApprovedRM',
            'totalPendingRM',
            'totalApprovedCount',
            'avgClaim',
            'monthlyData',
            'categoryLabels',
            'categoryTotals',
            'topStaff',
            'merchantData',
            'recentClaims'
        ));
    }

    /**
     * Manager profile summary view.
     */
    public function managerProfileIndex()
    {
        $pendingCount = Claim::where('status', 'Pending')->count();
        $preApprovedCount = Claim::where('status', 'Pre-Approved')->count();
        $totalReviewCount = Claim::count();
        $approvalsThisMonth = Claim::where('status', 'Approved')->whereMonth('updated_at', now()->month)->count();
        $policiesConfigured = ExpensePolicy::count();
        $totalClaims = Claim::count();
        $totalAmount = Claim::where('status', 'Approved')->sum('amount');
        $avgClaim = Claim::where('status', 'Approved')->avg('amount') ?? 0;

        $merchantData = Claim::where('status', 'Approved')
            ->select('merchant_name', DB::raw('SUM(amount) as total_spend'))
            ->groupBy('merchant_name')
            ->orderByDesc('total_spend')
            ->limit(8)
            ->get();

        $categoryData = Claim::where('status', 'Approved')
            ->select('predicted_category', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('predicted_category')
            ->get();

        $recentClaims = Claim::with('user')->orderBy('created_at', 'desc')->limit(10)->get();

        return view('manager.profile', compact(
            'pendingCount',
            'preApprovedCount',
            'totalReviewCount',
            'approvalsThisMonth',
            'policiesConfigured',
            'totalClaims',
            'totalAmount',
            'avgClaim',
            'merchantData',
            'categoryData',
            'recentClaims'
        ));
    }

    /**
     * Manager vehicles index view.
     */
    public function vehiclesIndex()
    {
        return view('manager.vehicles');
    }

    /**
     * Procurement Price Intelligence & Cross-Merchant Price Comparison Engine.
     */
    public function priceIntelligenceIndex(Request $request)
    {
        $search = $request->input('search');

        $itemsQuery = DB::table('claim_items')
            ->join('claims', 'claim_items.claim_id', '=', 'claims.claim_id')
            ->select(
                'claim_items.item_name',
                'claims.merchant_name',
                DB::raw('AVG(claim_items.unit_price) as avg_price'),
                DB::raw('MIN(claim_items.unit_price) as min_price'),
                DB::raw('MAX(claim_items.unit_price) as max_price'),
                DB::raw('COUNT(*) as purchase_count'),
                DB::raw('MAX(claims.transaction_date) as last_purchased_date')
            )
            ->whereNotNull('claims.merchant_name')
            ->when($search, fn($q) => $q->where('claim_items.item_name', 'like', "%{$search}%"))
            ->groupBy('claim_items.item_name', 'claims.merchant_name')
            ->orderBy('claim_items.item_name')
            ->get();

        $comparisonData = [];
        $grouped = $itemsQuery->groupBy('item_name');

        foreach ($grouped as $itemName => $merchants) {
            $sortedByPrice = $merchants->sortBy('avg_price');
            $cheapest = $sortedByPrice->first();
            $mostExpensive = $sortedByPrice->last();
            $priceDiff = $mostExpensive->avg_price - $cheapest->avg_price;
            $savingsPercent = $mostExpensive->avg_price > 0
                ? round(($priceDiff / $mostExpensive->avg_price) * 100)
                : 0;

            $comparisonData[] = (object) [
                'item_name' => $itemName,
                'total_purchases' => $merchants->sum('purchase_count'),
                'cheapest_merchant' => $cheapest->merchant_name,
                'cheapest_price' => $cheapest->avg_price,
                'expensive_merchant' => $mostExpensive->merchant_name,
                'expensive_price' => $mostExpensive->avg_price,
                'potential_savings_pct' => $savingsPercent,
                'merchant_breakdown' => $merchants
            ];
        }

        $topFrequentItems = DB::table('claim_items')
            ->select('item_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_spend'))
            ->groupBy('item_name')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        return view('manager.price_intelligence', compact('comparisonData', 'topFrequentItems', 'search'));
    }

    /**
     * Delegate payment disbursement desk to DisbursementController.
     */
    public function financeDisbursementIndex(Request $request)
    {
        return app(DisbursementController::class)->index($request);
    }

    /**
     * Delegate voucher payment processing to DisbursementController.
     */
    public function processDisbursement(Request $request, $id)
    {
        return app(DisbursementController::class)->processDisbursement($request, $id);
    }

    /**
     * Delegate payment slip scanning to DisbursementController.
     */
    public function asyncScanBankSlip(Request $request)
    {
        return app(DisbursementController::class)->asyncScanBankSlip($request, app(ReceiptOcrService::class));
    }

    /**
     * Delegate batch disbursement to DisbursementController.
     */
    public function processBatchDisbursement(Request $request)
    {
        return app(DisbursementController::class)->processBatchDisbursement($request);
    }

    /**
     * Delegate PDF voucher export to ExportController.
     */
    public function downloadPdfVoucher($id)
    {
        return app(ExportController::class)->downloadVoucherPdf($id);
    }

    /**
     * Delegate secure file serving to FileAccessController.
     */
    public function serveFile($folder, $filename)
    {
        return app(FileAccessController::class)->serveFile($folder, $filename);
    }

    /**
     * Delegate SLA turnaround analytics to ClaimVerificationController.
     */
    public function slaAnalytics(SlaTrackingService $slaTrackingService)
    {
        return app(ClaimVerificationController::class)->slaAnalytics($slaTrackingService);
    }

    /**
     * Delegate claim editing to ClaimSubmissionController.
     */
    public function edit($id)
    {
        return app(ClaimSubmissionController::class)->edit($id);
    }

    /**
     * Delegate claim resubmission to ClaimSubmissionController.
     */
    public function resubmit(Request $request, $id)
    {
        return app(ClaimSubmissionController::class)->resubmit($request, $id);
    }

    /**
     * Delegate claim withdrawal to ClaimSubmissionController.
     */
    public function withdraw(Request $request, $id)
    {
        return app(ClaimSubmissionController::class)->withdraw($request, $id);
    }
}
