<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StaffDashboardController extends Controller
{
    /**
     * Render the dynamic dashboard metrics, budget tracking, and charts for authenticated staff.
     */
    public function index()
    {
        $currentUser = Auth::user();
        if (!$currentUser) {
            abort(401, 'Unauthenticated.');
        }
        $userId = $currentUser->user_id ?? $currentUser->id;

        // Multi-level status counts
        $reimbursedCount = Claim::where('user_id', $userId)
            ->where('status', 'Reimbursed')
            ->count();

        $approvedCount = Claim::where('user_id', $userId)
            ->where('status', 'Approved')
            ->count();

        $preApprovedCount = Claim::where('user_id', $userId)
            ->where('status', 'Pre-Approved')
            ->count();

        $pendingCount = Claim::where('user_id', $userId)
            ->where('status', 'Pending')
            ->count();

        $rejectedCount = Claim::where('user_id', $userId)
            ->where('status', 'Rejected')
            ->count();

        $totalClaimsCount = Claim::where('user_id', $userId)->count();

        $totalDisbursedAmount = (float) Claim::where('user_id', $userId)
            ->where('status', 'Reimbursed')
            ->sum('amount');

        $totalSpending = (float) Claim::where('user_id', $userId)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->sum('amount');

        // Line chart and sparklines: Monthly spending
        $currentYear = Carbon::now()->year;
        $monthlySpending = DB::table('claims')
            ->select(DB::raw('MONTH(transaction_date) as month'), DB::raw('SUM(amount) as total'))
            ->whereYear('transaction_date', $currentYear)
            ->where('user_id', $userId)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->groupBy(DB::raw('MONTH(transaction_date)'))
            ->orderBy('month', 'asc')
            ->get();

        $lineChartData = array_fill(1, 12, 0);
        foreach ($monthlySpending as $spend) {
            $lineChartData[(int) $spend->month] = (float) $spend->total;
        }
        $lineChartData = array_values($lineChartData);
        $sparklineValues = $lineChartData;
        $barValues = $lineChartData;

        // Category distribution
        $categoryDistribution = DB::table('claims')
            ->select('predicted_category as category', DB::raw('SUM(amount) as total'))
            ->where('user_id', $userId)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->groupBy('predicted_category')
            ->orderBy('total', 'desc')
            ->get();

        $barLabels = [];
        $categoryBarValues = [];
        foreach ($categoryDistribution as $dist) {
            $barLabels[] = $dist->category ?? 'General';
            $categoryBarValues[] = (float) $dist->total;
        }

        if (empty($barLabels)) {
            $barLabels = ['Meals & Entertainment', 'Fuel / Automotive', 'Office Supplies'];
            $categoryBarValues = [0, 0, 0];
        }

        // Monthly entitlement budget tracking
        $currentMonth = Carbon::now()->month;
        $monthlyCategorySpend = DB::table('claims')
            ->select('predicted_category as category', DB::raw('SUM(amount) as total'))
            ->whereYear('transaction_date', $currentYear)
            ->whereMonth('transaction_date', $currentMonth)
            ->where('user_id', $userId)
            ->whereNotIn('status', ['Rejected'])
            ->groupBy('predicted_category')
            ->get()
            ->keyBy('category')
            ->map(fn($item) => (float) $item->total)
            ->toArray();

        $expensePolicies = DB::table('expense_policies')
            ->join('categories', 'expense_policies.category_id', '=', 'categories.id')
            ->select('expense_policies.*', 'categories.name as category_name')
            ->where('expense_policies.is_active', true)
            ->get();

        $monthlyBudget = 10000.00;
        $currentMonthTotal = Claim::where('user_id', $userId)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->whereMonth('transaction_date', $currentMonth)
            ->whereYear('transaction_date', $currentYear)
            ->sum('amount');

        $percentageUsed = $monthlyBudget > 0 ? min(100, round(($currentMonthTotal / $monthlyBudget) * 100)) : 0;

        // Recent claims submitted by this user
        $recentClaims = Claim::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('dashboard', [
            'totalDisbursedAmount' => $totalDisbursedAmount,
            'totalSpending' => $totalSpending,
            'reimbursedCount' => $reimbursedCount,
            'approvedCount' => $approvedCount,
            'preApprovedCount' => $preApprovedCount,
            'pendingCount' => $pendingCount,
            'rejectedCount' => $rejectedCount,
            'totalClaimsCount' => $totalClaimsCount,
            'lineChartData' => $lineChartData,
            'sparklineValues' => $sparklineValues,
            'barLabels' => $barLabels,
            'barValues' => $barValues,
            'recentClaims' => $recentClaims,
            'monthlyCategorySpend' => $monthlyCategorySpend,
            'expensePolicies' => $expensePolicies,
            'monthlyBudget' => $monthlyBudget,
            'currentMonthTotal' => $currentMonthTotal,
            'percentageUsed' => $percentageUsed,
        ]);
    }
}
