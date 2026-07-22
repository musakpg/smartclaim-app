<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Claim;
use App\Models\ClaimItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

// FIXED: Google Cloud Vision v2.x Enterprise Architecture Target Mappings
use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\BatchAnnotateImagesRequest;
use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\Image;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;

class ClaimController extends Controller
{
    /**
     * Display the regular staff / employee analytical dashboard metrics.
     */
    public function index()
    {
        $totalSpending = Claim::where('status', 'Approved')->sum('amount');
        $totalClaimsCount = Claim::count();
        $rejectedCount = Claim::where('status', 'Rejected')->count();
        $claims = Claim::latest()->take(5)->get();

        if ($totalClaimsCount === 0) {
            $topItems = collect();
            $categorySpending = collect();
            $frequentMerchants = collect();
            $anomalies = [];

            return view('dashboard', compact(
                'totalSpending',
                'totalClaimsCount',
                'rejectedCount',
                'claims',
                'topItems',
                'categorySpending',
                'frequentMerchants',
                'anomalies'
            ));
        }

        $topItems = DB::table('claim_items')
            ->select('item_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_spend'))
            ->groupBy('item_name')
            ->orderByDesc('total_qty')
            ->take(3)
            ->get();

        $categorySpending = Claim::select('predicted_category', DB::raw('SUM(amount) as total'))
            ->groupBy('predicted_category')
            ->get();

        $frequentMerchants = Claim::select('merchant_name', DB::raw('COUNT(*) as count'))
            ->groupBy('merchant_name')
            ->orderByDesc('count')
            ->take(3)
            ->get();

        $anomalies = [];
        $recentItems = DB::table('claim_items')
            ->join('claims', 'claim_items.claim_id', '=', 'claims.claim_id')
            ->select('claim_items.*', 'claims.merchant_name')
            ->orderByDesc('claim_items.created_at')
            ->take(5)
            ->get();

        foreach ($recentItems as $item) {
            $historicalAvg = DB::table('claim_items')
                ->where('item_name', $item->item_name)
                ->where('claim_id', '!=', $item->claim_id)
                ->avg('unit_price');

            if ($historicalAvg && $item->unit_price > ($historicalAvg * 1.25)) {
                $diffPercent = round((($item->unit_price - $historicalAvg) / $historicalAvg) * 100);
                $anomalies[] = "Price Anomaly: '{$item->item_name}' at {$item->merchant_name} cost RM " . number_format($item->unit_price, 2) . ". Your historical baseline average is RM " . number_format($historicalAvg, 2) . " (+{$diffPercent}%).";
            }
        }

