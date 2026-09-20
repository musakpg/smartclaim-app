<?php

namespace App\Services;

use App\Models\Claim;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FraudDetectionService
{
    /**
     * Evaluate multi-parameter risk scoring matrix (0 to 100%).
     * Returns: ['score' => int, 'flags' => array, 'image_hash' => string|null, 'exif_date' => string|null]
     */
    public static function evaluateClaimRisk($imageFile, float $userAmount, ?float $ocrAmount, string $transactionDate, ?int $currentClaimId = null): array
    {
        $riskScore = 0;
        $flags = [];
        $imageHash = null;
        $exifDate = null;

        // 1. Compute SHA-256 Image File Hash & Check Duplicate Image
        if ($imageFile && file_exists($imageFile->getRealPath())) {
            $imageHash = hash_file('sha256', $imageFile->getRealPath());

            $duplicateImageQuery = Claim::where('receipt_image_hash', $imageHash);
            if ($currentClaimId) {
                $duplicateImageQuery->where('claim_id', '!=', $currentClaimId);
            }

            if ($duplicateImageQuery->exists()) {
                $riskScore += 45;
                $flags[] = "Duplicate Image Hash: Exact identical receipt photo already used in prior claim.";
            }

            // 2. Extract EXIF Camera Metadata Date
            try {
                if (function_exists('exif_read_data') && in_array(strtolower($imageFile->getClientOriginalExtension()), ['jpg', 'jpeg'])) {
                    $exif = @exif_read_data($imageFile->getRealPath());
                    if (!empty($exif['DateTimeOriginal'])) {
                        $exifDate = Carbon::parse($exif['DateTimeOriginal'])->format('Y-m-d H:i:s');
                        $diffInDays = abs(Carbon::parse($exifDate)->diffInDays(Carbon::parse($transactionDate)));

                        if ($diffInDays > 60) {
                            $riskScore += 25;
                            $flags[] = "EXIF Anomaly: Photo capture date ({$exifDate}) differs by {$diffInDays} days from receipt date.";
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("EXIF extraction skipped: " . $e->getMessage());
            }
        }

        // 3. Amount Discrepancy (User manual input vs OCR detected total)
        if ($ocrAmount && $ocrAmount > 0.00) {
            if ($userAmount > ($ocrAmount * 1.15)) {
                $diff = $userAmount - $ocrAmount;
                $riskScore += 30;
                $flags[] = "Amount Inflation: User submitted RM " . number_format($userAmount, 2) . " but OCR read RM " . number_format($ocrAmount, 2) . " (+RM " . number_format($diff, 2) . ").";
            }
        }

        // 4. Weekend / Non-Working Day Anomaly
        $claimDay = Carbon::parse($transactionDate);
        if ($claimDay->isWeekend()) {
            $riskScore += 10;
            $flags[] = "Weekend Activity: Expense occurred on a weekend (" . $claimDay->format('l') . ").";
        }

        // Bound final score between 0 and 100
        $finalScore = min(100, $riskScore);

        return [
            'score' => $finalScore,
            'flags' => $flags,
            'image_hash' => $imageHash,
            'exif_date' => $exifDate,
        ];
    }
}