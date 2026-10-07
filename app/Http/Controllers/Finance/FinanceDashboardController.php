<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceDashboardController extends Controller
{
    /**
     * Display Finance Auditor dashboard overview.
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
            ->orderBy('month', 'asc')
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
}
