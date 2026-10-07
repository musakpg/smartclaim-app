<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\ExpensePolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManagerDashboardController extends Controller
{
    /**
     * Display Executive Manager dashboard overview.
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
}
