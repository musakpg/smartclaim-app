<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Render the dynamic dashboard metrics and charts populated from corporate user database logs.
     */
    public function index()
    {
        $currentUser = Auth::user();
        if (!$currentUser) {
            abort(401, 'Unauthenticated.');
        }
        $userId = $currentUser->user_id ?? $currentUser->id;

        // =================================================================
        // 1. MULTI-LEVEL STATUS CARD COUNTS
        // =================================================================
        $reimbursedCount = DB::table('claims')
            ->where('user_id', $userId)
            ->where('status', 'Reimbursed')
            ->count();

        $approvedCount = DB::table('claims')
            ->where('user_id', $userId)
            ->where('status', 'Approved')
            ->count();

        $preApprovedCount = DB::table('claims')
            ->where('user_id', $userId)
            ->where('status', 'Pre-Approved')
            ->count();

        $pendingCount = DB::table('claims')
            ->where('user_id', $userId)
            ->where('status', 'Pending')
            ->count();

        $rejectedCount = DB::table('claims')
            ->where('user_id', $userId)
            ->where('status', 'Rejected')
            ->count();

        $totalDisbursedAmount = (float) DB::table('claims')
            ->where('user_id', $userId)
            ->where('status', 'Reimbursed')
            ->sum('amount');

        // =================================================================
        // 2. LINE CHART DATA (MONTHLY APPROVED & REIMBURSED SPENDING)
        // =================================================================
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

        // =================================================================
        // 3. BAR CHART DATA (CATEGORY DISTRIBUTION)
        // =================================================================
        $categoryDistribution = DB::table('claims')
            ->select('predicted_category as category', DB::raw('SUM(amount) as total'))
            ->where('user_id', $userId)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->groupBy('predicted_category')
            ->orderBy('total', 'desc')
            ->get();

        $barLabels = [];
        $barValues = [];
        foreach ($categoryDistribution as $dist) {
            $barLabels[] = $dist->category ?? 'General';
            $barValues[] = (float) $dist->total;
        }

        if (empty($barLabels)) {
            $barLabels = ['Meals & Entertainment', 'Fuel / Automotive', 'Office Supplies'];
            $barValues = [0, 0, 0];
        }

        // =================================================================
        // 4. MONTHLY ENTITLEMENT TRACKER (CURRENT MONTH SPEND BY CATEGORY)
        // =================================================================
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

        // =================================================================
        // 5. RECENT CLAIMS FOR THIS LOGGED-IN STAFF
        // =================================================================
        $recentClaims = \App\Models\Claim::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('dashboard', [
            'totalDisbursedAmount' => $totalDisbursedAmount,
            'reimbursedCount' => $reimbursedCount,
            'approvedCount' => $approvedCount,
            'preApprovedCount' => $preApprovedCount,
            'pendingCount' => $pendingCount,
            'rejectedCount' => $rejectedCount,
            'lineChartData' => $lineChartData,
            'barLabels' => $barLabels,
            'barValues' => $barValues,
            'recentClaims' => $recentClaims,
            'monthlyCategorySpend' => $monthlyCategorySpend,
            'expensePolicies' => $expensePolicies
        ]);
    }
}