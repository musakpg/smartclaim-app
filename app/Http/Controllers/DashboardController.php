<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Render the dynamic dashboard metrics and charts populated from corporate user database logs.
     */
    public function index()
    {
        // Dynamic active user context tracker
        $userId = auth()->id() ?? 1; 

        // =================================================================
        // 📊 TIER 1: MULTI-LEVEL ADMINISTRATIVE CARD COUNTS
        // =================================================================

        // 1. ACCEPTED APPROVAL: Count the total number of successfully approved claims
        $approvedCount = DB::table('claims')
            ->where('user_id', $userId)
            ->where('status', 'Approved')
            ->count();

        // 2. PRE-ACCEPTED APPROVAL: Count claims that passed Finance but waiting for Manager sign-off
        $preApprovedCount = DB::table('claims')
            ->where('user_id', $userId)
            ->where('status', 'Pre-Approved')
            ->count();

        // 3. PENDING APPROVAL: Count new unverified claims waiting for Finance verification
        $pendingCount = DB::table('claims')
            ->where('user_id', $userId)
            ->where('status', 'Pending')
            ->count();

        // 4. REJECTED APPROVAL: Count claims rejected by either Finance or Manager tiers
        $rejectedCount = DB::table('claims')
            ->where('user_id', $userId)
            ->where('status', 'Rejected')
            ->count();

        // =================================================================
        // 📈 TIER 2: LINE CHART DATA STACK (MONTHLY SPENDING)
        // =================================================================

        // DATA CARTA GARIS: Only SUM amounts where status is explicitly 'Approved'
        $monthlySpending = DB::table('claims')
            ->select(DB::raw('MONTH(transaction_date) as month'), DB::raw('SUM(amount) as total'))
            ->whereYear('transaction_date', 2026)
            ->where('user_id', $userId)
            ->where('status', 'Approved') 
            ->groupBy(DB::raw('MONTH(transaction_date)'))
            ->orderBy('month', 'asc')
            ->get();

        // Build default 12 months array initialized to 0 starting from index 1 (January)
        $lineChartData = array_fill(1, 12, 0);
        foreach ($monthlySpending as $spend) {
            $lineChartData[$spend->month] = (float) $spend->total;
        }
        // Re-index array keys to 0-11 natively so Chart.js reads it without glitches
        $lineChartData = array_values($lineChartData);

        // =================================================================
        // 📊 TIER 3: BAR CHART DATA STACK (CATEGORY DISTRIBUTION)
        // =================================================================

        // DATA CARTA BAR: Only group by categories where status is 'Approved'
        $categoryDistribution = DB::table('claims')
            ->select('predicted_category as category', DB::raw('SUM(amount) as total'))
            ->where('user_id', $userId)
            ->where('status', 'Approved') 
            ->groupBy('predicted_category')
            ->orderBy('total', 'desc')
            ->get();

        $barLabels = [];
        $barValues = [];
        foreach ($categoryDistribution as $dist) {
            $barLabels[] = $dist->category ?? 'Unassigned';
            $barValues[] = (float) $dist->total;
        }

        // Default layout structural fallbacks if data registries are vacant
        if (empty($barLabels)) {
            $barLabels = ['Meals', 'Transport', 'Utilities'];
            $barValues = [0, 0, 0];
        }

        // =================================================================
        // 🚚 TIER 4: DISPATCHING PARAMETERS STRAIGHT INTO BLADE VIEW
        // =================================================================
        return view('dashboard', [
            'approvedCount'    => $approvedCount,
            'preApprovedCount' => $preApprovedCount,
            'pendingCount'     => $pendingCount,
            'rejectedCount'    => $rejectedCount,
            'lineChartData'    => $lineChartData,
            'barLabels'        => $barLabels,
            'barValues'        => $barValues
        ]);
    }
}