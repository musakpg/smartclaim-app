<?php
namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class ManagerController extends Controller
{
    // FIX UNTUK REPORTS
    public function reports()
    {
        $totalClaims = Claim::count();
        $totalAmount = Claim::where('status', 'Approved')->sum('amount');
        $avgClaim = Claim::avg('amount');

        // Tarik data untuk Chart
        $merchantData = Claim::select('merchant_name', \DB::raw('sum(amount) as total_spend'))
            ->groupBy('merchant_name')->get();
        $categoryData = Claim::select('predicted_category', \DB::raw('sum(amount) as total_amount'))
            ->groupBy('predicted_category')->get();

        // Data untuk Recent Audit Logs
        $allRecentClaims = Claim::with('user')->orderBy('created_at', 'desc')->get();

        return view('manager.reports', compact('totalClaims', 'totalAmount', 'avgClaim', 'merchantData', 'categoryData', 'allRecentClaims'));
    }

    // FIX UNTUK AUDIT LOGS
    public function auditLogs()
    {
        $auditLogs = AuditLog::with('user')->orderBy('created_at', 'desc')->get();
        return view('manager.audit_logs', compact('auditLogs'));
    }
}