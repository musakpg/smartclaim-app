<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Claim;
use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\BatchAnnotateImagesRequest;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\Image;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ReceiptOcrService
{
    /**
     * Process and extract data from an uploaded Malaysian expense receipt.
     *
     * @param UploadedFile $uploadedFile
     * @return array
     */
    public function scanReceipt(UploadedFile $uploadedFile): array
    {
        try {
            $imagePath = $uploadedFile->store('receipts/temp', 'private');
            $fullImagePath = storage_path('app/private/' . $imagePath);
            $imageData = file_get_contents($fullImagePath);
            $extractedText = '';

            // Security Layer 1: Image Hash & Duplication Interception
            $imageHash = hash('sha256', $imageData);
            $existingClaim = Claim::where('receipt_image_hash', $imageHash)->with('user')->first();

            if ($existingClaim) {
                if (file_exists($fullImagePath)) {
                    unlink($fullImagePath);
                }
                return [
                    'success' => false,
                    'is_duplicate_image' => true,
                    'duplicate_reason' => "Security Interception: This exact physical receipt was already submitted by " . ($existingClaim->user->name ?? 'another staff') . " on " . $existingClaim->created_at->format('d M Y') . " (Voucher #CLM-{$existingClaim->claim_id}). Please upload an authentic receipt."
                ];
            }

            // Security Layer 2: Exif Tamper Detection
            $tamperReason = $this->detectExifTampering($fullImagePath);
            if ($tamperReason) {
                if (file_exists($fullImagePath)) {
                    unlink($fullImagePath);
                }
                return [
                    'success' => false,
                    'is_tampered' => true,
                    'tamper_reason' => $tamperReason
                ];
            }

            // Demo Mode Bypass: Avoid consuming live Google Vision API quota
            if (auth()->check() && (bool) (auth()->user()->is_demo ?? false)) {
                if (file_exists($fullImagePath)) {
                    unlink($fullImagePath);
                }
                return [
                    'success' => true,
                    'receipt_image_hash' => $imageHash,
                    'merchant_name' => 'Kedai Runcit Mulia Sejati Baru',
                    'location_address' => 'Lot 6061 Jalan Imam, Kg Sg Ramal Dalam, 43000 Kajang, Selangor',
                    'receipt_invoice_no' => 'INV-DEMO-' . rand(1000, 9999),
                    'transaction_date' => now()->toDateString(),
                    'amount' => 100.20,
                    'tax_amount' => 0.00,
                    'payment_method' => 'Cash',
                    'predicted_category' => 'Office Pantry & Amenities (Groceries, Supplies)',
                    'items' => [
                        ['item_name' => 'AYAM SEGAR', 'quantity' => 1, 'unit_price' => 61.70, 'subtotal' => 61.70],
                        ['item_name' => 'BARANG DAPUR (KECIL)', 'quantity' => 3, 'unit_price' => 3.00, 'subtotal' => 9.00],
                        ['item_name' => 'SABUN PENCUCI', 'quantity' => 1, 'unit_price' => 5.30, 'subtotal' => 5.30],
                        ['item_name' => 'TISU DAPUR GULUNG', 'quantity' => 1, 'unit_price' => 4.90, 'subtotal' => 4.90],
                        ['item_name' => 'MINYAK MASAK 2KG', 'quantity' => 1, 'unit_price' => 8.80, 'subtotal' => 8.80],
                        ['item_name' => 'SPONGE CUCI PINGGAN', 'quantity' => 3, 'unit_price' => 3.50, 'subtotal' => 10.50],
                    ],
                    'raw_text' => "KEDAI RUNCIT MULIA SEJATI BARU\nLOT 6061 JALAN IMAM, KAJANG\nTOTAL CASH: RM 100.20\nTERIMA KASIH"
                ];
            }

            // Call Google Cloud Vision API
            $apiKey = config('services.google_vision.api_key');
            if (!empty($apiKey)) {
                $extractedText = $this->callVisionApi($imageData, $apiKey);
            }

            // SDK Annotator Client Fallback
            if (empty($extractedText)) {
                $extractedText = $this->callVisionSdkFallback($imageData);
            }

            if (file_exists($fullImagePath)) {
                unlink($fullImagePath);
            }

            if (empty($extractedText)) {
                return [
                    'success' => false,
                    'message' => 'OCR Engine was unable to detect readable text layers on this image.'
                ];
            }

            $processedText = preg_replace('/(\d+)\s*[\.,]\s*(\d{2})(?!\d)/', '$1.$2', $extractedText);
            $lines = explode("\n", trim($processedText));

            // Extract Attributes
            $merchantName = $this->extractMerchant($lines);
            $predictedCategory = $this->predictCategory($merchantName, $extractedText);
            $locationAddress = $this->extractLocation($lines);
            $receiptNo = $this->extractInvoiceNumber($extractedText);
            $transactionDate = $this->extractTransactionDate($processedText);
            $amountData = $this->extractAmount($processedText);
            $extractedAmount = $amountData['amount'];
            $taxAmount = $this->extractTax($processedText);
            $paymentMethod = $this->detectPaymentMethod($processedText);
            $extractedItems = $this->extractLineItems($extractedText, $lines, $extractedAmount);

            return [
                'success' => true,
                'receipt_image_hash' => $imageHash,
                'merchant_name' => $merchantName,
                'location_address' => $locationAddress,
                'receipt_invoice_no' => $receiptNo,
                'transaction_date' => $transactionDate,
                'amount' => $extractedAmount,
                'tax_amount' => $taxAmount,
                'payment_method' => $paymentMethod,
                'predicted_category' => $predictedCategory,
                'items' => $extractedItems,
                'raw_text' => trim($extractedText)
            ];

        } catch (\Throwable $e) {
            Log::error('OCR Async Scan Fatal Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Process and extract data from an uploaded bank transfer slip or PDF proof.
     *
     * @param UploadedFile $uploadedFile
     * @return array
     */
    public function scanBankSlip(UploadedFile $uploadedFile): array
    {
        try {
            $mimeType = $uploadedFile->getClientMimeType();
            $extension = strtolower($uploadedFile->getClientOriginalExtension());
            $imagePath = $uploadedFile->store('receipts/temp', 'private');
            $fullImagePath = storage_path('app/private/' . $imagePath);
            $fileData = file_get_contents($fullImagePath);
            $extractedText = '';

            // Demo Mode Bypass
            if (auth()->check() && (bool) (auth()->user()->is_demo ?? false)) {
                if (file_exists($fullImagePath)) {
                    unlink($fullImagePath);
                }
                return [
                    'success' => true,
                    'reference_number' => 'DEMO-EFT-' . rand(10000, 99999),
                    'transfer_date' => now()->toDateString(),
                    'recipient_account' => '114012345678',
                    'amount' => 100.20,
                    'raw_text' => "MAYBANK ISLAMIC BANKING\nTRANSFER SUCCESSFUL\nAMOUNT: RM 100.20\nREF: DEMO-EFT-99201"
                ];
            }

            $apiKey = config('services.google_vision.api_key');

            // PDF Text Stream Parsing
            if ($extension === 'pdf' || str_contains($mimeType, 'pdf')) {
                if (preg_match_all('/(?:\((.*?)\)\s*Tj|\[(.*?)\]\s*TJ)/s', $fileData, $streamMatches)) {
                    $collected = [];
                    foreach ($streamMatches[1] as $item) {
                        if (!empty($item)) {
                            $collected[] = $item;
                        }
                    }
                    foreach ($streamMatches[2] as $item) {
                        if (!empty($item)) {
                            preg_match_all('/\((.*?)\)/', $item, $inner);
                            if (!empty($inner[1])) {
                                $collected[] = implode('', $inner[1]);
                            }
                        }
                    }
                    $extractedText = implode("\n", $collected);
                }

                if (strlen(trim($extractedText)) < 15 && !empty($apiKey)) {
                    $base64Pdf = base64_encode($fileData);
                    $url = "https://vision.googleapis.com/v1/files:annotate?key=" . $apiKey;

                    $postData = [
                        'requests' => [
                            [
                                'inputConfig' => [
                                    'content' => $base64Pdf,
                                    'mimeType' => 'application/pdf'
                                ],
                                'features' => [['type' => 'DOCUMENT_TEXT_DETECTION']],
                                'pages' => [1]
                            ]
                        ]
                    ];

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

                    $rawResponse = curl_exec($ch);
                    curl_close($ch);

                    if ($rawResponse) {
                        $jsonResult = json_decode($rawResponse, true);
                        if (!empty($jsonResult['responses'][0]['responses'][0]['fullTextAnnotation']['text'])) {
                            $extractedText = $jsonResult['responses'][0]['responses'][0]['fullTextAnnotation']['text'];
                        }
                    }
                }
            } else {
                if (!empty($apiKey)) {
                    $base64Image = base64_encode($fileData);
                    $url = "https://vision.googleapis.com/v1/images:annotate?key=" . $apiKey;

                    $postData = [
                        'requests' => [
                            [
                                'image' => ['content' => $base64Image],
                                'features' => [['type' => 'DOCUMENT_TEXT_DETECTION']]
                            ]
                        ]
                    ];

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

                    $rawResponse = curl_exec($ch);
                    curl_close($ch);

                    if ($rawResponse) {
                        $jsonResult = json_decode($rawResponse, true);
                        if (!empty($jsonResult['responses'][0]['fullTextAnnotation']['text'])) {
                            $extractedText = $jsonResult['responses'][0]['fullTextAnnotation']['text'];
                        }
                    }
                }
            }

            if (file_exists($fullImagePath)) {
                unlink($fullImagePath);
            }

            if (empty($extractedText)) {
                return ['success' => false, 'message' => 'Could not read text layers from the uploaded file.'];
            }

            // Extract Reference ID
            $referenceId = '';
            if (preg_match('/(?:Paynet\s*Ref\s*No|Paynet\s*Ref|PayNet\s*Reference)[:\s\.]+([A-Za-z0-9]{15,})/i', $extractedText, $paynetMatch)) {
                $referenceId = trim($paynetMatch[1]);
            } elseif (preg_match('/(?:Channel\s*Ref\s*No|Reference\s*No|Reference\s*ID|Ref\s*No|Txn\s*ID|Transaction\s*ID)[:\s\.]+([A-Za-z0-9]{10,})/i', $extractedText, $channelMatch)) {
                $referenceId = trim($channelMatch[1]);
            } elseif (preg_match('/\b(20\d{2}[A-Za-z0-9]{10,})\b/', $extractedText, $seqMatch)) {
                $referenceId = trim($seqMatch[1]);
            }

            // Extract Timestamp
            $transferTime = date('d M Y, h:i A');
            if (preg_match('/(\d{1,2}\s+[A-Za-z]{3}\s+20\d{2})[\s,]+(\d{1,2}:\d{2}(?::\d{2})?\s*(?:AM|PM|am|pm)?)/', $extractedText, $timeMatch)) {
                $transferTime = trim($timeMatch[1]) . ', ' . trim($timeMatch[2]);
            } elseif (preg_match('/(\d{1,2}[\/\.-]\d{1,2}[\/\.-]20\d{2})[\s,]+(\d{1,2}:\d{2}(?::\d{2})?\s*(?:AM|PM|am|pm)?)/', $extractedText, $timeMatch2)) {
                $transferTime = trim($timeMatch2[1]) . ', ' . trim($timeMatch2[2]);
            }

            // Extract Transferred Amount
            $transferredAmount = '0.00';
            if (preg_match('/(?:Total\s*Amount|Amount)[\s:\.\$]*RM\s*([0-9]+\.\d{2})/i', $extractedText, $amtMatch)) {
                $transferredAmount = number_format((float) $amtMatch[1], 2, '.', '');
            } elseif (preg_match('/(?:RM|MYR)[\s:\.\$]*([0-9]+\.\d{2})\b/i', $extractedText, $amtMatch2)) {
                $transferredAmount = number_format((float) $amtMatch2[1], 2, '.', '');
            }

            return [
                'success' => true,
                'payment_reference' => $referenceId ?: date('Ymd') . strtoupper(substr(md5(time()), 0, 8)),
                'transfer_time' => $transferTime,
                'transferred_amount' => $transferredAmount,
                'raw_text' => $extractedText
            ];

        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Inspect image Exif metadata for editing software indicators.
     */
    protected function detectExifTampering(string $fullImagePath): ?string
    {
        if (!function_exists('exif_read_data')) {
            return null;
        }

        try {
            $exif = @exif_read_data($fullImagePath);
            if ($exif && !empty($exif['Software'])) {
                $software = strtolower($exif['Software']);
                if (str_contains($software, 'photoshop') || str_contains($software, 'canva') || str_contains($software, 'gimp') || str_contains($software, 'picsart')) {
                    return "Security Warning: Image metadata indicates modifications by editing software ({$exif['Software']}). Only original photos are accepted.";
                }
            }
        } catch (\Throwable $e) {
            // Suppress exif read failures
        }

        return null;
    }

    /**
     * Call Google Cloud Vision REST endpoint.
     */
    protected function callVisionApi(string $imageData, string $apiKey): string
    {
        $base64Image = base64_encode($imageData);
        $url = "https://vision.googleapis.com/v1/images:annotate?key=" . $apiKey;

        $postData = [
            'requests' => [
                [
                    'image' => ['content' => $base64Image],
                    'features' => [['type' => 'DOCUMENT_TEXT_DETECTION']]
                ]
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $rawResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!$curlError && $rawResponse) {
            $jsonResult = json_decode($rawResponse, true);
            if (!empty($jsonResult['responses'][0]['fullTextAnnotation']['text'])) {
                return $jsonResult['responses'][0]['fullTextAnnotation']['text'];
            }
        }

        return '';
    }

    /**
     * Fallback to Google Cloud Vision SDK client if configured.
     */
    protected function callVisionSdkFallback(string $imageData): string
    {
        try {
            $imageAnnotator = new ImageAnnotatorClient();
            $image = new Image();
            $image->setContent($imageData);

            $feature = new Feature();
            $feature->setType(Type::DOCUMENT_TEXT_DETECTION);

            $annotateImageRequest = new AnnotateImageRequest();
            $annotateImageRequest->setImage($image);
            $annotateImageRequest->setFeatures([$feature]);

            $batchRequest = new BatchAnnotateImagesRequest();
            $batchRequest->setRequests([$annotateImageRequest]);

            $response = $imageAnnotator->batchAnnotateImages($batchRequest);
            $responses = $response->getResponses();

            $extractedText = '';
            if (count($responses) > 0 && $responses[0]->getFullTextAnnotation()) {
                $extractedText = $responses[0]->getFullTextAnnotation()->getText();
            }
            $imageAnnotator->close();

            return $extractedText;
        } catch (\Throwable $sdkError) {
            Log::warning('Vision SDK Fallback failed: ' . $sdkError->getMessage());
            return '';
        }
    }

    /**
     * Extract merchant name from text lines.
     */
    public function extractMerchant(array $lines): string
    {
        $merchantName = 'Unknown Merchant';
        for ($i = 0; $i < min(6, count($lines)); $i++) {
            $candidateLine = trim($lines[$i]);
            if (preg_match('/(?:SDN\s*BHD|BHD|ENTERPRISE|MART|GROCER|STATION|PETRONAS|SHELL|PETRON|CALTEX|KK\s*SUPERMART|7-ELEVEN|TEXAS|TEALIVE|STARBUCKS|DIY|MR\s*DIY|ACE\s*HARDWARE|BOOKSTORE|RESTAURANT|CAFE|BAKERY|MYDIN|LOTUS|GIANT|WATSONS|GUARDIAN|SPEEDMART|PASARAYA|SUPERMARKET)/i', $candidateLine)) {
                $cleanMerchant = preg_replace('/(?:\d{4,}[A-Za-z0-9\-]*|\([A-Za-z0-9\-]+\))/i', '', $candidateLine);
                $cleanMerchant = trim(preg_replace('/[^A-Za-z0-9\s\&\-\']/', '', $cleanMerchant));
                if (strlen($cleanMerchant) > 3) {
                    return ucwords(strtolower($cleanMerchant));
                }
            }
        }

        foreach ($lines as $line) {
            $cleanLine = trim(preg_replace('/[^A-Za-z0-9\s\.\-\(\)\&]/', '', $line));
            if (strlen($cleanLine) <= 3) {
                continue;
            }
            if (preg_match('/(?:tel|phone|fax|date|time|tax|gst|sst|reg|welcome|cashier|terminal|receipt|slip|description|qty|price|amount|kuala|lumpur|johor)/i', $cleanLine)) {
                continue;
            }
            if (preg_match('/[\d\-\/]{5,}/', $cleanLine)) {
                continue;
            }
            return ucwords(strtolower($cleanLine));
        }

        return $merchantName;
    }

    /**
     * Predict expenditure category based on active keywords and merchant heuristics.
     */
    protected function predictCategory(string $merchantName, string $extractedText): string
    {
        $activeCategories = Category::where('is_active', true)->get();
        $idfDictionary = [];
        $categoryScores = [];

        foreach ($activeCategories as $category) {
            $categoryScores[$category->name] = 0.0;
            $keywords = array_filter(array_map('trim', explode(',', $category->keywords ?? '')));
            $idfDictionary[$category->name] = [];
            foreach ($keywords as $kw) {
                $idfDictionary[$category->name][strtolower($kw)] = 2.0;
            }
        }

        $cleanLowerText = strtolower($extractedText);
        $tokenWords = preg_split('/[^a-z0-9]/', $cleanLowerText, -1, PREG_SPLIT_NO_EMPTY);
        $tfCounts = array_count_values($tokenWords);

        foreach ($tfCounts as $word => $tfValue) {
            foreach ($idfDictionary as $catName => $keywords) {
                if (isset($keywords[$word])) {
                    $categoryScores[$catName] += $tfValue * $keywords[$word];
                }
            }
        }

        // Strong Merchant Heuristics
        $upperMerchant = strtoupper($merchantName);
        $heuristicMappings = [
            'Site Tools & Hardware' => '/(?:DIY|MR\s*DIY|ACE\s*HARDWARE|HARDWARE|TOOL|PAINT|CHOP\s*TONG)/i',
            'Meals & Entertainment' => '/(?:MCDONALD|KFC|RESTAURANT|CAFE|TEALIVE|STARBUCKS|PIZZA|SUBWAY|COFFEE|BURGER|NASI|KOPITIAM)/i',
            'Fuel / Automotive' => '/(?:PETRONAS|SHELL|PETRON|CALTEX|BHP|STATION|PETROLEUM)/i',
            'Office Supplies' => '/(?:BOOKSTORE|STATIONERY|POPULAR|OFFICE)/i',
        ];

        foreach ($heuristicMappings as $catName => $pattern) {
            if (isset($categoryScores[$catName]) && preg_match($pattern, $upperMerchant)) {
                $categoryScores[$catName] += 60.0;
            }
        }

        arsort($categoryScores);
        $predictedCategory = key($categoryScores);
        if (empty($categoryScores) || current($categoryScores) == 0.0) {
            $predictedCategory = $activeCategories->first()->name ?? 'Office Supplies';
        }

        return $predictedCategory;
    }

    /**
     * Extract branch address or location from text lines.
     */
    protected function extractLocation(array $lines): string
    {
        $addressParts = [];
        foreach ($lines as $line) {
            if (preg_match('/(?:INV|INVOICE|REF|BILL|CASHIER|TOTAL|RM|RINGGIT|CHANGE|ROUNDING|DUE)/i', $line)) {
                continue;
            }
            if (preg_match('/\b(?:No|Lot|Jalan|Jln|KM|Lebuhraya|Sg|Besi|Serdang|Solaris|Pandan|Kampong|Kg|Kuala|Lumpur|KL|Johor|JB|Selangor|Penang|Ipoh|Melaka|Batu\s+Pahat|Muar|Bangi|Bandar)\b/i', $line) || preg_match('/\b\d{5}\b/', $line)) {
                if (!preg_match('/(?:Tel|Fax|Email|Gst|Sst|Tax|Welcome|Thank)/i', $line)) {
                    $addressParts[] = trim($line);
                    if (count($addressParts) >= 2) {
                        break;
                    }
                }
            }
        }

        return !empty($addressParts) ? implode(', ', $addressParts) : 'Unknown Location';
    }

    /**
     * Extract invoice or receipt reference number.
     */
    protected function extractInvoiceNumber(string $extractedText): string
    {
        $invoicePatterns = [
            '/(?:INV\s*NO|INV\s*NO\.|INV|INVOICE\s*NO|RECEIPT\s*NO|DOC\s*NO|BILL\s*NO|TICKET\s*NO)[:\s\.]+([A-Za-z0-9\-]{4,})/i',
            '/\b(?:INV|RCpt|TX|CS|DSP|INV\-)\-?[A-Za-z0-9\-]{4,}\b/i',
            '/Invoice\s*No[:\s]+([A-Za-z0-9]+)/i'
        ];

        foreach ($invoicePatterns as $pattern) {
            if (preg_match($pattern, $extractedText, $invMatches)) {
                $candidate = trim($invMatches[1] ?? $invMatches[0]);
                $candidate = trim(preg_replace('/[:\s\.]+/', '', $candidate));
                if (!preg_match('/^(?:COPY|DUPLICATE|ORIGINAL|TAX|INVOICE|OFFICIAL|REPRINT|VALIDITY|TO|CLAIM)$/i', $candidate)) {
                    return strtoupper($candidate);
                }
            }
        }

        return 'NOT FOUND';
    }

    /**
     * Parse transaction date supporting English and Malay month representations.
     */
    public function extractTransactionDate(string $processedText): string
    {
        $transactionDate = date('Y-m-d');
        $monthMap = [
            'jan' => '01',
            'feb' => '02',
            'mar' => '03',
            'apr' => '04',
            'may' => '05',
            'mei' => '05',
            'jun' => '06',
            'jul' => '07',
            'aug' => '08',
            'ogos' => '08',
            'sep' => '09',
            'oct' => '10',
            'okt' => '10',
            'nov' => '11',
            'dec' => '12',
            'dis' => '12'
        ];

        $currentYear = (int) date('Y');

        if (preg_match('/(\d{1,2})\s*[\-\/.\s]?\s*(JAN|FEB|MAR|APR|MAY|MEI|JUN|JUL|AUG|OGOS|SEP|OCT|OKT|NOV|DEC|DIS)[A-Z]*\s*[\-\/.\s]?\s*(\d{2,4})/i', $processedText, $dateMatches)) {
            $day = str_pad($dateMatches[1], 2, '0', STR_PAD_LEFT);
            $month = $monthMap[strtolower($dateMatches[2])] ?? '01';
            $year = strlen($dateMatches[3]) == 2 ? '20' . $dateMatches[3] : $dateMatches[3];
            if ((int) $year <= $currentYear) {
                $transactionDate = "{$year}-{$month}-{$day}";
            }
        } elseif (preg_match('/\b(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{2,4})\b/', $processedText, $numMatches)) {
            $day = str_pad($numMatches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($numMatches[2], 2, '0', STR_PAD_LEFT);
            $year = strlen($numMatches[3]) == 2 ? '20' . $numMatches[3] : $numMatches[3];
            if ((int) $year <= $currentYear && (int) $month <= 12 && (int) $day <= 31) {
                $transactionDate = "{$year}-{$month}-{$day}";
            }
        }

        return $transactionDate;
    }

    /**
     * Extract net total amount and cash tender calculations.
     */
    public function extractAmount(string $processedText): array
    {
        $extractedAmount = '0.00';
        $cashTendered = 0.0;
        $changeReturned = 0.0;

        if (preg_match('/\b(?:GRAND\s*TOTAL|TOTAL\s*DUE|NET\s*TOTAL|JUMLAH|TOTAL)[\s:\.\$]*(?:RM|MYR)?[\s:\.\$]*(\d+\.\d{2})\b/i', $processedText, $totalMatch)) {
            $candidateTotal = (float) $totalMatch[1];
            if ($candidateTotal > 0) {
                $extractedAmount = number_format($candidateTotal, 2, '.', '');
            }
        }

        if (preg_match('/(?:CASH|TENDER|TUNAI)[\s:\.\$]*(?:RM|MYR)?[\s:\.\$]*(\d+\.\d{2})\b/i', $processedText, $cashMatch)) {
            $cashTendered = (float) $cashMatch[1];
        }
        if (preg_match('/(?:CHANGE|BAKI)[\s:\.\$]*(?:RM|MYR)?[\s:\.\$]*(\d+\.\d{2})\b/i', $processedText, $changeMatch)) {
            $changeReturned = (float) $changeMatch[1];
        }

        if ($cashTendered > 0 && $changeReturned > 0 && ($cashTendered - $changeReturned) > 0) {
            $computedNet = $cashTendered - $changeReturned;
            $extractedAmount = number_format($computedNet, 2, '.', '');
        }

        return [
            'amount' => $extractedAmount,
            'cash_tendered' => $cashTendered,
            'change_returned' => $changeReturned,
        ];
    }

    /**
     * Extract SST / GST tax total.
     */
    protected function extractTax(string $processedText): string
    {
        if (preg_match('/(?:TAX|GST|SST|CUKAI|VAT)[\s\:]*(?:RM|MYR)?[\s]*([\d\.,]+)/i', $processedText, $taxMatches)) {
            return number_format((float) str_replace(',', '', $taxMatches[1]), 2, '.', '');
        }

        return '0.00';
    }

    /**
     * Identify payment method from keywords.
     */
    protected function detectPaymentMethod(string $processedText): string
    {
        if (preg_match('/(?:CARD|CREDIT|DEBIT|VISA|MASTER|MYDEBIT|CHIP|WAVE)/i', $processedText)) {
            return 'Card';
        }

        if (preg_match('/(?:WALLET|E-WALLET|TOUCH|GO|TNG|DUITNOW|BOOST|GRABPAY|QR)/i', $processedText)) {
            return 'e-Wallet';
        }

        return 'Cash';
    }

    /**
     * Extract itemized lines for fuel or general receipts.
     */
    protected function extractLineItems(string $extractedText, array $lines, string $extractedAmount): array
    {
        $extractedItems = [];
        $rawLowerText = strtolower($extractedText);
        $isFuelReceipt = preg_match('/(?:petronas|shell|petron|caltex|primax|ron95|ron97|diesel|pump\s*\d+)/i', $rawLowerText);

        if ($isFuelReceipt) {
            $fuelLitre = 0.0;
            if (preg_match('/(\d+\.\d{2,3})\s*(?:L|LITRE|LTR)/i', $extractedText, $litreMatches)) {
                $fuelLitre = (float) $litreMatches[1];
            }

            $fuelType = 'RON95 Fuel';
            if (preg_match('/(Primax\s*95|Primax\s*97|RON\s*95|RON\s*97|Diesel)/i', $extractedText, $typeMatches)) {
                $fuelType = ucwords(strtolower($typeMatches[1]));
            }

            $extractedItems[] = [
                'item_name' => $fuelLitre > 0 ? "{$fuelType} ({$fuelLitre} L)" : $fuelType,
                'quantity' => 1,
                'unit_price' => $extractedAmount,
                'subtotal' => $extractedAmount
            ];
        } else {
            $ignoreRegex = '/(?:TOTAL|SUBTOTAL|SUB\s*TOTAL|ROUNDING|CHANGE|CASH|TENDER|VISA|MASTER|TAX|GST|SST|INVOICE|RECEIPT|BALANCE|SAVING|DISCOUNT|PROMO|POINT|PLASTIC|MYKAD|SUBSIDY|TEL|PHONE|FAX|DATE|TIME|CASHIER|THANK|WELCOME|STAR\s*GROCER|SDN\s*BHD|TAX\s*INVOICES|REG\s*NO|URL|HTTP|EXCHANGE|REFUND)/i';
            $lineCount = count($lines);

            for ($i = 0; $i < $lineCount; $i++) {
                $line = trim($lines[$i]);
                if (strlen($line) < 3) {
                    continue;
                }
                if (preg_match('/^(?:Total Item|Total Qty|Sub Total|SubTotal|Total Saving|Grand Total|Tender)/i', $line)) {
                    break;
                }
                if (preg_match($ignoreRegex, $line)) {
                    continue;
                }

                if (preg_match('/^([A-Za-z0-9\s\/\-\.\&]+?)(?:\s+(\d+(?:\.\d{2})?)\s*[\*xX]\s*(\d+))?\s+(\d+\.\d{2})$/', $line, $p1)) {
                    $rawName = trim($p1[1]);
                    $subtotal = (float) $p1[4];
                    $qty = !empty($p1[3]) ? (int) $p1[3] : 1;

                    if (!preg_match('/^\d{5,}$/', $rawName) && strlen($rawName) > 2) {
                        $cleanName = ucwords(strtolower(preg_replace('/[^A-Za-z0-9\s\-\.]/', '', $rawName)));
                        $extractedItems[] = [
                            'item_name' => $cleanName,
                            'quantity' => $qty,
                            'unit_price' => number_format($subtotal / max(1, $qty), 2, '.', ''),
                            'subtotal' => number_format($subtotal, 2, '.', '')
                        ];
                    }
                }
            }
        }

        return $extractedItems;
    }
}
