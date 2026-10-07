<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Claim;
use App\Models\ClaimItem;
use App\Models\Vehicle;
use App\Models\MileageRate;
use App\Models\AuditLog;
use App\Models\ExpensePolicy;
use App\Models\Category;
use App\Models\ClaimAuditReason;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
use App\Services\EmailDeliveryService;
use App\Mail\PasswordResetSuccessMail;
use Barryvdh\DomPDF\Facade\Pdf;

class ClaimController extends Controller
{
    // Comment: Render regular employee analytics dashboard
    public function index()
    {
        $currentUserId = Auth::id() ?? 1;

        $totalSpending = Claim::where('user_id', $currentUserId)
            ->whereIn('status', ['Approved', 'Reimbursed'])
            ->sum('amount');

        $approvedCount = Claim::where('user_id', $currentUserId)->whereIn('status', ['Approved', 'Reimbursed'])->count();
        $preApprovedCount = Claim::where('user_id', $currentUserId)->where('status', 'Pre-Approved')->count();
        $pendingCount = Claim::where('user_id', $currentUserId)->where('status', 'Pending')->count();
        $rejectedCount = Claim::where('user_id', $currentUserId)->where('status', 'Rejected')->count();
        $totalClaimsCount = Claim::where('user_id', $currentUserId)->count();

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

    // Comment: Render employee reimbursement ledger
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

    // Comment: Show claim creation view with active vehicle assets
    public function create()
    {
        $currentUserId = Auth::id() ?? 1;

        $personalVehicles = Vehicle::where('user_id', $currentUserId)
            ->where('ownership_type', 'personal')
            ->where('approval_status', 'Approved')
            ->whereDate('roadtax_expiry', '>=', Carbon::today())
            ->orderBy('plate_number', 'asc')
            ->get();

        $companyFleet = Vehicle::where('ownership_type', 'company')
            ->where('status', 'Active')
            ->orderBy('plate_number', 'asc')
            ->get();

        $categories = Category::where('is_active', true)->get();

        $expensePolicies = \App\Models\ExpensePolicy::with('category')->where('is_active', true)->get()->map(function ($policy) {
            $policy->category_name = $policy->category->name ?? '';
            return $policy;
        });
        return view('claims.create', compact('personalVehicles', 'companyFleet', 'categories', 'expensePolicies'));
    }

    public function history(\Illuminate\Http\Request $request)
    {
        $claimsQuery = Claim::with(['items', 'auditLogs.user'])->where('user_id', auth()->id())->latest();

        if ($request->wantsJson() || $request->ajax()) {
            $claims = $claimsQuery->get()->map(function($c) {
                return array_merge($c->toArray(), [
                    'items' => $c->items->toArray(),
                    'created_at_formatted' => $c->created_at->format('Y-m-d H:i'),
                    'audit_logs' => $c->auditLogs->map(fn($log) => array_merge($log->toArray(), [
                        'user_name' => $log->user->name ?? 'System',
                        'user_role' => $log->user->role ?? 'System',
                    ]))->values()->toArray(),
                    'forensic_hash' => hash('sha256', $c->claim_id . $c->created_at . $c->amount),
                    'estimated_completion_at' => $c->estimated_completion_at ? $c->estimated_completion_at->format('M d, Y') : null,
                    'sla_status' => $c->sla_status,
                    'time_remaining_human' => $c->time_remaining_human,
                ]);
            });
            return response()->json($claims);
        }

                $slaTrackingService = app(\App\Services\SlaTrackingService::class);
        $avgManagerTat = $slaTrackingService->getAverageManagerTurnaroundTime();
        $avgFinanceTat = $slaTrackingService->getAverageFinanceSettlementTime();
        
        return view('claims.history', compact('avgManagerTat', 'avgFinanceTat'));
    }

    public function checkDuplicate(Request $request)
    {
        $exists = Claim::where('receipt_invoice_no', trim($request->invoice_no))
            ->where('amount', $request->amount)
            ->exists();

        return response()->json(['duplicate' => $exists]);
    }

    // Comment: Universal Malaysian Receipt AI OCR Engine
    public function asyncScan(Request $request)
    {
        if (!$request->hasFile('receipt')) {
            return response()->json(['success' => false, 'message' => 'No receipt file upload detected.']);
        }

        try {
            $uploadedFile = $request->file('receipt');
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
                    // Suppress and bypass exif read failures
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
                if (preg_match('/(?:SDN\s*BHD|BHD|ENTERPRISE|MART|GROCER|STATION|PETRONAS|SHELL|PETRON|CALTEX|KK\s*SUPERMART|7-ELEVEN|TEXAS|TEALIVE|STARBUCKS|DIY|MR\s*DIY|ACE\s*HARDWARE|BOOKSTORE|RESTAURANT|CAFE|BAKERY|MYDIN|LOTUS|GIANT|WATSONS|GUARDIAN|SPEEDMART|PASARAYA|SUPERMARKET)/i', $candidateLine)) {
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

            // Fetch Categories from DB
            $activeCategories = Category::where('is_active', true)->get();
            $idfDictionary = [];
            $categoryScores = [];

            foreach ($activeCategories as $category) {
                $categoryScores[$category->name] = 0.0;
                $keywords = array_filter(array_map('trim', explode(',', $category->keywords ?? '')));
                $idfDictionary[$category->name] = [];
                foreach ($keywords as $kw) {
                    $idfDictionary[$category->name][strtolower($kw)] = 2.0; // Default weight
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

            // Extract Tax/SST Amount
            $taxAmount = '0.00';
            if (preg_match('/(?:TAX|GST|SST|CUKAI|VAT)[\s\:]*(?:RM|MYR)?[\s]*([\d\.,]+)/i', $processedText, $taxMatches)) {
                $taxAmount = number_format((float) str_replace(',', '', $taxMatches[1]), 2, '.', '');
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
                'tax_amount' => $taxAmount,
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

    // Comment: Display Finance Auditor dashboard
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

    public function auditingIndex(\Illuminate\Http\Request $request)
    {
        $claimsQuery = Claim::with(['items', 'user', 'auditLogs.user'])->orderBy('created_at', 'desc');

        if ($request->wantsJson() || $request->ajax()) {
            $claims = $claimsQuery->get()->map(function($c) {
                return array_merge($c->toArray(), [
                    'user_name' => $c->user->name ?? 'Unknown Staff',
                    'user_role' => $c->user->role ?? 'Staff',
                    'formatted_time' => $c->created_at->format('Y-m-d H:i'),
                    'items' => $c->items->toArray(),
                    'amount' => (float)$c->amount > 0 ? (float)$c->amount : (float)$c->calculated_amount,
                    'audit_logs' => $c->auditLogs->map(fn($log) => array_merge($log->toArray(), [
                        'user_name' => $log->user->name ?? 'System',
                        'user_role' => $log->user->role ?? 'System',
                    ]))->values()->toArray(),
                    'forensic_hash' => hash('sha256', $c->claim_id . $c->created_at . ((float)$c->amount > 0 ? (float)$c->amount : (float)$c->calculated_amount)),
                ]);
            });
            return response()->json($claims);
        }

        $pendingCount = Claim::where('status', 'Pending')->count();
        $approvedCount = Claim::where('status', 'Approved')->count();
        $rejectedCount = Claim::where('status', 'Rejected')->count();
        $preApprovedCount = Claim::where('status', 'Pre-Approved')->count();
        $reimbursedCount = Claim::where('status', 'Reimbursed')->count();

        $revisionReasons = ClaimAuditReason::revisions()->get();
        $rejectionReasons = ClaimAuditReason::rejections()->get();

        return view('finance.auditing', compact(
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'preApprovedCount',
            'reimbursedCount',
            'revisionReasons',
            'rejectionReasons'
        ));
    }

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

    public function managerVerificationIndex(\Illuminate\Http\Request $request)
    {
        $currentTab = $request->query('tab', 'pending');

        $claimsQuery = Claim::with(['items', 'user', 'auditLogs.user'])->orderBy('updated_at', 'desc');

        if ($currentTab === 'pending') {
            $claimsQuery->whereIn('status', ['Pending', 'Pre-Approved', 'Pending Manager']);
        } elseif ($currentTab === 'approved') {
            $claimsQuery->where('status', 'Approved');
        } elseif ($currentTab === 'rejected') {
            $claimsQuery->where('status', 'Rejected');
        } else {
            $claimsQuery->whereIn('status', ['Pending', 'Pre-Approved', 'Pending Manager']);
        }

        $claims = $claimsQuery->paginate(15);
        $claims->getCollection()->transform(function($c) {
            $c->user_name = $c->user->name ?? 'Staff User';
            return $c;
        });

        $preApprovedCount = Claim::whereIn('status', ['Pending', 'Pre-Approved', 'Pending Manager'])->count();
        $approvedCount = Claim::where('status', 'Approved')->count();
        $rejectedCount = Claim::where('status', 'Rejected')->count();
        $totalReviewCount = Claim::count();

        $revisionReasons = ClaimAuditReason::revisions()->get();
        $rejectionReasons = ClaimAuditReason::rejections()->get();

        return view('manager.verification', compact('claims', 'currentTab', 'preApprovedCount', 'approvedCount', 'rejectedCount', 'totalReviewCount', 'revisionReasons', 'rejectionReasons'));
    }

    // Comment: Multi-Level Approval State Interception Engine
    public function updateStatus(Request $request, $id)
    {
        $targetStatus = $request->status;

        $rules = [
            'status' => 'required|in:Approved,Rejected,Pre-Approved,REVISION_REQUIRED',
        ];

        // Conditional Validation Engine: Revision vs Rejection
        if ($targetStatus === 'REVISION_REQUIRED') {
            $rules['revision_reason'] = 'required|string|max:255';

            // Check if selected revision reason requires mandatory remarks
            $chosenReason = ClaimAuditReason::where('type', 'REVISION')
                ->where(function($q) use ($request) {
                    $q->where('title', $request->revision_reason)
                      ->orWhere('code', $request->revision_reason);
                })->first();

            $isRemarksMandatory = ($chosenReason && $chosenReason->requires_remarks) 
                || stripos($request->revision_reason ?? '', 'Other') !== false;

            if ($isRemarksMandatory) {
                $rules['remarks'] = 'required|string|min:5|max:1000';
            } else {
                $rules['remarks'] = 'nullable|string|max:1000';
            }
        } elseif ($targetStatus === 'Rejected') {
            $rules['rejection_reason'] = 'required|string|max:255';

            // Check if selected rejection reason requires mandatory remarks
            $chosenReason = ClaimAuditReason::where('type', 'REJECTION')
                ->where(function($q) use ($request) {
                    $q->where('title', $request->rejection_reason)
                      ->orWhere('code', $request->rejection_reason);
                })->first();

            $isRemarksMandatory = ($chosenReason && $chosenReason->requires_remarks)
                || stripos($request->rejection_reason ?? '', 'Other') !== false;

            if ($isRemarksMandatory) {
                $rules['remarks'] = 'required|string|min:5|max:1000';
            } else {
                $rules['remarks'] = 'nullable|string|max:1000';
            }
        } else {
            $rules['remarks'] = 'nullable|string|max:1000';
        }

        $request->validate($rules);
        
        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $id, $targetStatus) {
            $claim = Claim::lockForUpdate()->findOrFail($id);

            $userRole = Auth::check() ? Auth::user()->role : 'Finance';
            
            $normalizedRole = strtolower(trim($userRole));
            if (!in_array($normalizedRole, ['manager', 'finance', 'fin'])) {
                abort(403, 'Unauthorized action: Only Managers and Finance Officers can update claim status.');
            }

            if (Auth::id() === $claim->user_id) {
                abort(403, 'Unauthorized action: You cannot approve or reject your own claim.');
            }

            $finalStatus = $targetStatus;

            if ($targetStatus === 'Approved') {
                if ($userRole === 'Finance') {
                    $finalStatus = 'Pre-Approved';
                    $message = 'Claim successfully verified and escalated to Manager desk.';
                } else {
                    $finalStatus = 'Approved';
                    $message = 'Claim officially authorized and finalized for payment settlement.';
                }
            } elseif ($targetStatus === 'REVISION_REQUIRED') {
                $claim->revision_reason = $request->input('revision_reason');
                $claim->remarks = $request->input('remarks');
                $message = "Claim successfully returned to staff for revision.";
            } elseif ($targetStatus === 'Rejected') {
                $claim->rejection_reason = $request->input('rejection_reason');
                $claim->remarks = $request->input('remarks');
                $message = "Claim status successfully marked as Rejected.";
            } else {
                $message = "Claim status successfully marked as {$finalStatus}.";
            }

            $claim->status = $finalStatus;
            $claim->save();

            AuditLog::log(
                "CLAIM_{$finalStatus}",
                $claim->claim_id,
                ['status' => $claim->getOriginal('status')],
                [
                    'status' => $finalStatus,
                    'signed_by' => Auth::user()->name ?? 'System',
                    'actor_role' => Auth::user()->role ?? 'System',
                    'revision_reason' => $claim->revision_reason,
                    'rejection_reason' => $claim->rejection_reason,
                    'remarks' => $claim->remarks ?? $request->input('remarks', null)
                ]
            );

            $notifType = $finalStatus === 'Approved' ? 'success' : ($finalStatus === 'Rejected' ? 'danger' : ($finalStatus === 'REVISION_REQUIRED' ? 'warning' : 'info'));
            
            $staffMsg = "Your claim voucher #CLM-{$claim->claim_id} ({$claim->merchant_name}, RM " . number_format($claim->total_amount, 2) . ") has been marked as {$finalStatus}.";
            if ($finalStatus === 'REVISION_REQUIRED' && !empty($claim->revision_reason)) {
                $staffMsg .= " Reason: {$claim->revision_reason}" . (!empty($claim->remarks) ? " ({$claim->remarks})" : "");
            } elseif ($finalStatus === 'Rejected' && !empty($claim->rejection_reason)) {
                $staffMsg .= " Reason: {$claim->rejection_reason}" . (!empty($claim->remarks) ? " ({$claim->remarks})" : "");
            }

            NotificationService::send(
                $claim->user_id,
                "Claim Status: {$finalStatus}",
                $staffMsg,
                $notifType,
                route('claims.history')
            );

            // Cross-department notification routing between Manager and Finance
            if ($finalStatus === 'Pre-Approved') {
                NotificationService::notifyManagers(
                    "Claim Escalated for Approval: #CLM-{$claim->claim_id}",
                    "Finance officer " . (Auth::user()->name ?? 'Finance') . " has audited claim #CLM-{$claim->claim_id} ({$claim->merchant_name}, RM " . number_format($claim->total_amount, 2) . "). Awaiting executive authorization.",
                    'info',
                    route('manager.verification')
                );
            } elseif ($finalStatus === 'Approved') {
                NotificationService::notifyFinance(
                    "Claim Approved for Reimbursement: #CLM-{$claim->claim_id}",
                    "Manager " . (Auth::user()->name ?? 'Manager') . " has authorized claim #CLM-{$claim->claim_id} ({$claim->merchant_name}, RM " . number_format($claim->total_amount, 2) . ") for payment disbursement.",
                    'success',
                    route('finance.disbursement')
                );
            }

            return redirect()->back()->with('success', $message);
        });
    }

    // Comment: Handle expenditure persistence with physical duplicate prevention, fleet compliance and budget enforcement
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
                'mileage_km' => 'required|numeric|min:0.1',
                'start_location' => 'required|string',
                'destination_location' => 'required|string',
                'transaction_date' => 'nullable|date',
                'mileage_document' => 'required|image|max:5120',
                'business_purpose' => 'required|string',
            ]);

            $selectedVehicle = Vehicle::findOrFail($request->input('vehicle_id'));

            // Mileage claim is strictly for personal vehicle usage
            if ($selectedVehicle->ownership_type !== 'personal') {
                return redirect()->back()
                    ->withErrors(['vehicle_id' => 'Policy Violation: Company fleet assets are prohibited from claiming mileage allowance. Use Fuel Receipt claims instead.'])
                    ->withInput();
            }

            if ($selectedVehicle->user_id != $currentUserId) {
                return redirect()->back()
                    ->withErrors(['vehicle_id' => 'Security Interception: You are not authorized to claim mileage using this vehicle.'])
                    ->withInput();
            }

            if ($selectedVehicle->approval_status !== 'Approved') {
                return redirect()->back()
                    ->withErrors(['vehicle_id' => "Compliance Violation: Vehicle {$selectedVehicle->plate_number} is pending manager verification or has been rejected."])
                    ->withInput();
            }

            if ($selectedVehicle->roadtax_expiry && Carbon::parse($selectedVehicle->roadtax_expiry)->endOfDay()->isPast()) {
                return redirect()->back()
                    ->withErrors(['vehicle_id' => "Submission Denied: Road tax for vehicle {$selectedVehicle->plate_number} expired on " . Carbon::parse($selectedVehicle->roadtax_expiry)->format('d/m/Y') . ". Mileage claims are strictly prohibited for non-compliant vehicles."])
                    ->withInput();
            }

            $vehicleId = $selectedVehicle->vehicle_id;
            $vehiclePlateNumber = $selectedVehicle->plate_number;
            $vehicleType = ucfirst(strtolower($selectedVehicle->vehicle_type));

            // Dynamic rate per KM lookup
            $rateRecord = MileageRate::whereRaw('LOWER(vehicle_type) = ?', [strtolower($vehicleType)])->latest()->first();
            $ratePerKm = $rateRecord ? (float) $rateRecord->rate : ($vehicleType === 'Motorcycle' ? 0.30 : 0.60);

            $km = (float) $request->input('mileage_km');
            // Authoritative server-calculated amount
            $calculatedAmount = round($km * $ratePerKm, 2);

            if ($request->hasFile('mileage_document')) {
                $imagePath = $request->file('mileage_document')->store('receipts', 'private');
            }

            $merchantName = "Aero Art Mileage ({$vehicleType})";
            $predictedCategory = 'Travel';
            $paymentMethod = 'Allowance';

            $rawDate = $request->input('transaction_date');
            $targetTransactionDate = ($rawDate && $rawDate !== '')
                ? Carbon::parse($rawDate)->format('Y-m-d')
                : now()->format('Y-m-d');

        } else {
            $request->validate([
                'receipt' => 'required|image|max:5120',
                'merchant_name' => 'required|string',
                'amount' => 'required|numeric|min:0.01',
                'category' => 'required|string',
                'transaction_date' => 'nullable|date',
                'business_purpose' => 'required|string',
            ]);

            $calculatedAmount = (float) $request->input('amount');
            $merchantName = $request->input('merchant_name');
            $predictedCategory = $request->input('category') ?? 'Office Supplies';
            $paymentMethod = $request->input('payment_method') ?? 'Cash';
            $targetTransactionDate = Carbon::parse($request->input('transaction_date'))->format('Y-m-d');
            $vehiclePlateNumber = $request->input('vehicle_plate_number');

            if (in_array($predictedCategory, ['Fuel & Fleet Logistics', 'Fuel / Automotive', 'Fuel'])) {
                $request->validate([
                    'vehicle_plate_number' => 'required|string|exists:vehicles,plate_number',
                ]);

                $fleetVehicle = Vehicle::where('plate_number', $vehiclePlateNumber)
                    ->where('ownership_type', 'company')
                    ->first();

                if (!$fleetVehicle || $fleetVehicle->status !== 'Active') {
                    return redirect()->back()
                        ->withErrors(['vehicle_plate_number' => 'Compliance Violation: Selected plate number is not an active corporate fleet asset.'])
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
                $imagePath = $request->file('receipt')->store('receipts', 'private');
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
            $targetTransactionDate,
            null, // currentClaimId is null on create
            $request->input('merchant_name'),
            $request->input('receipt_invoice_no'),
            $request->input('start_location'),
            $request->input('destination_location'),
            $request->input('mileage_km') ? (float) $request->input('mileage_km') : null
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

        $categoryRecord = \App\Models\Category::where('name', $predictedCategory)
            ->orWhere('code', $predictedCategory)
            ->first();
        $categoryId = $categoryRecord ? $categoryRecord->id : null;

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
                'category_id' => $categoryId,
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
                $claim->claim_id,
                null,
                [
                    'type' => $claim->claim_type,
                    'amount' => $calculatedAmount,
                    'merchant' => $merchantName,
                    'submission_mode' => $isMileage ? 'Mileage Allowance' : 'OCR Receipt',
                    'is_violation' => $policyCheck['is_violation']
                ]
            );

            // Log the fraud analysis result as a separate forensic audit node
            AuditLog::log(
                'FRAUD_ANALYZED',
                $claim->claim_id,
                null,
                [
                    'risk_score' => $fraudEvaluation['score'],
                    'flag_count' => count($fraudEvaluation['flags']),
                    'top_flag' => !empty($fraudEvaluation['flags']) ? $fraudEvaluation['flags'][0]['flag_type'] : null,
                    'top_severity' => !empty($fraudEvaluation['flags']) ? $fraudEvaluation['flags'][0]['severity'] : null,
                    'policy_violation' => $policyCheck['is_violation'],
                    'policy_reason' => $policyCheck['reason'] ?? null
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
                    "Staff {$currentUser->name} submitted claim #CLM-{$claim->claim_id} flagged with " . ($policyCheck['is_violation'] ? 'Policy Breach' : "High Risk ({$fraudEvaluation['score']}%)"),
                    'danger',
                    route('manager.verification') . '?status=Pre-Approved'
                );
            } else {
                NotificationService::notifyManagers(
                    'New Claim Submitted for Review',
                    "Staff {$currentUser->name} submitted claim #CLM-{$claim->claim_id} for RM " . number_format($calculatedAmount, 2) . " awaiting verification.",
                    'info',
                    route('manager.verification')
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
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,'.$user->id,
            'bank_name' => 'sometimes|required|string|max:100',
            'bank_account_no' => 'sometimes|required|string|max:50',
            'bank_account_holder' => 'sometimes|required|string|max:150',
            'phone_number' => 'sometimes|nullable|string|max:20',
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

        $user = auth()->user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        try {
            EmailDeliveryService::sendMailable($user->email, new PasswordResetSuccessMail($user));
        } catch (\Throwable $e) {
            Log::warning("Failed to send password update confirmation email to {$user->email}: " . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Account password successfully updated.');
    }

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

    public function toggleUserStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->user_id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot deactivate your own account.');
        }

        $oldStatus = $user->is_active;
        $user->is_active = !$user->is_active;
        $user->save();

        // Ensure status changes are logged to audit_logs
        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $user->is_active ? 'Activated User' : 'Deactivated User',
            'model_type' => 'User',
            'model_id' => $user->user_id,
            'old_values' => json_encode(['is_active' => (bool)$oldStatus]),
            'new_values' => json_encode(['is_active' => (bool)$user->is_active]),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'event_category' => 'USER_MANAGEMENT'
        ]);

        return redirect()->back()->with('success', 'User status updated successfully.');
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
        $categoryFilter = $request->input('category');
        $searchQuery = $request->input('search');

        $query = AuditLog::with('user');

        if ($categoryFilter) {
            if ($categoryFilter === 'claims') {
                $query->where(function($q) {
                    $q->where('event_category', 'CLAIMS')
                      ->orWhere('event_category', 'CLAIM')
                      ->orWhere('model_type', 'like', '%Claim%')
                      ->orWhere('action', 'like', '%Status changed%')
                      ->orWhere('action', 'like', '%FRAUD%');
                });
            } elseif ($categoryFilter === 'system' || $categoryFilter === 'config') {
                $query->where(function($q) {
                    $q->where('event_category', 'CONFIG')
                      ->orWhereIn('model_type', ['MileageRate', 'Vehicle', 'Category', 'ExpenseCategory'])
                      ->orWhereIn('event_category', ['MILEAGE_CONFIG', 'CATEGORY', 'VEHICLE']);
                });
            } elseif ($categoryFilter === 'security') {
                $query->where(function($q) {
                    $q->where('event_category', 'SECURITY')
                      ->orWhere('action', 'like', '%LOGIN%')
                      ->orWhere('action', 'like', '%AUTH%')
                      ->orWhere('action', 'like', '%PASSWORD%');
                });
            }
        }

        if ($searchQuery) {
            $query->where(function($q) use ($searchQuery) {
                $q->where('action', 'like', "%{$searchQuery}%")
                  ->orWhere('model_type', 'like', "%{$searchQuery}%")
                  ->orWhereHas('user', function($userQ) use ($searchQuery) {
                      $userQ->where('name', 'like', "%{$searchQuery}%")
                            ->orWhere('role', 'like', "%{$searchQuery}%");
                  });
            });
        }

        $auditLogs = $query->latest()->paginate(20)->withQueryString();

        return view('manager.audit_logs', compact('auditLogs'));
    }

    public function financeProfileIndex()
    {
        $totalBatchesSettled = \App\Models\Claim::where('status', 'Reimbursed')->count();
        $recentVolume = \App\Models\Claim::where('status', 'Reimbursed')
            ->whereMonth('updated_at', now()->month)
            ->sum('amount');
            
        return view('finance.profile', compact('totalBatchesSettled', 'recentVolume'));
    }

    // Comment: Compute organization-wide transaction records for Finance BI statistics
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
        $approvalsThisMonth = Claim::where('status', 'Approved')->whereMonth('updated_at', now()->month)->count();
        $policiesConfigured = \App\Models\ExpensePolicy::count();
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
            'approvalsThisMonth',
            'policiesConfigured',
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

    // Comment: Procurement Price Intelligence & Cross-Merchant Price Comparison Engine
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

    // Comment: Unified Payment Disbursement Desk
    public function financeDisbursementIndex(Request $request)
    {
        $tab = $request->input('tab', 'pending');

        $pendingDisbursements = Claim::with(['user.activeCashAdvance', 'items'])
            ->where('status', 'Approved')
            ->orderBy('updated_at', 'desc')
            ->get();

        $settledDisbursements = Claim::with(['user.activeCashAdvance', 'items'])
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

    // Comment: Process single voucher payout settlement with proof slip
    public function processDisbursement(Request $request, $id)
    {
        $userRole = Auth::check() ? Auth::user()->role : '';
        $normalizedRole = strtolower(trim($userRole));
        if (!in_array($normalizedRole, ['finance', 'fin'])) {
            abort(403, 'Unauthorized action: Only Finance Officers can process disbursements.');
        }

        $request->validate([
            'settlement_method' => 'nullable|in:bank_transfer,contra',
            'payment_reference' => 'required_without:settlement_method|required_if:settlement_method,bank_transfer|string|max:100|nullable',
            'payment_proof' => 'required_without:settlement_method|required_if:settlement_method,bank_transfer|file|mimes:jpeg,png,jpg,pdf|max:10240|nullable',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $id) {
            $claim = Claim::with('user.activeCashAdvance')->lockForUpdate()->findOrFail($id);

            if ($claim->status !== 'Approved') {
                return redirect()->back()->withErrors(['error' => 'This voucher is not eligible for disbursement.']);
            }

            $settlementMethod = $request->input('settlement_method', 'bank_transfer');
            $proofPath = null;
            $cleanRef = null;

            if ($settlementMethod === 'contra') {
                $advance = $claim->user->activeCashAdvance;
                if (!$advance) {
                    return redirect()->back()->withErrors(['error' => 'No active cash advance found for contra settlement.']);
                }

                $deduction = min($claim->amount, $advance->remaining_balance);
                $originalStatus = $advance->status;
                $originalBalance = $advance->remaining_balance;
                $advance->remaining_balance -= $deduction;
                
                if ($advance->remaining_balance <= 0) {
                    $advance->status = 'CLEARED';
                } else {
                    $advance->status = 'PARTIALLY_RECONCILED';
                }
                $advance->save();

                if (class_exists(\App\Models\AuditLog::class)) {
                    \App\Models\AuditLog::log(
                        'CONTRA_ADJUSTMENT',
                        $claim->claim_id,
                        ['remaining_balance' => $originalBalance, 'status' => $originalStatus],
                        ['remaining_balance' => $advance->remaining_balance, 'status' => $advance->status, 'deducted_amount' => $deduction],
                        auth()->id(),
                        'Cash Advance',
                        'CashAdvance',
                        $advance->advance_id
                    );
                }

                $cleanRef = 'CONTRA-' . $advance->advance_id;

                if ($claim->amount > $deduction) {
                    $request->validate([
                        'payment_reference' => 'required|string|max:100',
                        'payment_proof' => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240',
                    ], [
                        'payment_reference.required' => 'Bank reference is required to pay the difference.',
                        'payment_proof.required' => 'Payment slip is required to pay the difference.'
                    ]);

                    if ($request->hasFile('payment_proof')) {
                        $proofPath = $request->file('payment_proof')->store('payment_proofs', 'private');
                    }
                    $cleanRef = 'CONTRA-' . $advance->advance_id . ' & ' . strtoupper(trim($request->input('payment_reference')));
                }
            } else {
                if ($request->hasFile('payment_proof')) {
                    $proofPath = $request->file('payment_proof')->store('payment_proofs', 'private');
                }
                $cleanRef = strtoupper(trim($request->input('payment_reference')));
            }

            $claim->status = 'Reimbursed';
            $claim->payment_reference = $cleanRef;
            $claim->paid_at = Carbon::now();
            if ($proofPath) {
                $claim->payment_proof_path = $proofPath;
            }
            $claim->save();

            if (class_exists(\App\Models\AuditLog::class)) {
                \App\Models\AuditLog::log(
                    'PAYMENT_DISBURSED',
                    $claim->claim_id,
                    ['status' => 'Approved', 'payment_reference' => null],
                    [
                        'status' => 'Reimbursed',
                        'amount' => $claim->amount,
                        'payment_reference' => $claim->payment_reference,
                        'proof_file' => $proofPath,
                        'disbursed_by' => Auth::user()->name ?? 'Finance Officer',
                        'settlement_method' => $settlementMethod
                    ]
                );
            }

            NotificationService::send(
                $claim->user_id,
                'Payment Reimbursed: Claim Settled',
                "Your claim voucher #CLM-{$claim->claim_id} (RM " . number_format($claim->amount, 2) . ") has been paid. Reference: {$cleanRef}",
                'success',
                route('dashboard')
            );

            return redirect()->route('finance.disbursement', ['tab' => 'settled'])
                ->with('success', "Payment voucher #CLM-{$claim->claim_id} successfully settled and marked as Reimbursed.");
        });
    }

    // Comment: Bank Slip & PDF OCR Extraction Engine
    public function asyncScanBankSlip(Request $request)
    {
        if (!$request->hasFile('payment_proof')) {
            return response()->json(['success' => false, 'message' => 'No payment slip detected.']);
        }

        try {
            $uploadedFile = $request->file('payment_proof');
            $mimeType = $uploadedFile->getClientMimeType();
            $extension = strtolower($uploadedFile->getClientOriginalExtension());
            $imagePath = $uploadedFile->store('receipts/temp', 'private');
            $fullImagePath = storage_path('app/private/' . $imagePath);
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

    // Comment: Process batch disbursement of multiple claims simultaneously with a single proof slip
    public function processBatchDisbursement(Request $request)
    {
        $userRole = Auth::check() ? Auth::user()->role : '';
        $normalizedRole = strtolower(trim($userRole));
        if (!in_array($normalizedRole, ['finance', 'fin'])) {
            abort(403, 'Unauthorized action: Only Finance Officers can process disbursements.');
        }

        $request->validate([
            'claim_ids' => 'required|array|min:1',
            'claim_ids.*' => 'required|integer|exists:claims,claim_id',
            'payment_reference' => 'required|string|max:100',
            'payment_proof' => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240',
        ]);

        try {
            $proofPath = $request->file('payment_proof')->store('payment_proofs', 'private');
            $now = Carbon::now();
            $paymentRef = strtoupper(trim($request->input('payment_reference')));
            $claimIds = $request->input('claim_ids');

            DB::transaction(function () use ($claimIds, $paymentRef, $proofPath, $now) {
                $claims = Claim::whereIn('claim_id', $claimIds)->where('status', 'Approved')->lockForUpdate()->get();

                foreach ($claims as $claim) {
                    $claim->update([
                        'status' => 'Reimbursed',
                        'payment_reference' => $paymentRef,
                        'payment_proof_path' => $proofPath,
                        'paid_at' => $now,
                    ]);

                    if (class_exists(\App\Models\AuditLog::class)) {
                        \App\Models\AuditLog::log(
                            'PAYMENT_DISBURSED_BATCH',
                            $claim->claim_id,
                            ['status' => 'Approved', 'payment_reference' => null],
                            [
                                'status' => 'Reimbursed',
                                'amount' => $claim->amount,
                                'batch_reference' => $paymentRef,
                                'disbursed_by' => Auth::user()->name ?? 'Finance Officer'
                            ]
                        );
                    }

                    try {
                        NotificationService::send(
                            $claim->user_id,
                            '💳 Payment Reimbursed: #CLM-' . $claim->claim_id,
                            "Your claim #CLM-{$claim->claim_id} of RM " . number_format($claim->amount, 2) . " has been paid via batch reference: {$paymentRef}.",
                            'success',
                            route('dashboard')
                        );
                    } catch (\Throwable $th) {
                        // Suppress individual device notification drops
                    }
                }
            });

            return redirect()->route('finance.disbursement', ['tab' => 'settled'])
                ->with('success', 'Batch disbursement successfully processed for ' . count($claimIds) . ' voucher(s).');

        } catch (\Throwable $e) {
            Log::error('Batch Disbursement Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to process batch disbursement: ' . $e->getMessage()]);
        }
    }

    // Comment: Generate and download official corporate PDF voucher for audits
    public function downloadPdfVoucher($id)
    {
        $claim = Claim::with(['user', 'items', 'vehicle'])->findOrFail($id);

        $currentUser = Auth::user();
        $isOwner = ($currentUser->user_id ?? $currentUser->id) == $claim->user_id;
        $isPrivileged = in_array($currentUser->role ?? '', ['Finance', 'Manager', 'finance', 'manager']);

        if (!$isOwner && !$isPrivileged) {
            abort(403, 'Unauthorized access to this corporate voucher.');
        }

        $pdf = Pdf::loadView('claims.pdf_voucher', compact('claim'))
            ->setPaper('a4', 'portrait');

        $fileName = 'VOUCHER-CLM-' . str_pad($claim->claim_id, 4, '0', STR_PAD_LEFT) . '.pdf';

        return $pdf->download($fileName);
    }

    // Secured Private File Storage Serving
    public function serveFile($folder, $filename)
    {
        // Enforce authentication
        if (!Auth::check()) {
            abort(403, 'Unauthorized access.');
        }

        // Prevent directory traversal attacks
        if (str_contains($folder, '..') || str_contains($filename, '..') || str_contains($folder, '\\') || str_contains($filename, '\\')) {
            abort(403, 'Invalid file path traversal attempt.');
        }

        $cleanFolder = trim($folder, '/');
        $cleanFilename = trim($filename, '/');
        $path = $cleanFolder . '/' . $cleanFilename;

        $currentUser = Auth::user();
        $currentUserId = $currentUser->user_id ?? $currentUser->id;
        $normalizedRole = strtolower(trim($currentUser->role ?? ''));
        $isPrivileged = in_array($normalizedRole, ['manager', 'finance', 'fin']);

        // Authorize: Only the document/claim owner, Finance auditors, or Managers can access files
        if (!$isPrivileged) {
            $baseName = basename($cleanFilename);

            $ownsClaim = Claim::where('user_id', $currentUserId)
                ->where(function ($query) use ($path, $baseName) {
                    $query->where('receipt_image_path', $path)
                        ->orWhere('payment_proof_path', $path)
                        ->orWhere('receipt_image_path', 'like', "%{$baseName}")
                        ->orWhere('payment_proof_path', 'like', "%{$baseName}");
                })
                ->exists();

            $ownsVehicle = Vehicle::where('user_id', $currentUserId)
                ->where(function ($query) use ($path, $baseName) {
                    $query->where('grant_document_path', $path)
                        ->orWhere('roadtax_document_path', $path)
                        ->orWhere('grant_document_path', 'like', "%{$baseName}")
                        ->orWhere('roadtax_document_path', 'like', "%{$baseName}");
                })
                ->exists();

            if (!$ownsClaim && !$ownsVehicle) {
                abort(403, 'Unauthorized access to this private document.');
            }
        }

        // Verify physical presence on storage disk
        if (Storage::disk('private')->exists($path)) {
            return response()->file(Storage::disk('private')->path($path));
        }

        // Legacy fallback for documents previously stored in public disk
        if (Storage::disk('public')->exists($path)) {
            return response()->file(Storage::disk('public')->path($path));
        }

        abort(404, 'File not found on storage disk.');
    }
    public function slaAnalytics(\App\Services\SlaTrackingService $slaTrackingService)
    {
        $avgManagerTat = $slaTrackingService->getAverageManagerTurnaroundTime();
        $avgFinanceTat = $slaTrackingService->getAverageFinanceSettlementTime();
        $bottlenecks = $slaTrackingService->getBottleneckQueue();

        return view('manager.sla_analytics', compact('avgManagerTat', 'avgFinanceTat', 'bottlenecks'));
    }

    // Comment: Render Staff Edit & Resubmit Form for claims in REVISION_REQUIRED
    public function edit($id)
    {
        $claim = Claim::with(['items', 'vehicle'])->findOrFail($id);
        $currentUserId = Auth::id() ?? 1;

        if ($claim->user_id !== $currentUserId) {
            abort(403, 'Unauthorized: You can only edit your own claims.');
        }

        if ($claim->status !== 'REVISION_REQUIRED') {
            return redirect()->route('claims.history')
                ->withErrors(['error' => 'This claim cannot be edited because it is not flagged for revision. Current status: ' . $claim->status]);
        }

        $personalVehicles = Vehicle::where('user_id', $currentUserId)
            ->where('ownership_type', 'personal')
            ->where('approval_status', 'Approved')
            ->whereDate('roadtax_expiry', '>=', Carbon::today())
            ->orderBy('plate_number', 'asc')
            ->get();

        $companyFleet = Vehicle::where('ownership_type', 'company')
            ->where('status', 'Active')
            ->orderBy('plate_number', 'asc')
            ->get();

        $categories = Category::where('is_active', true)->get();

        $expensePolicies = ExpensePolicy::with('category')->where('is_active', true)->get()->map(function ($policy) {
            $policy->category_name = $policy->category->name ?? '';
            return $policy;
        });

        return view('claims.edit', compact('claim', 'personalVehicles', 'companyFleet', 'categories', 'expensePolicies'));
    }

    // Comment: Handle Staff Resubmission of Claim after addressing clarification
    public function resubmit(Request $request, $id)
    {
        $claim = Claim::with('items')->findOrFail($id);
        $currentUserId = Auth::id() ?? 1;

        if ($claim->user_id !== $currentUserId) {
            abort(403, 'Unauthorized: You can only resubmit your own claims.');
        }

        if ($claim->status !== 'REVISION_REQUIRED') {
            return redirect()->route('claims.history')
                ->withErrors(['error' => 'This claim is not currently marked for revision.']);
        }

        $isMileage = $claim->claim_type === 'Mileage';

        if ($isMileage) {
            $request->validate([
                'title' => 'required|string|max:255',
                'vehicle_id' => 'required|exists:vehicles,vehicle_id',
                'mileage_km' => 'required|numeric|min:0.1',
                'start_location' => 'required|string',
                'destination_location' => 'required|string',
                'transaction_date' => 'nullable|date',
                'mileage_document' => 'nullable|image|max:5120',
                'business_purpose' => 'required|string',
            ]);
        } else {
            $request->validate([
                'title' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0.01',
                'receipt_invoice_no' => 'required|string|max:100',
                'transaction_date' => 'required|date',
                'category_id' => 'required|exists:categories,id',
                'payment_method' => 'required|string',
                'business_purpose' => 'required|string',
                'receipt_image' => 'nullable|image|max:5120',
            ]);
        }

        return DB::transaction(function () use ($request, $claim, $isMileage) {
            $oldStatus = $claim->status;

            if ($isMileage) {
                $vehicle = Vehicle::findOrFail($request->input('vehicle_id'));
                $claim->title = $request->input('title');
                $claim->vehicle_id = $vehicle->vehicle_id;
                $claim->vehicle_plate_number = $vehicle->plate_number;
                $claim->vehicle_type = $vehicle->vehicle_type;
                $claim->mileage_km = (float) $request->input('mileage_km');
                $claim->start_location = $request->input('start_location');
                $claim->destination_location = $request->input('destination_location');
                $claim->business_purpose = $request->input('business_purpose');
                if ($request->input('transaction_date')) {
                    $claim->transaction_date = $request->input('transaction_date');
                }

                $mileageRate = MileageRate::where('vehicle_type', $vehicle->vehicle_type)->first();
                $ratePerKm = $mileageRate ? (float) $mileageRate->rate_per_km : 0.80;
                $claim->rate_per_km = $ratePerKm;
                $claim->calculated_amount = round($claim->mileage_km * $ratePerKm, 2);
                $claim->amount = $claim->calculated_amount;

                if ($request->hasFile('mileage_document')) {
                    $claim->mileage_document_path = $request->file('mileage_document')->store('mileage_docs', 'private');
                }
            } else {
                $claim->title = $request->input('title');
                $claim->amount = (float) $request->input('amount');
                $claim->receipt_invoice_no = $request->input('receipt_invoice_no');
                $claim->transaction_date = $request->input('transaction_date');
                $claim->category_id = $request->input('category_id');
                $claim->payment_method = $request->input('payment_method');
                $claim->business_purpose = $request->input('business_purpose');

                $category = Category::find($request->input('category_id'));
                if ($category) {
                    $claim->predicted_category = $category->name;
                }

                if ($request->hasFile('receipt_image')) {
                    $claim->receipt_image_path = $request->file('receipt_image')->store('receipts', 'private');
                    $claim->receipt_image_hash = hash('sha256', file_get_contents($request->file('receipt_image')->getRealPath()));
                }
            }

            // Revert status to Submitted/Pending for Auditor re-verification
            $claim->status = 'Pending';
            $claim->save();

            // Record event in AuditLog
            AuditLog::log(
                "CLAIM_RESUBMITTED",
                $claim->claim_id,
                ['status' => $oldStatus],
                [
                    'status' => 'Pending',
                    'action_summary' => 'Claim resubmitted by employee after addressing audit clarification',
                    'resubmitted_by' => Auth::user()->name ?? 'Staff User',
                    'remarks' => $request->input('resubmission_notes', 'Addressed auditor feedback.')
                ]
            );

            // Notify Finance/Manager that clarification has been provided
            $financeUsers = User::whereIn('role', ['Finance', 'Manager', 'finance', 'manager'])->get();
            foreach ($financeUsers as $fUser) {
                NotificationService::send(
                    $fUser->user_id ?? $fUser->id,
                    "Claim Clarification Provided: #CLM-{$claim->claim_id}",
                    "Employee " . (Auth::user()->name ?? 'Staff') . " has updated and resubmitted claim #CLM-{$claim->claim_id} ({$claim->title}).",
                    'info',
                    route('finance.auditing')
                );
            }

            return redirect()->route('claims.history')
                ->with('success', "Claim #CLM-{$claim->claim_id} has been resubmitted successfully for review.");
        });
    }

    // Comment: Staff Lifecycle - Claim Withdrawal / Deletion Governance
    public function withdraw(Request $request, $id)
    {
        $claim = Claim::findOrFail($id);
        $currentUserId = Auth::id() ?? 1;

        if ($claim->user_id !== $currentUserId) {
            abort(403, 'Unauthorized: You can only cancel or withdraw your own claims.');
        }

        // Permitted: SUBMITTED (Pending) and REVISION_REQUIRED
        // Forbidden / Locked: PRE_APPROVED, APPROVED, DISBURSED/REIMBURSED
        $lockedStatuses = ['Pre-Approved', 'Approved', 'Disbursed', 'Reimbursed'];
        if (in_array($claim->status, $lockedStatuses)) {
            return redirect()->back()->withErrors([
                'error' => "Action Locked: Claim #CLM-{$claim->claim_id} cannot be withdrawn because it is already under corporate governance ({$claim->status})."
            ]);
        }

        $allowedStatuses = ['Pending', 'REVISION_REQUIRED', 'Submitted'];
        if (!in_array($claim->status, $allowedStatuses)) {
            return redirect()->back()->withErrors([
                'error' => "Claim in status '{$claim->status}' cannot be withdrawn."
            ]);
        }

        return DB::transaction(function () use ($claim) {
            $claimId = $claim->claim_id;
            $oldStatus = $claim->status;

            AuditLog::log(
                "CLAIM_WITHDRAWN",
                $claimId,
                ['status' => $oldStatus],
                [
                    'status' => 'Withdrawn',
                    'actor' => Auth::user()->name ?? 'Staff User',
                    'reason' => 'Withdrawn by employee prior to financial finalization'
                ]
            );

            // Delete associated claim items
            $claim->items()->delete();
            $claim->delete();

            return redirect()->route('claims.history')
                ->with('success', "Claim #CLM-{$claimId} has been successfully withdrawn and deleted.");
        });
    }
}




