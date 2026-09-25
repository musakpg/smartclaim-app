<?php

namespace App\Services;

use App\Models\Claim;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SlaTrackingService
{
    /**
     * Define the SLA thresholds in hours based on corporate policy.
     */
    const MANAGER_SLA_HOURS = 48; // Manager Pre-Approval SLA
    const FINANCE_SLA_HOURS = 72; // Finance Disbursement SLA

    /**
     * Calculates historical average manager turnaround time (submission -> approval).
     *
     * @return float Average hours
     */
    public function getAverageManagerTurnaroundTime(): float
    {
        return Cache::remember('sla.avg_manager_tat', 60, function () {
            // Find all claims that passed Manager approval phase
            $claims = Claim::whereIn('status', ['Approved', 'Disbursed', 'Rejected'])->get();
            if ($claims->isEmpty()) {
                return self::MANAGER_SLA_HOURS / 2; // Default baseline assumption
            }

            $totalHours = 0;
            $count = 0;

            foreach ($claims as $claim) {
                // Find submission log and manager review log
                $submitLog = $claim->auditLogs()->where('action', 'Claim Submitted')->first();
                $reviewLog = $claim->auditLogs()->whereIn('action', ['Manager Approved', 'Claim Rejected'])->first();

                if ($submitLog && $reviewLog) {
                    $hours = $submitLog->created_at->diffInHours($reviewLog->created_at);
                    $totalHours += $hours;
                    $count++;
                }
            }

            return $count > 0 ? round($totalHours / $count, 1) : self::MANAGER_SLA_HOURS / 2;
        });
    }

    /**
     * Calculates historical average finance settlement time (manager approval -> disbursement).
     *
     * @return float Average hours
     */
    public function getAverageFinanceSettlementTime(): float
    {
        return Cache::remember('sla.avg_finance_tat', 60, function () {
            $claims = Claim::where('status', 'Disbursed')->get();
            if ($claims->isEmpty()) {
                return self::FINANCE_SLA_HOURS / 2;
            }

            $totalHours = 0;
            $count = 0;

            foreach ($claims as $claim) {
                $reviewLog = $claim->auditLogs()->where('action', 'Manager Approved')->first();
                $disburseLog = $claim->auditLogs()->where('action', 'Claim Disbursed')->first();

                if ($reviewLog && $disburseLog) {
                    $hours = $reviewLog->created_at->diffInHours($disburseLog->created_at);
                    $totalHours += $hours;
                    $count++;
                }
            }

            return $count > 0 ? round($totalHours / $count, 1) : self::FINANCE_SLA_HOURS / 2;
        });
    }

    /**
     * Get the dynamically projected estimated completion date for a claim.
     */
    /**
     * Get the dynamically projected estimated completion date for a claim.
     */
    public function getEstimatedCompletionAt(Claim $claim): ?Carbon
    {
        if (in_array($claim->status, ['Disbursed', 'Reimbursed', 'Rejected', 'Cancelled'])) {
            return $claim->updated_at;
        }

        if (in_array($claim->status, ['Submitted', 'Pending', 'Pre-Approved', 'Pending Manager'])) {
            // Projected time: created_at + avg manager TAT + avg finance TAT
            $avgTotalHours = $this->getAverageManagerTurnaroundTime() + $this->getAverageFinanceSettlementTime();
            return $claim->created_at->copy()->addHours($avgTotalHours);
        }

        if ($claim->status === 'Approved' || $claim->status === 'Pending Finance') {
            // Projected time: manager approved at + avg finance TAT
            $reviewLog = $claim->auditLogs()->whereIn('action', ['Manager Approved', 'CLAIM_Approved', 'Status changed to Approved'])->first();
            $baseTime = $reviewLog ? $reviewLog->created_at : $claim->updated_at;
            
            return $baseTime->copy()->addHours($this->getAverageFinanceSettlementTime());
        }

        return null;
    }

    /**
     * Get SLA Status for a claim.
     */
    public function getSlaStatus(Claim $claim): string
    {
        if (in_array($claim->status, ['Disbursed', 'Reimbursed', 'Rejected', 'Cancelled'])) {
            return 'resolved';
        }

        $now = Carbon::now();
        $targetDate = $this->getSlaTargetDate($claim);

        if (!$targetDate) {
            return 'unknown';
        }

        $hoursRemaining = $now->diffInHours($targetDate, false);

        if ($hoursRemaining < 0) {
            return 'breached';
        } elseif ($hoursRemaining <= 12) {
            return 'approaching_deadline';
        }

        return 'on_track';
    }
    
    /**
     * Calculate absolute SLA deadline based on policy standard (not historical average).
     */
    public function getSlaTargetDate(Claim $claim): ?Carbon
    {
        if (in_array($claim->status, ['Submitted', 'Pending', 'Pre-Approved', 'Pending Manager'])) {
            return $claim->created_at->copy()->addHours(self::MANAGER_SLA_HOURS);
        }

        if ($claim->status === 'Approved' || $claim->status === 'Pending Finance') {
            $reviewLog = $claim->auditLogs()->whereIn('action', ['Manager Approved', 'CLAIM_Approved', 'Status changed to Approved'])->first();
            $baseTime = $reviewLog ? $reviewLog->created_at : $claim->updated_at;
            return $baseTime->copy()->addHours(self::FINANCE_SLA_HOURS);
        }

        return null;
    }

    /**
     * Format time remaining as human readable string.
     */
    public function getTimeRemainingHuman(Claim $claim): string
    {
        if (in_array($claim->status, ['Disbursed', 'Reimbursed', 'Rejected', 'Cancelled'])) {
            $completionLog = $claim->auditLogs()->whereIn('action', ['Claim Disbursed', 'Claim Rejected', 'CLAIM_Rejected', 'Status changed to Reimbursed'])->first();
            $baseTime = $completionLog ? $completionLog->created_at : $claim->updated_at;
            
            $days = $claim->created_at->diffInDays($baseTime);
            return $days == 0 ? "Completed in < 1 day" : "Completed in {$days} days";
        }

        $targetDate = $this->getSlaTargetDate($claim);
        if (!$targetDate) {
            return "Pending";
        }

        $now = Carbon::now();
        
        if ($now->greaterThan($targetDate)) {
            $diff = $now->diff($targetDate);
            if ($diff->d > 0) {
                return "Overdue by {$diff->d} days, {$diff->h} hours";
            }
            return "Overdue by {$diff->h} hours";
        }

        $diff = $now->diff($targetDate);
        if ($diff->d > 0) {
            return "Est. {$diff->d} days, {$diff->h} hours";
        }
        return "Est. {$diff->h} hours";
    }

    /**
     * Get bottleneck queue list (claims exceeding SLA).
     */
    public function getBottleneckQueue()
    {
        $claims = Claim::whereIn('status', ['Submitted', 'Pending', 'Pre-Approved', 'Pending Manager', 'Approved', 'Pending Finance'])
            ->with(['user', 'auditLogs'])
            ->get();
            
        return $claims->filter(function($claim) {
            return $this->getSlaStatus($claim) === 'breached';
        })->map(function($claim) {
            $target = $this->getSlaTargetDate($claim);
            $now = Carbon::now();
            $claim->hours_overdue = $target ? floor($now->diffInHours($target, true)) : 0;
            $claim->days_in_queue = floor($claim->created_at->diffInDays($now));
            return $claim;
        })->sortByDesc('hours_overdue');
    }
}
