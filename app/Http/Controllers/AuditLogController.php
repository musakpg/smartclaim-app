<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
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
}
