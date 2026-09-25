<?php

namespace App\Services;

use App\Models\Claim;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FraudDetectionService
{
    /**
     * Evaluate multi-parameter risk scoring matrix (0 to 100%).
     * Returns: ['score' => int, 'flags' => array, 'image_hash' => string|null, 'exif_date' => string|null]
     */
    public static function evaluateClaimRisk($imageFile, float $userAmount, ?float $ocrAmount, string $transactionDate, ?int $currentClaimId = null, ?string $merchantName = null, ?string $invoiceNo = null, ?string $startLocation = null, ?string $destinationLocation = null, ?float $claimedDistance = null): array
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
                $flags[] = [
                    'flag_type' => 'DUPLICATE_SUSPICION',
                    'severity' => 'critical',
                    'title' => 'Duplicate Image Detected',
                    'description' => 'Exact identical receipt photo already used in prior claim.'
                ];
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
                            $flags[] = [
                                'flag_type' => 'EXIF_ANOMALY',
                                'severity' => 'warning',
                                'title' => 'EXIF Capture Date Discrepancy',
                                'description' => "Photo capture date ({$exifDate}) differs by {$diffInDays} days from receipt date."
                            ];
                        }
                    }
                    if (!empty($exif['Software'])) {
                        $software = strtolower($exif['Software']);
                        if (str_contains($software, 'photoshop') || str_contains($software, 'canva') || str_contains($software, 'gimp') || str_contains($software, 'picsart')) {
                            $riskScore += 100;
                            $flags[] = [
                                'flag_type' => 'IMAGE_TAMPERING',
                                'severity' => 'critical',
                                'title' => 'High-Risk Image Tampering',
                                'description' => "Image metadata indicates modifications by editing software ({$exif['Software']})."
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("EXIF extraction skipped: " . $e->getMessage());
            }
        }

        // 2.5 Composite Matching: Invoice + Merchant + Date
        if (!empty($merchantName) && !empty($invoiceNo)) {
            $duplicateInvoiceQuery = Claim::where('merchant_name', $merchantName)
                ->where('receipt_invoice_no', $invoiceNo)
                ->where('transaction_date', $transactionDate);
            
            if ($currentClaimId) {
                $duplicateInvoiceQuery->where('claim_id', '!=', $currentClaimId);
            }

            if ($duplicateInvoiceQuery->exists()) {
                $riskScore += 50;
                $flags[] = [
                    'flag_type' => 'COMPOSITE_DUPLICATE',
                    'severity' => 'critical',
                    'title' => 'Composite Data Match',
                    'description' => 'Matching Invoice Number, Merchant, and Date found in another claim.'
                ];
            }
        }

        // 3. Amount Discrepancy (User manual input vs OCR detected total)
        if ($ocrAmount && $ocrAmount > 0.00) {
            if ($userAmount > ($ocrAmount * 1.15)) {
                $diff = $userAmount - $ocrAmount;
                $riskScore += 30;
                $flags[] = [
                    'flag_type' => 'AMOUNT_INFLATION',
                    'severity' => 'warning',
                    'title' => 'Amount Inflation',
                    'description' => "User submitted RM " . number_format($userAmount, 2) . " but OCR read RM " . number_format($ocrAmount, 2) . " (+RM " . number_format($diff, 2) . ")."
                ];
            }
        }

        // 4. Weekend / Non-Working Day Anomaly
        $claimDay = Carbon::parse($transactionDate);
        if ($claimDay->isWeekend()) {
            $riskScore += 10;
            $flags[] = [
                'flag_type' => 'WEEKEND_ANOMALY',
                'severity' => 'info',
                'title' => 'Weekend Activity',
                'description' => "Expense occurred on a weekend (" . $claimDay->format('l') . ")."
            ];
        }

        // 5. Mileage Validation (Google Maps Directions API)
        if ($startLocation && $destinationLocation && $claimedDistance) {
            $apiKey = config('services.google.maps_api_key');
            if ($apiKey) {
                try {
                    $response = Http::get('https://maps.googleapis.com/maps/api/directions/json', [
                        'origin' => $startLocation,
                        'destination' => $destinationLocation,
                        'key' => $apiKey
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        if (($data['status'] ?? '') === 'OK' && isset($data['routes'][0]['legs'][0]['distance']['value'])) {
                            $estimatedDistanceMeters = $data['routes'][0]['legs'][0]['distance']['value'];
                            $estimatedDistanceKm = $estimatedDistanceMeters / 1000;
                            
                            $variance = (($claimedDistance - $estimatedDistanceKm) / $estimatedDistanceKm) * 100;
                            
                            if ($variance > 15) {
                                $isCritical = $variance > 30;
                                $riskScore += $isCritical ? 60 : 30;
                                $flags[] = [
                                    'flag_type' => 'MILEAGE_INFLATION',
                                    'severity' => $isCritical ? 'critical' : 'warning',
                                    'title' => 'Mileage Inflation Suspected',
                                    'description' => "Claimed distance ({$claimedDistance} KM) exceeds Google Maps driving route (" . number_format($estimatedDistanceKm, 1) . " KM) by " . number_format($variance, 1) . "%."
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Google Maps API mileage validation failed: " . $e->getMessage());
                }
            }
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