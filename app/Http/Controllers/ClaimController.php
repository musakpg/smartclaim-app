<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Claim;
use App\Models\ClaimItem;
use App\Models\Vehicle;
use App\Models\MileageRate;
use App\Models\AuditLog;
use App\Models\ExpensePolicy;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Carbon\Carbon;
use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\BatchAnnotateImagesRequest;
use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\Image;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use App\Services\BudgetEnforcementService;
use App\Services\ActiveLearningService;
use App\Services\FraudDetectionService;
use App\Services\NotificationService;

class ClaimController extends Controller
{
    /**
     * Display the regular staff / employee analytical dashboard metrics.
     */
    public function index()
    {
        $currentUserId = Auth::id() ?? 1;

        // 1. Calculate KPI Metrics
        $totalSpending = Claim::where('user_id', $currentUserId)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->sum('amount');

        $approvedCount = Claim::where('user_id', $currentUserId)->whereIn('status', ['Approved', 'Reimbursed'])->count();
        $preApprovedCount = Claim::where('user_id', $currentUserId)->where('status', 'Pre-Approved')->count();
        $pendingCount = Claim::where('user_id', $currentUserId)->where('status', 'Pending')->count();
        $rejectedCount = Claim::where('user_id', $currentUserId)->where('status', 'Rejected')->count();
        $totalClaimsCount = Claim::where('user_id', $currentUserId)->count();

        // 2. Compute Monthly Spending for Line Chart (Jan - Dec)
        $currentYear = Carbon::now()->year;
        $monthlyExpenses = Claim::where('user_id', $currentUserId)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->whereYear('transaction_date', $currentYear)
            ->selectRaw('MONTH(transaction_date) as month, SUM(amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $lineChartData = [];
        for ($m = 1; $m <= 12; $m++) {
            $lineChartData[] = isset($monthlyExpenses[$m]) ? (float) $monthlyExpenses[$m] : 0.0;
        }

        // 3. Compute Category Spending for Bar Chart
        $categorySummary = Claim::where('user_id', $currentUserId)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->select('predicted_category', DB::raw('SUM(amount) as total'))
            ->groupBy('predicted_category')
            ->get();

        $barLabels = $categorySummary->pluck('predicted_category')->toArray();
        $barValues = $categorySummary->pluck('total')->map(fn($v) => (float) $v)->toArray();

        if (empty($barLabels)) {
            $barLabels = ['Meals & Entertainment', 'Fuel / Automotive', 'Office Supplies'];
            $barValues = [0, 0, 0];
        }

        return view('dashboard', compact(
            'totalSpending',
            'totalClaimsCount',
            'approvedCount',
            'preApprovedCount',
            'pendingCount',
            'rejectedCount',
            'lineChartData',
            'barLabels',
            'barValues'
        ));
    }

    /**
     * Display Reimbursement Ledger for Staff with Real-Time Settlement Balance.
     */
    public function reimbursementIndex()
    {
        $currentUserId = Auth::id() ?? 1;

        $approvedClaims = Claim::where('user_id', $currentUserId)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->orderBy('updated_at', 'desc')
            ->get();

        $approvedTotal = $approvedClaims->sum('amount');
        $paidTotal = $approvedClaims->where('status', 'Reimbursed')->sum('amount');
        $processingTotal = $approvedClaims->where('status', 'Approved')->sum('amount');

        return view('reimbursement.index', compact('approvedClaims', 'approvedTotal', 'paidTotal', 'processingTotal'));
    }

    /**
     * Show the form for creating a new claim with global vehicle asset mapping.
     */
    public function create()
    {
        $currentUserId = Auth::id() ?? 1;

        // 1. Personal vehicles for Mileage allowance claims
        $personalVehicles = Vehicle::where('user_id', $currentUserId)
            ->where('ownership_type', 'personal')
            ->where('approval_status', 'Approved')
            ->whereDate('roadtax_expiry', '>=', Carbon::today())
            ->orderBy('plate_number', 'asc')
            ->get();

        // 2. Company fleet vehicles for Fuel receipt claims
        $companyFleet = Vehicle::where('ownership_type', 'company')
            ->where('status', 'Active')
            ->orderBy('plate_number', 'asc')
            ->get();

        return view('claims.create', compact('personalVehicles', 'companyFleet'));
    }

    public function history()
    {
        $claims = Claim::latest()->get();
        return view('claims.history', compact('claims'));
    }

    public function checkDuplicate(Request $request)
    {
        $exists = Claim::where('receipt_invoice_no', trim($request->invoice_no))
            ->where('amount', $request->amount)
            ->exists();

        return response()->json(['duplicate' => $exists]);
    }

    /**
     * Universal Malaysian Receipt AI OCR Engine.
     */
    public function asyncScan(Request $request)
    {
        if (!$request->hasFile('receipt')) {
            return response()->json(['success' => false, 'message' => 'No receipt file upload detected.']);
        }

        try {
            $uploadedFile = $request->file('receipt');
            $imagePath = $uploadedFile->store('receipts/temp', 'public');
            $fullImagePath = storage_path('app/public/' . $imagePath);
            $imageData = file_get_contents($fullImagePath);
            $extractedText = '';

            // Security Layer 1: Image Hash & Duplication Interception
            $imageHash = hash('sha256', $imageData);
            $existingClaim = Claim::where('receipt_image_hash', $imageHash)->with('user')->first();

            if ($existingClaim) {
                if (file_exists($fullImagePath)) {
                    unlink($fullImagePath);
                }
                return response()->json([
                    'success' => false,
                    'is_duplicate_image' => true,
                    'duplicate_reason' => "Security Interception: This exact physical receipt was already submitted by " . ($existingClaim->user->name ?? 'another staff') . " on " . $existingClaim->created_at->format('d M Y') . " (Voucher #CLM-{$existingClaim->claim_id}). Please upload an authentic receipt."
                ]);
            }

            // Security Layer 2: Exif Tamper Detection
            if (function_exists('exif_read_data')) {
                try {
                    $exif = @exif_read_data($fullImagePath);
                    if ($exif && !empty($exif['Software'])) {
                        $software = strtolower($exif['Software']);
                        if (str_contains($software, 'photoshop') || str_contains($software, 'canva') || str_contains($software, 'gimp') || str_contains($software, 'picsart')) {
                            if (file_exists($fullImagePath)) {
                                unlink($fullImagePath);
                            }
                            return response()->json([
                                'success' => false,
                                'is_tampered' => true,
                                'tamper_reason' => "Security Warning: Image metadata indicates modifications by editing software ({$exif['Software']}). Only original photos are accepted."
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    // Bypass exif read errors
                }
            }

            $apiKey = env('GOOGLE_CLOUD_API_KEY');

            // Direct REST Vision API Call
            if (!empty($apiKey)) {
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
                        $extractedText = $jsonResult['responses'][0]['fullTextAnnotation']['text'];
                    }
                }
            }

            // SDK Annotator Client Fallback
            if (empty($extractedText)) {
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

                    if (count($responses) > 0 && $responses[0]->getFullTextAnnotation()) {
                        $extractedText = $responses[0]->getFullTextAnnotation()->getText();
                    }
                    $imageAnnotator->close();
                } catch (\Throwable $sdkError) {
                    Log::warning('Vision SDK Fallback failed: ' . $sdkError->getMessage());
                }
            }

            if (file_exists($fullImagePath)) {
                unlink($fullImagePath);
            }

            if (empty($extractedText)) {
                return response()->json([
                    'success' => false,
                    'message' => 'OCR Engine was unable to detect readable text layers on this image.'
                ]);
            }

            $processedText = preg_replace('/(\d+)\s*[\.,]\s*(\d{2})(?!\d)/', '$1.$2', $extractedText);
            $lines = explode("\n", trim($processedText));

            // Extract Merchant Name
            $merchantName = 'Unknown Merchant';
            for ($i = 0; $i < min(6, count($lines)); $i++) {
                $candidateLine = trim($lines[$i]);
                if (preg_match('/(?:SDN\s*BHD|BHD|ENTERPRISE|MART|GROCER|STATION|PETRONAS|SHELL|PETRON|CALTEX|KK\s*SUPERMART|7-ELEVEN|TEXAS|TEALIVE|STARBUCKS|DIY|MR\s*DIY|BOOKSTORE|RESTAURANT|CAFE|BAKERY|MYDIN|LOTUS|GIANT|WATSONS|GUARDIAN)/i', $candidateLine)) {
                    $cleanMerchant = preg_replace('/(?:\d{4,}[A-Za-z0-9\-]*|\([A-Za-z0-9\-]+\))/i', '', $candidateLine);
                    $cleanMerchant = trim(preg_replace('/[^A-Za-z0-9\s\&\-\']/', '', $cleanMerchant));
                    if (strlen($cleanMerchant) > 3) {
                        $merchantName = ucwords(strtolower($cleanMerchant));
                        break;
                    }
                }
            }

            if ($merchantName === 'Unknown Merchant') {
                foreach ($lines as $line) {
                    $cleanLine = trim(preg_replace('/[^A-Za-z0-9\s\.\-\(\)\&]/', '', $line));
                    if (strlen($cleanLine) <= 3)
                        continue;
                    if (preg_match('/(?:tel|phone|fax|date|time|tax|gst|sst|reg|welcome|cashier|terminal|receipt|slip|description|qty|price|amount|kuala|lumpur|johor)/i', $cleanLine))
                        continue;
                    if (preg_match('/[\d\-\/]{5,}/', $cleanLine))
                        continue;
                    $merchantName = ucwords(strtolower($cleanLine));
                    break;
                }
            }

            // TF-IDF Categorization
            $idfDictionary = [
                'meals' => ['restaurant' => 2.0, 'mcdonalds' => 2.5, 'kfc' => 2.5, 'cafe' => 1.8, 'food' => 1.2, 'beverage' => 1.5, 'chicken' => 1.9, 'coffee' => 2.0, 'dinner' => 1.6, 'lunch' => 1.6, 'bistro' => 2.2, 'bakery' => 2.1, 'pizza' => 2.3, 'tealive' => 2.5, 'starbucks' => 2.5, 'bhd' => 0.1, 'sdn' => 0.1, 'ayam' => 1.8, 'kopi' => 1.9, 'makan' => 1.5, 'minum' => 1.5, 'burger' => 2.2, 'nasi' => 1.7, 'mee' => 1.7, 'teh' => 1.5, 'ice' => 1.1, 'dunkin' => 2.5, 'subway' => 2.5, 'secret' => 2.2, 'recipe' => 2.2],
                'transport' => ['petronas' => 2.5, 'shell' => 2.5, 'petron' => 2.5, 'caltex' => 2.5, 'fuel' => 2.0, 'diesel' => 2.2, 'ron95' => 2.5, 'ron97' => 2.5, 'toll' => 2.1, 'plus' => 2.0, 'parking' => 1.8, 'petroleum' => 2.3, 'touch' => 2.1, 'go' => 1.2, 'station' => 1.5, 'pump' => 1.9, 'mesra' => 2.2, 'select' => 2.0],
                'supplies' => ['bookstore' => 2.5, 'stationery' => 2.3, 'paper' => 1.8, 'printing' => 1.9, 'ink' => 2.4, 'cartridge' => 2.5, 'stapler' => 2.5, 'marker' => 2.2, 'hardware' => 2.0, 'diy' => 2.2, 'office' => 1.5, 'files' => 2.0, 'binding' => 2.3, 'toner' => 2.5, 'pen' => 1.6]
            ];

            $cleanLowerText = strtolower($extractedText);
            $tokenWords = preg_split('/[^a-z0-9]/', $cleanLowerText, -1, PREG_SPLIT_NO_EMPTY);
            $tfCounts = array_count_values($tokenWords);

            $categoryScores = [
                'Meals & Entertainment' => 0.0,
                'Fuel / Automotive' => 0.0,
                'Office Supplies' => 0.0
            ];

            foreach ($tfCounts as $word => $tfValue) {
                if (isset($idfDictionary['meals'][$word]))
                    $categoryScores['Meals & Entertainment'] += $tfValue * $idfDictionary['meals'][$word];
                if (isset($idfDictionary['transport'][$word]))
                    $categoryScores['Fuel / Automotive'] += $tfValue * $idfDictionary['transport'][$word];
                if (isset($idfDictionary['supplies'][$word]))
                    $categoryScores['Office Supplies'] += $tfValue * $idfDictionary['supplies'][$word];
            }

            $upperMerchant = strtoupper($merchantName);
            if (preg_match('/(?:MCDONALD|KFC|RESTAURANT|CAFE|TEALIVE|STARBUCKS|PIZZA|SUBWAY|COFFEE|BURGER|NASI)/', $upperMerchant)) {
                $categoryScores['Meals & Entertainment'] += 50.0;
            }
            if (preg_match('/(?:PETRONAS|SHELL|PETRON|CALTEX|STATION|MOTOR|WORKSHOP|GARAGE)/', $upperMerchant)) {
                $categoryScores['Fuel / Automotive'] += 50.0;
            }
            if (preg_match('/(?:BOOKSTORE|STATIONERY|DIY|POPULAR|OFFICE)/', $upperMerchant)) {
                $categoryScores['Office Supplies'] += 50.0;
            }

            arsort($categoryScores);
            $predictedCategory = key($categoryScores);
            if (current($categoryScores) == 0.0) {
                $predictedCategory = 'Office Supplies';
            }

            // Extract Location
            $locationAddress = 'Unknown Location';
            $addressParts = [];
            foreach ($lines as $line) {
                if (preg_match('/(?:INV|INVOICE|REF|BILL|CASHIER|TOTAL|RM|RINGGIT|CHANGE|ROUNDING|DUE)/i', $line))
                    continue;
                if (preg_match('/\b(?:No|Lot|Jalan|Jln|KM|Lebuhraya|Sg|Besi|Serdang|Solaris|Pandan|Kampong|Kg|Kuala|Lumpur|KL|Johor|JB|Selangor|Penang|Ipoh|Melaka|Batu\s+Pahat|Muar|Bangi|Bandar)\b/i', $line) || preg_match('/\b\d{5}\b/', $line)) {
                    if (!preg_match('/(?:Tel|Fax|Email|Gst|Sst|Tax|Welcome|Thank)/i', $line)) {
                        $addressParts[] = trim($line);
                        if (count($addressParts) >= 2)
                            break;
                    }
                }
            }
            if (!empty($addressParts)) {
                $locationAddress = implode(', ', $addressParts);
            }

            // Extract Invoice No
            $receiptNo = 'NOT FOUND';
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
                        $receiptNo = strtoupper($candidate);
                        break;
                    }
                }
            }

            // Extract Transaction Date
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

            if (preg_match('/(\d{1,2})\s*[\-\/.\s]?\s*(JAN|FEB|MAR|APR|MAY|MEI|JUN|JUL|AUG|OGOS|SEP|OCT|OKT|NOV|DEC|DIS)[A-Z]*\s*[\-\/.\s]?\s*(\d{2,4})/i', $processedText, $dateMatches)) {
                $day = str_pad($dateMatches[1], 2, '0', STR_PAD_LEFT);
                $month = $monthMap[strtolower($dateMatches[2])] ?? '01';
                $year = strlen($dateMatches[3]) == 2 ? '20' . $dateMatches[3] : $dateMatches[3];
                if ((int) $year <= 2026) {
                    $transactionDate = "{$year}-{$month}-{$day}";
                }
            } elseif (preg_match('/\b(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{2,4})\b/', $processedText, $numMatches)) {
                $day = str_pad($numMatches[1], 2, '0', STR_PAD_LEFT);
                $month = str_pad($numMatches[2], 2, '0', STR_PAD_LEFT);
                $year = strlen($numMatches[3]) == 2 ? '20' . $numMatches[3] : $numMatches[3];
                if ((int) $year <= 2026 && (int) $month <= 12 && (int) $day <= 31) {
                    $transactionDate = "{$year}-{$month}-{$day}";
                }
            }

            // Extract Net Amount
            $extractedAmount = '0.00';
            $cashTendered = 0.0;
            $changeReturned = 0.0;

            if (preg_match('/(?:GRAND\s*TOTAL|TOTAL\s*DUE|NET\s*TOTAL|JUMLAH|TOTAL)[\s:\.\$]*(\d+\.\d{2})\b/i', $processedText, $totalMatch)) {
                $candidateTotal = (float) $totalMatch[1];
                if ($candidateTotal > 0) {
                    $extractedAmount = number_format($candidateTotal, 2, '.', '');
                }
            }

            if (preg_match('/(?:CASH|TENDER|TUNAI)[\s:\.\$]*(\d+\.\d{2})\b/i', $processedText, $cashMatch)) {
                $cashTendered = (float) $cashMatch[1];
            }
            if (preg_match('/(?:CHANGE|BAKI)[\s:\.\$]*(\d+\.\d{2})\b/i', $processedText, $changeMatch)) {
                $changeReturned = (float) $changeMatch[1];
            }

            if ($cashTendered > 0 && $changeReturned > 0 && ($cashTendered - $changeReturned) > 0) {
                $computedNet = $cashTendered - $changeReturned;
                $extractedAmount = number_format($computedNet, 2, '.', '');
            }

            // Arbitrate Payment Method
            $paymentMethod = 'Cash';
            if (preg_match('/(?:CARD|CREDIT|DEBIT|VISA|MASTER|MYDEBIT|CHIP|WAVE)/i', $processedText)) {
                $paymentMethod = 'Card';
            } elseif (preg_match('/(?:WALLET|E-WALLET|TOUCH|GO|TNG|DUITNOW|BOOST|GRABPAY|QR)/i', $processedText)) {
                $paymentMethod = 'e-Wallet';
            }

            // Itemization Extraction
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
                    if (strlen($line) < 3)
                        continue;
                    if (preg_match('/^(?:Total Item|Total Qty|Sub Total|SubTotal|Total Saving|Grand Total|Tender)/i', $line))
                        break;
                    if (preg_match($ignoreRegex, $line))
                        continue;

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

            return response()->json([
                'success' => true,
                'receipt_image_hash' => $imageHash,
                'merchant_name' => $merchantName,
                'location_address' => $locationAddress,
                'receipt_invoice_no' => $receiptNo,
                'transaction_date' => $transactionDate,
                'amount' => $extractedAmount,
                'payment_method' => $paymentMethod,
                'predicted_category' => $predictedCategory,
                'items' => $extractedItems,
                'raw_text' => trim($extractedText)
            ]);

        } catch (\Throwable $e) {
            Log::error('OCR Async Scan Fatal Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Display the Finance Auditor executive monitoring interface.
     */
    public function financeIndex()
    {
        $claims = Claim::with(['items', 'user'])->orderBy('created_at', 'desc')->get();

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
     * Display the active claims auditing workspace for Finance.
     */
    public function auditingIndex()
    {
        $claims = Claim::with(['items', 'user'])->orderBy('created_at', 'desc')->get();

        $pendingCount = Claim::where('status', 'Pending')->count();
        $approvedCount = Claim::where('status', 'Approved')->count();
        $rejectedCount = Claim::where('status', 'Rejected')->count();
        $preApprovedCount = Claim::where('status', 'Pre-Approved')->count();
        $reimbursedCount = Claim::where('status', 'Reimbursed')->count();

        return view('finance.auditing', compact(
            'claims',
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'preApprovedCount',
            'reimbursedCount'
        ));
    }

    /**
     * Display the Manager executive BI analytics dashboard.
     */
    public function managerIndex()
    {
        $preApprovedCount = Claim::where('status', 'Pre-Approved')->count();
        $approvedCount = Claim::where('status', 'Approved')->count();
        $rejectedCount = Claim::where('status', 'Rejected')->count();
        $totalReviewCount = Claim::count();

        $monthlyExpenditures = array_fill(1, 12, 0);

        $realClaimsData = Claim::where('status', 'Approved')
            ->whereYear('transaction_date', date('Y'))
            ->selectRaw('MONTH(transaction_date) as month, SUM(amount) as total_amount')
            ->groupBy('month')
            ->pluck('total_amount', 'month')
            ->toArray();

        foreach ($realClaimsData as $monthNum => $totalSum) {
            $monthlyExpenditures[$monthNum] = (float) $totalSum;
        }

        $managerLineData = array_values($monthlyExpenditures);

        return view('manager.dashboard', compact(
            'preApprovedCount',
            'approvedCount',
            'rejectedCount',
            'totalReviewCount',
            'managerLineData'
        ));
    }

    /**
     * Dedicated workspace desk for managing sign-off status matrices.
     */
    public function managerVerificationIndex()
    {
        $claims = Claim::with(['items', 'user'])->whereIn('status', ['Pre-Approved', 'Approved', 'Rejected'])->orderBy('updated_at', 'desc')->get();

        $preApprovedCount = Claim::where('status', 'Pre-Approved')->count();
        $approvedCount = Claim::where('status', 'Approved')->count();
        $rejectedCount = Claim::where('status', 'Rejected')->count();
        $totalReviewCount = Claim::count();

        return view('manager.verification', compact('claims', 'preApprovedCount', 'approvedCount', 'rejectedCount', 'totalReviewCount'));
    }

    /**
     * Multi-Level Approval State Interception Engine.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:Approved,Rejected,Pre-Approved']);
        $claim = Claim::findOrFail($id);

        $userRole = Auth::check() ? Auth::user()->role : 'Finance';
        $targetStatus = $request->status;

        if ($targetStatus === 'Approved') {
            if ($userRole === 'Finance') {
                $targetStatus = 'Pre-Approved';
                $message = 'Claim successfully verified and escalated to Manager desk.';
            } else {
                $targetStatus = 'Approved';
                $message = 'Claim officially authorized and finalized for payment settlement.';
            }
        } else {
            $message = "Claim status successfully marked as {$targetStatus}.";
        }

        $claim->status = $targetStatus;
        $claim->save();

        // Record Status Sign-off Audit Event
        AuditLog::log(
            "CLAIM_{$targetStatus}",
            "Claim #CLM-{$claim->claim_id} status updated to {$targetStatus} by " . (Auth::user()->name ?? 'Manager'),
            'Claim',
            (string) $claim->claim_id,
            [
                'previous_status' => $claim->getOriginal('status'),
                'new_status' => $targetStatus,
                'signed_by' => Auth::user()->name ?? 'System'
            ]
        );

        // Dispatch Status Update Notification to Staff Owner
        $notifType = $targetStatus === 'Approved' ? 'success' : ($targetStatus === 'Rejected' ? 'danger' : 'info');
        NotificationService::send(
            $claim->user_id,
            "Claim Status: {$targetStatus}",
            "Your claim voucher #CLM-{$claim->claim_id} ({$claim->merchant_name}) has been marked as {$targetStatus}.",
            $notifType,
            route('claims.history')
        );

        return redirect()->back()->with('success', $message);
    }

    /**
     * Handle expenditure persistence with physical duplicate prevention and budget enforcement.
     */
    public function store(Request $request)
    {
        $isMileage = $request->input('claim_type') === 'Mileage';

        $currentUser = Auth::user() ?? \App\Models\User::first();
        $currentUserId = $currentUser->id ?? $currentUser->user_id ?? 1;

        $vehicleId = null;
        $vehiclePlateNumber = null;
        $vehicleType = null;
        $imagePath = null;

        if ($isMileage) {
            $request->validate([
                'title' => 'required|string|max:255',
                'vehicle_id' => 'required|exists:vehicles,vehicle_id',
                'mileage_km' => 'required|string',
                'start_location' => 'required|string',
                'destination_location' => 'required|string',
                'transaction_date' => 'nullable|date',
                'mileage_document' => 'required|image|max:5120',
                'business_purpose' => 'required|string',
            ]);

            $selectedVehicle = Vehicle::findOrFail($request->input('vehicle_id'));

            if ($selectedVehicle->user_id != $currentUserId || $selectedVehicle->ownership_type !== 'personal') {
                return redirect()->back()
                    ->withErrors(['error' => 'Security Interception: You are not authorized to claim mileage using this vehicle.'])
                    ->withInput();
            }

            if ($selectedVehicle->approval_status !== 'Approved') {
                return redirect()->back()
                    ->withErrors(['error' => "Compliance Violation: Vehicle {$selectedVehicle->plate_number} is pending manager verification or has been rejected."])
                    ->withInput();
            }

            if ($selectedVehicle->roadtax_expiry && Carbon::parse($selectedVehicle->roadtax_expiry)->isPast()) {
                return redirect()->back()
                    ->withErrors(['error' => "Submission Denied: Roadtax for vehicle {$selectedVehicle->plate_number} expired on " . Carbon::parse($selectedVehicle->roadtax_expiry)->format('d/m/Y') . ". Please renew before submitting claims."])
                    ->withInput();
            }

            $vehicleId = $selectedVehicle->vehicle_id;
            $vehiclePlateNumber = $selectedVehicle->plate_number;
            $vehicleType = $selectedVehicle->vehicle_type;

            $rawKm = $request->input('mileage_km');
            $km = (float) filter_var($rawKm, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            $rawAmount = $request->input('amount');
            $calculatedAmount = (float) filter_var($rawAmount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            if ($calculatedAmount <= 0) {
                $rate = ($vehicleType === 'Car') ? 0.60 : 0.30;
                $calculatedAmount = $km * $rate;
            }

            if ($request->hasFile('mileage_document')) {
                $imagePath = $request->file('mileage_document')->store('receipts', 'public');
            }

            $merchantName = ($vehicleType === 'Car') ? 'Aero Art Mileage (Car)' : 'Aero Art Mileage (Motorcycle)';
            $predictedCategory = 'Travel & Mileage';
            $paymentMethod = 'Allowance';

            $rawDate = $request->input('transaction_date');
            $targetTransactionDate = ($rawDate && $rawDate !== '')
                ? Carbon::parse($rawDate)->format('Y-m-d')
                : now()->format('Y-m-d');

        } else {
            $request->validate([
                'receipt' => 'required|image|max:5120',
                'merchant_name' => 'required|string',
                'amount' => 'required|numeric',
                'category' => 'required|string',
                'transaction_date' => 'nullable|date',
                'business_purpose' => 'required|string',
            ]);

            $calculatedAmount = (float) $request->input('amount');
            $merchantName = $request->input('merchant_name');
            $predictedCategory = $request->input('category') ?? 'Unassigned';
            $paymentMethod = $request->input('payment_method') ?? 'Cash';
            $targetTransactionDate = Carbon::parse($request->input('transaction_date'))->format('Y-m-d');
            $vehiclePlateNumber = $request->input('vehicle_plate_number');

            if (in_array($predictedCategory, ['Fuel / Automotive', 'Fuel'])) {
                $request->validate([
                    'vehicle_plate_number' => 'required|string|exists:vehicles,plate_number',
                ]);

                $fleetVehicle = Vehicle::where('plate_number', $vehiclePlateNumber)
                    ->where('ownership_type', 'company')
                    ->first();

                if (!$fleetVehicle || $fleetVehicle->status !== 'Active') {
                    return redirect()->back()
                        ->withErrors(['error' => 'Compliance Violation: Selected plate number is not an active corporate fleet asset.'])
                        ->withInput();
                }

                $vehicleId = $fleetVehicle->vehicle_id;
            }

            // Physical Image Hash Duplicate Check
            $uploadedReceiptFile = $request->file('receipt');
            if ($uploadedReceiptFile) {
                $imageHashToCheck = hash('sha256', file_get_contents($uploadedReceiptFile->getRealPath()));
                $duplicateClaim = Claim::where('receipt_image_hash', $imageHashToCheck)->first();

                if ($duplicateClaim) {
                    return redirect()->back()
                        ->withErrors(['duplicate' => "Security Violation: This exact physical receipt was already submitted in voucher #CLM-{$duplicateClaim->claim_id}. Please upload an authentic receipt."])
                        ->withInput();
                }
            }

            // Invoice No & Amount Duplicate Check
            $inputInvoiceNo = strtoupper(trim($request->input('receipt_invoice_no', '')));
            if (!empty($inputInvoiceNo) && $inputInvoiceNo !== 'NOT FOUND') {
                $isDatabaseDuplicate = Claim::where('receipt_invoice_no', $inputInvoiceNo)
                    ->where('amount', $calculatedAmount)
                    ->exists();

                if ($isDatabaseDuplicate) {
                    return redirect()->back()
                        ->withErrors(['duplicate' => "Security Interception: A voucher record with identical Invoice No ({$inputInvoiceNo}) and Amount already exists in the system."])
                        ->withInput();
                }
            }

            if ($request->hasFile('receipt')) {
                $imagePath = $request->file('receipt')->store('receipts', 'public');
            }
        }

        // Budget Enforcement Service
        $policyCheck = BudgetEnforcementService::checkPolicy(
            (int) $currentUserId,
            $predictedCategory,
            $calculatedAmount
        );

        // Fraud Scoring Service
        $uploadedFile = $request->file('receipt') ?? $request->file('mileage_document');
        $rawOcrAmount = (float) $request->input('raw_ocr_amount', 0.00);

        $fraudEvaluation = FraudDetectionService::evaluateClaimRisk(
            $uploadedFile,
            $calculatedAmount,
            $rawOcrAmount > 0 ? $rawOcrAmount : null,
            $targetTransactionDate
        );

        // Active Learning Feedback
        $initialAiPrediction = $request->input('ai_raw_prediction') ?? $request->input('predicted_category');
        $finalSelectedCategory = $predictedCategory;

        if (!empty($initialAiPrediction) && !empty($finalSelectedCategory) && $initialAiPrediction !== $finalSelectedCategory) {
            ActiveLearningService::processFeedback(
                (int) $currentUserId,
                $merchantName,
                $initialAiPrediction,
                $finalSelectedCategory,
                $request->input('extracted_raw_text')
            );
        }

        DB::beginTransaction();
        try {
            $claim = Claim::create([
                'user_id' => $currentUserId,
                'vehicle_id' => $vehicleId,
                'claim_type' => $request->input('claim_type') ?? 'Receipt',
                'title' => $request->input('title') ?? ('Claim at ' . $merchantName),
                'merchant_name' => $merchantName,
                'location_address' => $request->input('location_address') ?? ($isMileage ? $request->input('start_location') : 'Unknown Address'),
                'receipt_invoice_no' => $request->input('receipt_invoice_no') ?? 'NOT FOUND',
                'transaction_date' => $targetTransactionDate,
                'amount' => $calculatedAmount,
                'mileage_km' => $isMileage ? $km : null,
                'vehicle_type' => $vehicleType,
                'start_location' => $isMileage ? $request->input('start_location') : null,
                'destination_location' => $isMileage ? $request->input('destination_location') : null,
                'payment_method' => $paymentMethod,
                'predicted_category' => $predictedCategory,
                'business_purpose' => $request->input('business_purpose') ?? 'Business expense',
                'receipt_image_path' => $imagePath ?? 'receipts/default.png',
                'receipt_image_hash' => $fraudEvaluation['image_hash'],
                'risk_score' => $fraudEvaluation['score'],
                'fraud_flags' => $fraudEvaluation['flags'],
                'exif_date_taken' => $fraudEvaluation['exif_date'],
                'extracted_raw_text' => trim($request->input('extracted_raw_text', '')),
                'vehicle_plate_number' => $vehiclePlateNumber,
                'status' => 'Pending',
                'is_policy_violation' => $policyCheck['is_violation'],
                'policy_violation_reason' => $policyCheck['reason'],
                'estimated_payout_date' => Carbon::now()->addWeekdays(5)->toDateString(),
            ]);

            $itemsData = $request->input('items', []);
            if (is_string($itemsData)) {
                $itemsData = json_decode($itemsData, true) ?? [];
            }

            if (!empty($itemsData)) {
                foreach ($itemsData as $item) {
                    if (!empty($item['item_name']) && !empty($item['subtotal'])) {
                        ClaimItem::create([
                            'claim_id' => $claim->claim_id,
                            'item_name' => trim($item['item_name']),
                            'quantity' => (int) ($item['quantity'] ?? 1),
                            'unit_price' => (float) ($item['unit_price'] ?? $item['subtotal']),
                            'subtotal' => (float) $item['subtotal'],
                        ]);
                    }
                }
            }

            AuditLog::log(
                'CLAIM_SUBMITTED',
                "Staff submitted expense voucher #CLM-{$claim->claim_id} ({$merchantName}) valued at RM " . number_format($calculatedAmount, 2),
                'Claim',
                (string) $claim->claim_id,
                [
                    'type' => $claim->claim_type,
                    'amount' => $calculatedAmount,
                    'risk_score' => $fraudEvaluation['score'],
                    'is_violation' => $policyCheck['is_violation']
                ]
            );

            DB::commit();

            NotificationService::send(
                $currentUserId,
                'Claim Submitted',
                "Your claim voucher #CLM-{$claim->claim_id} for RM " . number_format($calculatedAmount, 2) . " has been submitted for audit.",
                'info',
                route('claims.history')
            );

            if ($fraudEvaluation['score'] >= 50 || $policyCheck['is_violation']) {
                NotificationService::notifyManagers(
                    'Audit Alert: Flagged Claim',
                    "Staff submitted claim #CLM-{$claim->claim_id} flagged with " . ($policyCheck['is_violation'] ? 'Policy Breach' : "High Risk ({$fraudEvaluation['score']}%)"),
                    'danger',
                    route('manager.verification') . '?status=Pre-Approved'
                );
            }

            return redirect()->route('dashboard')->with('success', 'Claim voucher submitted successfully for audit.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Claim Submission Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Database Error: ' . $e->getMessage()])->withInput();
        }
    }

    public function profileIndex()
    {
        $user = auth()->user();
        return view('profile.index', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'bank_name' => 'required|string|max:100',
            'bank_account_no' => 'required|string|max:50',
            'bank_account_holder' => 'required|string|max:150',
            'phone_number' => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return redirect()->back()->with('success', 'Banking and profile particulars successfully updated.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('success', 'Account password successfully updated.');
    }

    /**
     * Load live mileage rates and category expense policy caps.
     */
    public function policyIndex()
    {
        $mileageRates = collect();
        if (class_exists(MileageRate::class)) {
            $carRate = MileageRate::whereRaw('LOWER(vehicle_type) = ?', ['car'])->latest()->first();
            $motorRate = MileageRate::whereRaw('LOWER(vehicle_type) = ?', ['motorcycle'])->latest()->first();

            if ($carRate)
                $mileageRates->push($carRate);
            if ($motorRate)
                $mileageRates->push($motorRate);
        }

        $expensePolicies = class_exists(ExpensePolicy::class)
            ? ExpensePolicy::where('is_active', true)->get()
            : collect();

        return view('policy.index', compact('expensePolicies', 'mileageRates'));
    }

    public function mileageRatesIndex()
    {
        return view('manager.mileage_rates');
    }

    public function expenseCategoriesIndex()
    {
        return view('manager.expense_categories');
    }

    public function userManagementIndex()
    {
        $users = User::orderBy('name', 'asc')->get();
        return view('manager.user_management', compact('users'));
    }

    public function financeStaffDirectoryIndex()
    {
        $staffMembers = User::where('role', 'staff')
            ->orWhereNull('role')
            ->orderBy('name', 'asc')
            ->get();

        return view('finance.staff_directory', compact('staffMembers'));
    }

    public function auditLogsIndex(Request $request)
    {
        $eventType = $request->input('event_type');

        $logs = AuditLog::with('user')
            ->when($eventType, fn($q) => $q->where('event_type', $eventType))
            ->latest()
            ->paginate(15);

        return view('manager.audit_logs', compact('logs'));
    }

    public function financeProfileIndex()
    {
        return view('finance.profile');
    }

    /**
     * Compute organization-wide transaction records to generate Finance BI statistics.
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
     * Executive BI Reports & Analytics Dashboard (Manager Portal).
     */
    public function managerReportsIndex(Request $request)
    {
        $year = $request->input('year', date('Y'));

        $pendingCount = Claim::where('status', 'Pending')->count();
        $preApprovedCount = Claim::where('status', 'Pre-Approved')->count();
        $totalClaims = Claim::whereYear('created_at', $year)->count();

        $totalApprovedRM = (float) Claim::whereYear('created_at', $year)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->sum('amount');

        $totalPendingRM = (float) Claim::whereYear('created_at', $year)
            ->whereIn('status', ['Pending', 'Pre-Approved', 'Approved'])
            ->sum('amount');

        $totalApprovedCount = Claim::whereYear('created_at', $year)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->count();

        $avgClaim = Claim::whereYear('created_at', $year)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->avg('amount') ?? 0.0;

        $monthlyClaims = Claim::whereYear('created_at', $year)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->selectRaw('MONTH(created_at) as month, SUM(amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyData[] = (float) ($monthlyClaims[$m] ?? 0.00);
        }

        $categoryBreakdown = Claim::whereYear('created_at', $year)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->select(
                DB::raw('COALESCE(predicted_category, "General") as category_name'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('category_name')
            ->get();

        $categoryLabels = $categoryBreakdown->pluck('category_name')->toArray();
        $categoryTotals = $categoryBreakdown->pluck('total')->map(fn($val) => (float) $val)->toArray();

        if (empty($categoryLabels)) {
            $categoryLabels = ['Meals & Entertainment', 'Fuel / Automotive', 'Office Supplies'];
            $categoryTotals = [0, 0, 0];
        }

        $topStaff = Claim::whereYear('created_at', $year)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->whereNotNull('user_id')
            ->with(['user', 'items'])
            ->select(
                'user_id',
                DB::raw('SUM(amount) as total_spent'),
                DB::raw('COUNT(*) as total_claims')
            )
            ->groupBy('user_id')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get()
            ->map(function ($item) use ($year) {
                $staffClaims = Claim::where('user_id', $item->user_id)
                    ->whereYear('created_at', $year)
                    ->whereIn('status', ['Approved', 'Reimbursed'])
                    ->with('items')
                    ->orderBy('created_at', 'desc')
                    ->get();

                return (object) [
                    'user_id' => $item->user_id,
                    'name' => $item->user->name ?? 'Unknown Staff',
                    'user_role' => $item->user->role ?? 'Staff',
                    'total_spent' => (float) $item->total_spent,
                    'total_claims' => $item->total_claims,
                    'claims_list' => $staffClaims
                ];
            });

        $merchantData = Claim::whereYear('created_at', $year)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->whereNotNull('merchant_name')
            ->select('merchant_name', DB::raw('SUM(amount) as total_spend'))
            ->groupBy('merchant_name')
            ->orderByDesc('total_spend')
            ->limit(6)
            ->get();

        $recentClaims = Claim::with('user')->orderBy('created_at', 'desc')->limit(8)->get();

        return view('manager.reports', compact(
            'year',
            'pendingCount',
            'preApprovedCount',
            'totalClaims',
            'totalApprovedRM',
            'totalPendingRM',
            'totalApprovedCount',
            'avgClaim',
            'monthlyData',
            'categoryLabels',
            'categoryTotals',
            'topStaff',
            'merchantData',
            'recentClaims'
        ));
    }

    public function managerProfileIndex()
    {
        $pendingCount = Claim::where('status', 'Pending')->count();
        $preApprovedCount = Claim::where('status', 'Pre-Approved')->count();
        $totalReviewCount = Claim::count();
        $totalClaims = Claim::count();
        $totalAmount = Claim::where('status', 'Approved')->sum('amount');
        $avgClaim = Claim::where('status', 'Approved')->avg('amount') ?? 0;

        $merchantData = Claim::where('status', 'Approved')
            ->select('merchant_name', DB::raw('SUM(amount) as total_spend'))
            ->groupBy('merchant_name')
            ->orderByDesc('total_spend')
            ->limit(8)
            ->get();

        $categoryData = Claim::where('status', 'Approved')
            ->select('predicted_category', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('predicted_category')
            ->get();

        $recentClaims = Claim::with('user')->orderBy('created_at', 'desc')->limit(10)->get();

        return view('manager.profile', compact(
            'pendingCount',
            'preApprovedCount',
            'totalReviewCount',
            'totalClaims',
            'totalAmount',
            'avgClaim',
            'merchantData',
            'categoryData',
            'recentClaims'
        ));
    }

    public function vehiclesIndex()
    {
        return view('manager.vehicles');
    }

    /**
     * Procurement Price Intelligence & Cross-Merchant Price Comparison Engine.
     */
    public function priceIntelligenceIndex(Request $request)
    {
        $search = $request->input('search');

        $itemsQuery = DB::table('claim_items')
            ->join('claims', 'claim_items.claim_id', '=', 'claims.claim_id')
            ->select(
                'claim_items.item_name',
                'claims.merchant_name',
                DB::raw('AVG(claim_items.unit_price) as avg_price'),
                DB::raw('MIN(claim_items.unit_price) as min_price'),
                DB::raw('MAX(claim_items.unit_price) as max_price'),
                DB::raw('COUNT(*) as purchase_count'),
                DB::raw('MAX(claims.transaction_date) as last_purchased_date')
            )
            ->whereNotNull('claims.merchant_name')
            ->when($search, fn($q) => $q->where('claim_items.item_name', 'like', "%{$search}%"))
            ->groupBy('claim_items.item_name', 'claims.merchant_name')
            ->orderBy('claim_items.item_name')
            ->get();

        $comparisonData = [];
        $grouped = $itemsQuery->groupBy('item_name');

        foreach ($grouped as $itemName => $merchants) {
            $sortedByPrice = $merchants->sortBy('avg_price');
            $cheapest = $sortedByPrice->first();
            $mostExpensive = $sortedByPrice->last();
            $priceDiff = $mostExpensive->avg_price - $cheapest->avg_price;
            $savingsPercent = $mostExpensive->avg_price > 0
                ? round(($priceDiff / $mostExpensive->avg_price) * 100)
                : 0;

            $comparisonData[] = (object) [
                'item_name' => $itemName,
                'total_purchases' => $merchants->sum('purchase_count'),
                'cheapest_merchant' => $cheapest->merchant_name,
                'cheapest_price' => $cheapest->avg_price,
                'expensive_merchant' => $mostExpensive->merchant_name,
                'expensive_price' => $mostExpensive->avg_price,
                'potential_savings_pct' => $savingsPercent,
                'merchant_breakdown' => $merchants
            ];
        }

        $topFrequentItems = DB::table('claim_items')
            ->select('item_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_spend'))
            ->groupBy('item_name')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        return view('manager.price_intelligence', compact('comparisonData', 'topFrequentItems', 'search'));
    }

    /**
     * Display Finance Payment Disbursement Reconciliation Desk.
     */
    public function financeDisbursementIndex(Request $request)
    {
        $tab = $request->input('tab', 'pending');

        $pendingDisbursements = Claim::with(['user', 'items'])
            ->where('status', 'Approved')
            ->orderBy('updated_at', 'desc')
            ->get();

        $settledDisbursements = Claim::with(['user', 'items'])
            ->where('status', 'Reimbursed')
            ->orderBy('paid_at', 'desc')
            ->take(30)
            ->get();

        $totalPendingAmount = $pendingDisbursements->sum('amount');
        $totalSettledAmount = Claim::where('status', 'Reimbursed')->sum('amount');

        return view('finance.disbursement', compact(
            'pendingDisbursements',
            'settledDisbursements',
            'totalPendingAmount',
            'totalSettledAmount',
            'tab'
        ));
    }

    /**
     * Execute Bank Payout Settlement and Mark Claim as Reimbursed.
     */
    public function processDisbursement(Request $request, $id)
    {
        $request->validate([
            'payment_reference' => 'required|string|max:100',
            'payment_proof' => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ]);

        $claim = Claim::findOrFail($id);

        $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');

        $claim->status = 'Reimbursed';
        $claim->payment_reference = $request->input('payment_reference');
        $claim->paid_at = Carbon::now();
        $claim->payment_proof_path = $proofPath;
        $claim->save();

        AuditLog::log(
            'PAYMENT_DISBURSED',
            "Payment of RM " . number_format($claim->amount, 2) . " settled to staff for #CLM-{$claim->claim_id} (Ref: {$claim->payment_reference})",
            'Claim',
            (string) $claim->claim_id,
            [
                'amount' => $claim->amount,
                'payment_reference' => $claim->payment_reference,
                'proof_file' => $proofPath,
                'disbursed_by' => Auth::user()->name ?? 'Finance Officer'
            ]
        );

        NotificationService::send(
            $claim->user_id,
            'Payment Reimbursed',
            "Your claim voucher #CLM-{$claim->claim_id} (RM " . number_format($claim->amount, 2) . ") has been successfully paid out. Reference: {$claim->payment_reference}",
            'success',
            route('reimbursement.index')
        );

        return redirect()->back()->with('success', "Payment voucher #CLM-{$claim->claim_id} successfully settled and marked as Reimbursed.");
    }

    /**
     * AI OCR Engine for Malaysian Bank Slips & PDFs.
     */
    public function asyncScanBankSlip(Request $request)
    {
        if (!$request->hasFile('payment_proof')) {
            return response()->json(['success' => false, 'message' => 'No payment slip detected.']);
        }

        try {
            $uploadedFile = $request->file('payment_proof');
            $mimeType = $uploadedFile->getClientMimeType();
            $extension = strtolower($uploadedFile->getClientOriginalExtension());
            $imagePath = $uploadedFile->store('receipts/temp', 'public');
            $fullImagePath = storage_path('app/public/' . $imagePath);
            $fileData = file_get_contents($fullImagePath);
            $extractedText = '';

            $apiKey = env('GOOGLE_CLOUD_API_KEY');

            // PDF Text Stream Parsing
            if ($extension === 'pdf' || str_contains($mimeType, 'pdf')) {
                if (preg_match_all('/(?:\((.*?)\)\s*Tj|\[(.*?)\]\s*TJ)/s', $fileData, $streamMatches)) {
                    $collected = [];
                    foreach ($streamMatches[1] as $item) {
                        if (!empty($item))
                            $collected[] = $item;
                    }
                    foreach ($streamMatches[2] as $item) {
                        if (!empty($item)) {
                            preg_match_all('/\((.*?)\)/', $item, $inner);
                            if (!empty($inner[1]))
                                $collected[] = implode('', $inner[1]);
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
                return response()->json(['success' => false, 'message' => 'Could not read text layers from the uploaded file.']);
            }

            // Extract Reference ID
            $referenceId = '';
            if (preg_match('/(?:Paynet\s*Ref\s*No|Paynet\s*Ref|PayNet\s*Reference)[:\s\.]+([A-Za-z0-9]{15,})/i', $extractedText, $paynetMatch)) {
                $referenceId = trim($paynetMatch[1]);
            } elseif (preg_match('/(?:Channel\s*Ref\s*No|Reference\s*No|Reference\s*ID|Ref\s*No|Txn\s*ID|Transaction\s*ID)[:\s\.]+([A-Za-z0-9]{10,})/i', $extractedText, $channelMatch)) {
                $referenceId = trim($channelMatch[1]);
            } elseif (preg_match('/\b(202[4-6][A-Za-z0-9]{10,})\b/', $extractedText, $seqMatch)) {
                $referenceId = trim($seqMatch[1]);
            }

            // Extract Timestamp
            $transferTime = date('d M Y, h:i A');
            if (preg_match('/(\d{1,2}\s+[A-Za-z]{3}\s+202[4-6])[\s,]+(\d{1,2}:\d{2}(?::\d{2})?\s*(?:AM|PM|am|pm)?)/', $extractedText, $timeMatch)) {
                $transferTime = trim($timeMatch[1]) . ', ' . trim($timeMatch[2]);
            } elseif (preg_match('/(\d{1,2}[\/\.-]\d{1,2}[\/\.-]202[4-6])[\s,]+(\d{1,2}:\d{2}(?::\d{2})?\s*(?:AM|PM|am|pm)?)/', $extractedText, $timeMatch2)) {
                $transferTime = trim($timeMatch2[1]) . ', ' . trim($timeMatch2[2]);
            }

            // Extract Transferred Amount
            $transferredAmount = '0.00';
            if (preg_match('/(?:Total\s*Amount|Amount)[\s:\.\$]*RM\s*([0-9]+\.\d{2})/i', $extractedText, $amtMatch)) {
                $transferredAmount = number_format((float) $amtMatch[1], 2, '.', '');
            } elseif (preg_match('/(?:RM|MYR)[\s:\.\$]*([0-9]+\.\d{2})\b/i', $extractedText, $amtMatch2)) {
                $transferredAmount = number_format((float) $amtMatch2[1], 2, '.', '');
            }

            return response()->json([
                'success' => true,
                'payment_reference' => $referenceId ?: '20260825' . strtoupper(substr(md5(time()), 0, 8)),
                'transfer_time' => $transferTime,
                'transferred_amount' => $transferredAmount,
                'raw_text' => $extractedText
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Process Batch Disbursement of multiple claims simultaneously with a single proof slip.
     */
    public function processBatchDisbursement(Request $request)
    {
        $request->validate([
            'claim_ids' => 'required|array|min:1',
            'claim_ids.*' => 'required|integer|exists:claims,claim_id',
            'payment_reference' => 'required|string|max:100',
            'payment_proof' => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240',
        ]);

        $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');
        $now = now();
        $paymentRef = trim($request->input('payment_reference'));

        $claims = Claim::whereIn('claim_id', $request->input('claim_ids'))->get();

        foreach ($claims as $claim) {
            $claim->update([
                'status' => 'Reimbursed',
                'payment_reference' => $paymentRef,
                'payment_proof_path' => $proofPath,
                'paid_at' => $now,
            ]);

            try {
                NotificationService::send(
                    $claim->user_id,
                    'Payment Reimbursed: #CLM-' . $claim->claim_id,
                    "Your claim #CLM-{$claim->claim_id} of RM " . number_format($claim->amount, 2) . " has been paid via batch reference: {$paymentRef}.",
                    'success',
                    route('dashboard')
                );
            } catch (\Throwable $th) {
                // Ignore individual push notification failures
            }
        }

        return redirect()->route('finance.disbursement', ['tab' => 'settled'])
            ->with('success', 'Batch disbursement successfully processed for ' . count($claims) . ' vouchers.');
    }
}