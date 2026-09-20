<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\ExpensePolicy;
use Carbon\Carbon;

class BudgetEnforcementService
{
    /**
     * Check if a claim breaches monthly or single-claim policy limits.
     * Returns: ['is_violation' => bool, 'reason' => string|null, 'current_month_spent' => float, 'monthly_cap' => float]
     */
    public static function checkPolicy(int $userId, string $category, float $amount): array
    {
        $policy = ExpensePolicy::where('category_name', $category)
            ->where('is_active', true)
            ->first();

        if (!$policy) {
            return [
                'is_violation' => false,
                'reason' => null,
                'current_month_spent' => 0.00,
                'monthly_cap' => 0.00
            ];
        }

        // 1. Semak Had Siling Resit Tunggal (Single Receipt Max Limit)
        if ($amount > (float) $policy->max_single_claim_limit) {
            return [
                'is_violation' => true,
                'reason' => "Exceeded single claim ceiling (Max: RM" . number_format($policy->max_single_claim_limit, 2) . ")",
                'current_month_spent' => 0.00,
                'monthly_cap' => (float) $policy->monthly_budget_cap
            ];
        }

        // 2. Evaluate Monthly Category Budget Cap for Staff Claimant
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $currentMonthSpent = (float) Claim::where('user_id', $userId)
            ->where('predicted_category', $category) // Removed non-existent 'expense_category' column
            ->whereIn('status', ['Approved', 'Pre-Approved', 'Submitted', 'Pending'])
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $projectedTotal = $currentMonthSpent + $amount;

        if ($projectedTotal > (float) $policy->monthly_budget_cap) {
            $overBy = $projectedTotal - (float) $policy->monthly_budget_cap;
            return [
                'is_violation' => true,
                'reason' => "Exceeded monthly {$category} quota by RM" . number_format($overBy, 2) . " (Cap: RM" . number_format($policy->monthly_budget_cap, 2) . ")",
                'current_month_spent' => $currentMonthSpent,
                'monthly_cap' => (float) $policy->monthly_budget_cap
            ];
        }

        return [
            'is_violation' => false,
            'reason' => null,
            'current_month_spent' => $currentMonthSpent,
            'monthly_cap' => (float) $policy->monthly_budget_cap
        ];
    }
}