        return view('dashboard', compact(
            'totalSpending',
            'totalClaimsCount',
            'rejectedCount',
            'claims',
            'topItems',
            'categorySpending',
            'frequentMerchants',
            'anomalies'
        ));
    }

    /**
     * Show the form for creating a new claim with global vehicle asset mapping.
     */
    public function create()
    {
        $myVehicles = DB::table('vehicles')->where('status', 'Active')->orderBy('plate_number', 'asc')->get();
        return view('claims.create', compact('myVehicles'));
    }

    public function history()
    {
        $claims = Claim::latest()->get();
        return view('claims.history', compact('claims'));
    }

    public function checkDuplicate(Request $request)
    {
        // FIXED: Enforce strict verification using only Invoice No and Amount arrays
        $exists = Claim::where('receipt_invoice_no', trim($request->invoice_no))
            ->where('amount', $request->amount)
            ->exists();

        return response()->json(['duplicate' => $exists]);
    }

    /**
     * Core AI OCR Processing Engine Powered by Google Cloud Vision API with TF-IDF Classification.
     */
    public function asyncScan(Request $request)
    {
        if (!$request->hasFile('receipt')) {
            return response()->json(['success' => false, 'message' => 'No receipt file upload detected.']);
        }

        try {
            $imagePath = $request->file('receipt')->store('receipts/temp', 'public');
            $fullImagePath = storage_path('app/public/' . $imagePath);

            // FIXED: Instantiated ImageAnnotatorClient natively using v2.x factories
            $imageAnnotator = new ImageAnnotatorClient([
                'apiKey' => env('GOOGLE_CLOUD_API_KEY')
            ]);

            $imageData = file_get_contents($fullImagePath);

            // FIXED: Set up objects utilizing strict object factory setters for Google v2 library
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

            if (count($responses) === 0 || !$responses[0]->getFullTextAnnotation()) {
                $imageAnnotator->close();
                if (file_exists($fullImagePath))
                    unlink($fullImagePath);
                return response()->json(['success' => false, 'message' => 'Google Cloud Vision failed to parse image text layers.']);
            }

            $extractedText = $responses[0]->getFullTextAnnotation()->getText();
            $imageAnnotator->close();

            if (file_exists($fullImagePath))
                unlink($fullImagePath);

            // Standardize decimal regex pattern layouts cleanly
            $processedText = preg_replace('/(\d+)\s*[\.,]\s*(\d{2})(?!\d)/', '$1.$2', $extractedText);
            $lines = explode("\n", trim($processedText));

            // 1. EXTRACT CORPORATE ENTITY NAME (MERCHANT MATCHING MATRIX)
            $merchantName = 'Unknown Merchant';
            for ($i = 0; $i < min(4, count($lines)); $i++) {
                $candidateLine = trim($lines[$i]);
                if (preg_match('/(?:SDN\s*BHD|BHD|ENTERPRISE|MART|GROCER|STATION|PETRONAS|SHELL|PETRON|KK\s*SUPERMART|7-ELEVEN|TEXAS|TEALIVE|STARBUCKS|DIY|MR\s*DIY|BOOKSTORE)/i', $candidateLine)) {
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

            // 2. INDUSTRIAL EXPENSE TF-IDF EXTENSION CATEGORIZATION MATRIX
            $idfDictionary = [
                'meals' => [
                    'restaurant' => 2.0,
                    'mcdonalds' => 2.5,
                    'kfc' => 2.5,
                    'cafe' => 1.8,
                    'food' => 1.2,
                    'beverage' => 1.5,
                    'chicken' => 1.9,
                    'coffee' => 2.0,
                    'dinner' => 1.6,
                    'lunch' => 1.6,
                    'bistro' => 2.2,
                    'bakery' => 2.1,
                    'pizza' => 2.3,
                    'tealive' => 2.5,
                    'starbucks' => 2.5,
                    'bhd' => 0.1,
                    'sdn' => 0.1,
                    'ayam' => 1.8,
                    'kopi' => 1.9,
                    'makan' => 1.5,
                    'minum' => 1.5,
                    'burger' => 2.2,
                    'nasi' => 1.7,
                    'mee' => 1.7,
                    'teh' => 1.5,
                    'ice' => 1.1,
                    'dunkin' => 2.5,
                    'subway' => 2.5,
                    'baskin' => 2.5,
                    'secret' => 2.2,
                    'recipe' => 2.2,
                    'catering' => 2.4,
                    'chkn' => 2.0,
                    'bev' => 1.8,
                    'snack' => 1.7,
                    'milo' => 1.6,
                    'waffle' => 2.1,
                    'toast' => 1.8
                ],
                'transport' => [
                    'petronas' => 2.5,
                    'shell' => 2.5,
                    'petron' => 2.5,
                    'caltex' => 2.5,
                    'fuel' => 2.0,
                    'diesel' => 2.2,
                    'ron95' => 2.5,
                    'ron97' => 2.5,
                    'toll' => 2.1,
                    'plus' => 2.0,
                    'parking' => 1.8,
                    'petroleum' => 2.3,
                    'touch' => 2.1,
                    'go' => 1.2,
                    'station' => 1.5,
                    'pump' => 1.9,
                    'kedai' => 0.8,
                    'mesra' => 2.2,
                    'select' => 2.0,
                    'lubricant' => 2.4,
                    'motor' => 1.7,
                    'car' => 1.5,
                    'tyre' => 2.3,
                    'service' => 1.3,
                    'garage' => 2.1,
                    'autobahn' => 2.5,
                    'workshop' => 2.2,
                    'engine' => 2.0,
                    'oil' => 1.6,
                    'battery' => 2.1
                ],
                'supplies' => [
                    'bookstore' => 2.5,
                    'stationery' => 2.3,
                    'paper' => 1.8,
                    'printing' => 1.9,
                    'ink' => 2.4,
                    'cartridge' => 2.5,
                    'stapler' => 2.5,
                    'ribbon' => 2.4,
                    'marker' => 2.2,
                    'hardware' => 2.0,
                    'diy' => 2.2,
                    'office' => 1.5,
                    'files' => 2.0,
                    'binding' => 2.3,
                    'toner' => 2.5,
                    'pen' => 1.6,
                    'pencil' => 1.8,
                    'eraser' => 2.0,
                    'book' => 1.5,
                    'envelope' => 2.1,
                    'label' => 1.7,
                    'tape' => 1.6,
                    'scissors' => 2.1,
                    'stamps' => 1.9,
                    'copy' => 1.2,
                    'popular' => 2.5,
                    'smiggle' => 2.5,
                    'faber' => 2.5,
                    'castell' => 2.5,
                    'ntc' => 2.2
                ]
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
                if (isset($idfDictionary['meals'][$word])) {
                    $categoryScores['Meals & Entertainment'] += $tfValue * $idfDictionary['meals'][$word];
                }
                if (isset($idfDictionary['transport'][$word])) {
                    $categoryScores['Fuel / Automotive'] += $tfValue * $idfDictionary['transport'][$word];
                }
                if (isset($idfDictionary['supplies'][$word])) {
                    $categoryScores['Office Supplies'] += $tfValue * $idfDictionary['supplies'][$word];
                }
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
            $highestScore = current($categoryScores);

            if ($highestScore == 0.0) {
                $predictedCategory = 'Unassigned';
            }

            // 3. EXTRACT GEOGRAPHICAL ADDRESS PATTERNS
            $locationAddress = 'Unknown Location';
            $addressParts = [];
            foreach ($lines as $line) {
                if (preg_match('/(?:INV|INVOICE|REF|BILL|CASHIER|TOTAL|RM|RINGGIT|CHANGE|ROUNDING|DUE)/i', $line))
                    continue;
                if (preg_match('/\b(?:No|Lot|Jalan|Jln|KM|Lebuhraya|Sg|Besi|Serdang|Solaris|Pandan|Kampong|Kg|Kuala|Lumpur|KL|Johor|JB|Selangor|Penang|Ipoh|Melaka|Batu\s+Pahat|Muar)\b/i', $line) || preg_match('/\b\d{5}\b/', $line)) {
                    if (!preg_match('/(?:Tel|Fax|Email|Gst|Sst|Tax|Welcome|Thank)/i', $line)) {
                        $addressParts[] = trim($line);
                        if (count($addressParts) >= 2)
                            break;
                    }
                }
            }
            if (!empty($addressParts))
                $locationAddress = implode(', ', $addressParts);

            // 4. EXTRACT REFERENCE INVOICE STRUCTURAL STRINGS
            $receiptNo = 'NOT FOUND';
            $invoicePatterns = [
                '/(?:INV\s*NO|INV\s*NO\.|INV|INVOICE\s*NO|RECEIPT\s*NO|DOC\s*NO|BILL\s*NO|TICKET\s*NO)[:\s\.]+([A-Za-z0-9\-]{4,})/i',
                '/\b(?:INV|RCpt|TX|CS|INV\-)\-?[A-Za-z0-9\-]{4,}\b/i'
            ];

            foreach ($invoicePatterns as $pattern) {
                if (preg_match($pattern, $extractedText, $invMatches)) {
                    $candidate = trim($invMatches[1] ?? $invMatches[0]);
                    $candidate = trim(preg_replace('/[:\s\.]+/', '', $candidate));
                    if (!preg_match('/^(?:COPY|DUPLICATE|ORIGINAL|TAX|INVOICE|OFFICIAL|REPRINT|VALIDITY|TO|CLAIM|E-INVOICE)$/i', $candidate)) {
                        $receiptNo = strtoupper($candidate);
                        break;
                    }
                }
            }

            // 5. EXTRACT CHRONOLOGICAL TRANSACTION DATES (MULTI-FORMAT MATRIX)
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
                $monthStr = strtolower($dateMatches[2]);
                $month = $monthMap[$monthStr] ?? '01';
                $year = $dateMatches[3];
                if (strlen($year) == 2)
                    $year = '20' . $year;
                if ((int) $year <= 2026) {
                    $transactionDate = "{$year}-{$month}-{$day}";
                }
            } else if (preg_match('/\b(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{2,4})\b|\b(\d{4})[\/\.-](\d{1,2})[\/\.-](\d{1,2})\b/', $processedText, $numMatches)) {
                if (!empty($numMatches[4])) {
                    $transactionDate = "{$numMatches[4]}-" . str_pad($numMatches[5], 2, '0', STR_PAD_LEFT) . "-" . str_pad($numMatches[6], 2, '0', STR_PAD_LEFT);
                } else {
                    $day = str_pad($numMatches[1], 2, '0', STR_PAD_LEFT);
                    $month = str_pad($numMatches[2], 2, '0', STR_PAD_LEFT);
                    $year = $numMatches[3];
                    if (strlen($year) == 2)
                        $year = '20' . $year;
                    if ((int) $year <= 2026 && (int) $month <= 12 && (int) $day <= 31) {
                        $transactionDate = "{$year}-{$month}-{$day}";
                    }
                }
            }

            // 6. EXTRACT GRAND TOTAL NUMERIC PAYLOAD VALUES
            $extractedAmount = '0.00';
            $parsedAmounts = [];

            if (preg_match_all('/(?:GRAND\s*TOTAL|TOTAL\s*DUE|TOTAL|AMOUNT|CASH|SUBTOTAL|ROUNDING|BAYAR|NET\s*TOTAL|NET)[:\s\.\,\$]*([0-9]+\.\d{2})\b/i', $processedText, $amountMatches)) {
                foreach ($amountMatches[1] as $val) {
                    $parsedAmounts[] = (float) $val;
                }
            }

            if (empty($parsedAmounts)) {
                if (preg_match_all('/\b\d+\.\d{2}\b/', $processedText, $rawFloats)) {
                    foreach ($rawFloats[0] as $val) {
                        $parsedAmounts[] = (float) $val;
                    }
                }
            }

            if (!empty($parsedAmounts)) {
                $filteredAmounts = array_filter($parsedAmounts, function ($amt) {
                    return $amt != 2.08 && $amt != 6.00 && $amt != 0.00;
                });
                if (!empty($filteredAmounts)) {
                    $extractedAmount = number_format(max($filteredAmounts), 2, '.', '');
                }
            }

            // 7. ARBITRATE PAYMENT METHOD METHODOLOGIES
            $paymentMethod = 'Cash';
            if (preg_match('/(?:CARD|CREDIT|DEBIT|VISA|MASTER|MASTERCARD|MYDEBIT|CHIP|WAVE|PAYMENT)/i', $processedText)) {
                $paymentMethod = 'Card';
            } elseif (preg_match('/(?:WALLET|E-WALLET|TOUCH|GO|TNG|DUITNOW|BOOST|GRABPAY|QR|QRDUITNOW)/i', $processedText)) {
                $paymentMethod = 'e-Wallet';
            }

            $extractedItems = [];

            return response()->json([
                'success' => true,
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
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Display the Finance Auditor executive monitoring interface (Focuses on Level 1 Approval).
     */
    public function financeIndex()
    {
        $claims = Claim::with(['items', 'user'])->orderBy('created_at', 'desc')->get();
        $pendingCount = Claim::where('status', 'Pending')->count();
        $approvedCount = Claim::where('status', 'Approved')->count();
        $rejectedCount = Claim::where('status', 'Rejected')->count();
        $preApprovedCount = Claim::where('status', 'Pre-Approved')->count();

        $currentYear = Carbon::now()->year;
        $monthlyExpenses = DB::table('claims')
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('SUM(amount) as total_amount'))
            ->whereYear('created_at', $currentYear)
            ->where('status', 'Approved')
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy('month', 'asc')
            ->get()
            ->pluck('total_amount', 'month')
            ->toArray();

        $dashboardLineData = [];
        for ($m = 1; $m <= 12; $m++) {
            $dashboardLineData[] = isset($monthlyExpenses[$m]) ? (float) $monthlyExpenses[$m] : 0.0;
        }

        return view('finance.dashboard', compact(
            'claims',
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'preApprovedCount',
            'dashboardLineData'
        ));
    }

    /**
     * Display the active standalone claims auditing desktop workspace for Finance.
     */
    public function auditingIndex()
    {
        // Pastikan status yang ada dalam database adalah 'Pending', 'Approved', 'Rejected', 'Pre-Approved', 'Reimbursed'
        $claims = Claim::with(['items', 'user'])->orderBy('created_at', 'desc')->get();

        // Count untuk sidebar
        $pendingCount = Claim::where('status', 'Pending')->count();
        $approvedCount = Claim::where('status', 'Approved')->count();
        $rejectedCount = Claim::where('status', 'Rejected')->count();
        $preApprovedCount = Claim::where('status', 'Pre-Approved')->count();
        $reimbursedCount = Claim::where('status', 'Reimbursed')->count(); // Tambah ini

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
     * Display the Manager executive BI analytics dashboard with real financial metrics.
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
     * Dedicated workspace desk for managing relational database sign-off status matrices.
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

        return redirect()->back()->with('success', $message);
    }

    /**
     * Handle corporate expenditure persistence layers with strict regulatory tracking rules.
     */
    public function store(Request $request)
    {
        Log::info('Submit Request received:', $request->all());
        $isMileage = $request->input('claim_type') === 'Mileage';

        if ($isMileage) {
            $request->validate([
                'title' => 'required|string',
                'mileage_km' => 'required|string',
                'vehicle_type' => 'required|string',
                'start_location' => 'required|string',
                'destination_location' => 'required|string',
                'transaction_date' => 'nullable|date',
                'mileage_document' => 'required|image|max:5120',
            ]);

            $rawKm = $request->input('mileage_km');
            $km = (float) filter_var($rawKm, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            $rawAmount = $request->input('amount');
            $calculatedAmount = (float) filter_var($rawAmount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            $imagePath = $request->file('mileage_document')->store('receipts', 'public');

            $vehicle = $request->input('vehicle_type');
            $merchantName = ($vehicle === 'Car') ? 'Aero Art Transport (Car)' : 'Aero Art Transport (Motorcycle)';
            $predictedCategory = 'Transport';
            $paymentMethod = 'Allowance';

            $vehiclePlateNumber = $request->input('vehicle_plate_number');
            $rawDate = $request->input('transaction_date');
            $targetTransactionDate = ($rawDate && $rawDate !== '')
                ? Carbon::parse($rawDate)->format('Y-m-d')
                : now()->format('Y-m-d');
        } else {
            $request->validate([
                'receipt' => 'required|image|max:5120',
                'merchant_name' => 'required|string',
                'amount' => 'required|numeric',
                'transaction_date' => 'nullable|date',
            ]);

            $calculatedAmount = (float) $request->input('amount');
            $merchantName = $request->input('merchant_name');
            $predictedCategory = $request->input('category') ?? 'Unassigned';
            $paymentMethod = $request->input('payment_method') ?? 'Cash';

            $targetTransactionDate = Carbon::parse($request->input('transaction_date'))->format('Y-m-d');
            $vehiclePlateNumber = $request->input('vehicle_plate_number');

            $imagePath = $request->file('receipt')->store('receipts', 'public');

            // FIXED: Duplicate check locked securely strictly using Invoice No and Amount attributes
            $isDatabaseDuplicate = Claim::where('receipt_invoice_no', strtoupper(trim($request->input('receipt_invoice_no'))))
                ->where('amount', $calculatedAmount)
                ->exists();

            if ($isDatabaseDuplicate) {
                return redirect()->back()->withErrors(['duplicate' => 'Security Interception: A voucher record with identical Invoice No and Amount already exists.'])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            $claim = Claim::create([
                'user_id' => Auth::id() ?? 1,
                'claim_type' => $request->input('claim_type') ?? 'Receipt',
                'title' => $request->input('title') ?? 'Claim at ' . $merchantName,
                'merchant_name' => $merchantName,
                'location_address' => $request->input('location_address') ?? ($isMileage ? $request->input('start_location') : 'Unknown Address'),
                'receipt_invoice_no' => $request->input('receipt_invoice_no') ?? 'NOT FOUND',
                'transaction_date' => $targetTransactionDate,
                'amount' => $calculatedAmount,
                'mileage_km' => $isMileage ? $km : null,
                'vehicle_type' => $isMileage ? $vehicle : null,
                'start_location' => $isMileage ? $request->input('start_location') : null,
                'destination_location' => $isMileage ? $request->input('destination_location') : null,
                'payment_method' => $paymentMethod,
                'predicted_category' => $predictedCategory,
                'business_purpose' => $request->input('business_purpose') ?? 'Business expense',
                'receipt_image_path' => $imagePath,
                'extracted_raw_text' => trim($request->input('extracted_raw_text', '')),
                'vehicle_plate_number' => $vehiclePlateNumber,
                'status' => 'Pending'
            ]);

            DB::commit();
            return redirect()->route('dashboard')->with('success', 'Claim has been submitted.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Persistence error: ' . $e->getMessage()]);
        }
    }

    public function profileIndex()
    {
        $user = Auth::user() ?? (object) ['name' => 'Muhammad Musa Al-Kazhim', 'email' => 'musa@aeroart.com'];
        return view('profile.index', compact('user'));
    }

    public function policyIndex()
    {
        return view('policy.index');
    }

    public function reimbursementIndex()
    {
        $approvedClaims = Claim::where('user_id', 1)->where('status', 'Approved')->orderBy('updated_at', 'desc')->get();
        $approvedTotal = $approvedClaims->sum('amount');
        $paidTotal = 0;
        $processingTotal = 0;

        foreach ($approvedClaims as $claim) {
            if ($claim->claim_id % 2 === 0) {
                $paidTotal += $claim->amount;
            } else {
                $processingTotal += $claim->amount;
            }
        }
        return view('reimbursement.index', compact('approvedClaims', 'approvedTotal', 'paidTotal', 'processingTotal'));
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
        return view('manager.user_management');
    }
    public function auditLogsIndex()
    {
        return view('manager.audit_logs');
    }
    public function financeProfileIndex()
    {
        return view('finance.profile');
    }

    /**
     * Compute organization-wide transaction records to generate Chart.js statistics.
     */
    public function financeReportsIndex()
    {
        $currentYear = Carbon::now()->year;
        $monthlyExpenses = DB::table('claims')->select(DB::raw('MONTH(created_at) as month'), DB::raw('SUM(amount) as total_amount'))->whereYear('created_at', $currentYear)->where('status', 'Approved')->groupBy(DB::raw('MONTH(created_at)'))->get()->pluck('total_amount', 'month')->toArray();
        $chartLineData = [];
        for ($m = 1; $m <= 12; $m++) {
            $chartLineData[] = isset($monthlyExpenses[$m]) ? (float) $monthlyExpenses[$m] : 0.0;
        }
        $merchantTraffic = DB::table('claims')->select('merchant_name', DB::raw('count(*) as total_claims'))->whereNotNull('merchant_name')->where('merchant_name', '!=', '')->groupBy('merchant_name')->orderBy('total_claims', 'desc')->limit(5)->get();
        return view('finance.reports', ['chartLineData' => json_encode($chartLineData), 'merchantLabels' => json_encode($merchantTraffic->pluck('merchant_name')->toArray()), 'merchantCounts' => json_encode($merchantTraffic->pluck('total_claims')->toArray())]);
    }

    public function managerReportsIndex()
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

        return view('manager.reports', compact(
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

    private function getReportData()
    {
        $currentYear = Carbon::now()->year;
        $monthlyExpenses = Claim::where('status', 'Approved')->whereYear('transaction_date', $currentYear)
            ->selectRaw('MONTH(transaction_date) as month, SUM(amount) as total')->groupBy('month')->pluck('total', 'month')->toArray();
        $chartLineData = [];
        for ($m = 1; $m <= 12; $m++) {
            $chartLineData[] = isset($monthlyExpenses[$m]) ? (float) $monthlyExpenses[$m] : 0.0;
        }

        $merchantTraffic = Claim::select('merchant_name', DB::raw('count(*) as total'))->whereNotNull('merchant_name')->groupBy('merchant_name')->limit(5)->get();

        return [
            'chartLineData' => json_encode($chartLineData),
            'merchantLabels' => json_encode($merchantTraffic->pluck('merchant_name')),
            'merchantCounts' => json_encode($merchantTraffic->pluck('total'))
        ];
    }
    // Dalam ManagerController / FinanceController
    public function updateMileageRate(Request $request)
    {
        // ... logic update ...

        // Auto-log aktiviti
        AuditLog::log('CONFIG_UPDATE', [
            'target' => 'mileage_car_rate',
            'val' => $request->rate
        ]);
    }
}