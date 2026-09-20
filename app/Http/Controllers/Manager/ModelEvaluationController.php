<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ModelBenchmark;
use Illuminate\Http\Request;

class ModelEvaluationController extends Controller
{
    /**
     * Display the NLP & OCR Model Evaluation metrics dashboard.
     */
    public function index()
    {
        $benchmarks = ModelBenchmark::all();
        $totalSamples = $benchmarks->count();

        $evaluatedCount = $benchmarks->whereNotNull('predicted_category')->count();
        $categoryAccuracy = 0;
        $ocrAmountAccuracy = 0;
        $avgExecutionTime = 0;

        $confusionMatrix = [
            'Meals & Entertainment' => ['TP' => 0, 'FP' => 0, 'FN' => 0],
            'Fuel / Automotive' => ['TP' => 0, 'FP' => 0, 'FN' => 0],
            'Office Supplies' => ['TP' => 0, 'FP' => 0, 'FN' => 0],
        ];

        if ($evaluatedCount > 0) {
            $correctCategory = $benchmarks->where('is_category_correct', true)->count();
            $correctAmount = $benchmarks->where('is_amount_correct', true)->count();

            $categoryAccuracy = ($correctCategory / $evaluatedCount) * 100;
            $ocrAmountAccuracy = ($correctAmount / $evaluatedCount) * 100;
            $avgExecutionTime = $benchmarks->avg('processing_time_ms');

            // Compute Precision & Recall per class
            foreach ($confusionMatrix as $cat => &$vals) {
                foreach ($benchmarks as $b) {
                    if ($b->actual_category === $cat && $b->predicted_category === $cat) {
                        $vals['TP']++;
                    } elseif ($b->actual_category !== $cat && $b->predicted_category === $cat) {
                        $vals['FP']++;
                    } elseif ($b->actual_category === $cat && $b->predicted_category !== $cat) {
                        $vals['FN']++;
                    }
                }
            }
        }

        return view('manager.model-evaluation', compact(
            'benchmarks',
            'totalSamples',
            'evaluatedCount',
            'categoryAccuracy',
            'ocrAmountAccuracy',
            'avgExecutionTime',
            'confusionMatrix'
        ));
    }

    /**
     * Run batch TF-IDF classification & OCR regex pipeline against test dataset.
     */
    public function runBenchmark()
    {
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
                'tealive' => 2.5,
                'starbucks' => 2.5,
                'ayam' => 1.8,
                'makan' => 1.5,
                'burger' => 2.2,
                'tea' => 1.5
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
                'petroleum' => 2.3,
                'pump' => 1.9,
                'primax' => 2.5,
                'engine' => 2.0,
                'oil' => 1.6
            ],
            'supplies' => [
                'bookstore' => 2.5,
                'stationery' => 2.3,
                'paper' => 1.8,
                'printing' => 1.9,
                'ink' => 2.4,
                'cartridge' => 2.5,
                'stapler' => 2.5,
                'diy' => 2.2,
                'files' => 2.0,
                'toner' => 2.5,
                'pen' => 1.6,
                'popular' => 2.5,
                'tape' => 1.6
            ]
        ];

        $benchmarks = ModelBenchmark::all();

        foreach ($benchmarks as $sample) {
            $startTime = microtime(true);

            // 1. Run Amount Extraction Engine
            $extractedAmount = 0.00;
            if (preg_match_all('/(?:TOTAL|AMOUNT|DUE|BAYAR|RM)[:\s\.\,\$]*([0-9]+\.\d{2})\b/i', $sample->raw_ocr_payload, $matches)) {
                $floats = array_map('floatval', $matches[1]);
                $extractedAmount = max($floats);
            }

            // 2. Run TF-IDF Classification Engine
            $cleanText = strtolower($sample->raw_ocr_payload);
            $tokens = preg_split('/[^a-z0-9]/', $cleanText, -1, PREG_SPLIT_NO_EMPTY);
            $tfCounts = array_count_values($tokens);

            $scores = [
                'Meals & Entertainment' => 0.0,
                'Fuel / Automotive' => 0.0,
                'Office Supplies' => 0.0
            ];

            foreach ($tfCounts as $word => $tf) {
                if (isset($idfDictionary['meals'][$word]))
                    $scores['Meals & Entertainment'] += $tf * $idfDictionary['meals'][$word];
                if (isset($idfDictionary['transport'][$word]))
                    $scores['Fuel / Automotive'] += $tf * $idfDictionary['transport'][$word];
                if (isset($idfDictionary['supplies'][$word]))
                    $scores['Office Supplies'] += $tf * $idfDictionary['supplies'][$word];
            }

            arsort($scores);
            $predictedCategory = key($scores);

            $executionTimeMs = (microtime(true) - $startTime) * 1000;

            $sample->update([
                'predicted_category' => $predictedCategory,
                'extracted_amount' => $extractedAmount,
                'is_category_correct' => ($predictedCategory === $sample->actual_category),
                'is_amount_correct' => (abs($extractedAmount - $sample->actual_amount) < 0.01),
                'processing_time_ms' => round($executionTimeMs, 2),
            ]);
        }

        return redirect()->route('manager.model_evaluation')
            ->with('success', 'Model benchmark evaluation executed successfully across ' . $benchmarks->count() . ' test samples.');
    }
    /**
     * Upload an actual physical receipt image, run live Google Cloud Vision OCR + TF-IDF, and append to benchmark ledger.
     */
    public function uploadAndEvaluate(Request $request)
    {
        $request->validate([
            'sample_name' => 'required|string|max:255',
            'receipt' => 'required|image|max:5120',
            'actual_category' => 'required|string',
            'actual_amount' => 'required|numeric',
        ]);

        try {
            $startTime = microtime(true);

            // 1. Upload & Google Cloud Vision OCR Extraction
            $imagePath = $request->file('receipt')->store('benchmarks/temp', 'public');
            $fullImagePath = storage_path('app/public/' . $imagePath);

            $imageAnnotator = new \Google\Cloud\Vision\V1\Client\ImageAnnotatorClient([
                'apiKey' => env('GOOGLE_CLOUD_API_KEY')
            ]);

            $imageData = file_get_contents($fullImagePath);
            $image = (new \Google\Cloud\Vision\V1\Image())->setContent($imageData);
            $feature = (new \Google\Cloud\Vision\V1\Feature())->setType(\Google\Cloud\Vision\V1\Feature\Type::DOCUMENT_TEXT_DETECTION);
            $requestObj = (new \Google\Cloud\Vision\V1\AnnotateImageRequest())->setImage($image)->setFeatures([$feature]);
            $batchRequest = (new \Google\Cloud\Vision\V1\BatchAnnotateImagesRequest())->setRequests([$requestObj]);

            $response = $imageAnnotator->batchAnnotateImages($batchRequest);
            $responses = $response->getResponses();

            if (count($responses) === 0 || !$responses[0]->getFullTextAnnotation()) {
                $imageAnnotator->close();
                if (file_exists($fullImagePath))
                    unlink($fullImagePath);
                return redirect()->back()->withErrors(['error' => 'OCR failed to extract readable text layers.']);
            }

            $extractedText = $responses[0]->getFullTextAnnotation()->getText();
            $imageAnnotator->close();
            if (file_exists($fullImagePath))
                unlink($fullImagePath);

            // 2. Hierarchical Grand Total Amount Extraction (Subsidy & Multi-Total Aware)
            $processedText = preg_replace('/(\d+)\s*[\.,]\s*(\d{2})(?!\d)/', '$1.$2', $extractedText);
            $extractedAmount = 0.00;

            // Keutamaan 1: Cari baris Grand Total / Amount Paid / Total Bayar
            if (preg_match('/(?:GRAND\s*TOTAL|AMOUNT\s*PAID|AMOUNT\s*TENDERED|JUMLAH\s*BERSIH|BAYARAN|NET\s*TOTAL)[:\s\.\,\$]*([0-9]+\.\d{2})\b/i', $processedText, $match)) {
                $extractedAmount = (float) $match[1];
            }
            // Keutamaan 2: Jika tiada, cari baris Subtotal atau Total umum
            elseif (preg_match_all('/(?:TOTAL|AMOUNT|SUBTOTAL|RM)[:\s\.\,\$]*([0-9]+\.\d{2})\b/i', $processedText, $amountMatches)) {
                $floats = array_map('floatval', $amountMatches[1]);
                // Buang nombor kod tarif standard/liter yang biasa mengganggu
                $filtered = array_filter($floats, fn($a) => $a != 2.08 && $a != 1.99 && $a != 3.87 && $a != 0.00);
                if (!empty($filtered)) {
                    // Ambil nilai padanan terakhir pada resit (biasanya bahagian bawah/ringkasan)
                    $extractedAmount = end($filtered);
                }
            }

            // 3. TF-IDF Classification Engine
            $idfDictionary = [
                'meals' => ['restaurant' => 2.0, 'mcdonalds' => 2.5, 'kfc' => 2.5, 'cafe' => 1.8, 'food' => 1.2, 'coffee' => 2.0, 'ayam' => 1.8, 'makan' => 1.5, 'burger' => 2.2],
                'transport' => ['petronas' => 2.5, 'shell' => 2.5, 'petron' => 2.5, 'caltex' => 2.5, 'fuel' => 2.0, 'diesel' => 2.2, 'ron95' => 2.5, 'pump' => 1.9, 'engine' => 2.0],
                'supplies' => ['bookstore' => 2.5, 'stationery' => 2.3, 'paper' => 1.8, 'printing' => 1.9, 'ink' => 2.4, 'stapler' => 2.5, 'diy' => 2.2, 'popular' => 2.5]
            ];

            $cleanLowerText = strtolower($extractedText);
            $tokenWords = preg_split('/[^a-z0-9]/', $cleanLowerText, -1, PREG_SPLIT_NO_EMPTY);
            $tfCounts = array_count_values($tokenWords);

            $scores = ['Meals & Entertainment' => 0.0, 'Fuel / Automotive' => 0.0, 'Office Supplies' => 0.0];
            foreach ($tfCounts as $word => $tf) {
                if (isset($idfDictionary['meals'][$word]))
                    $scores['Meals & Entertainment'] += $tf * $idfDictionary['meals'][$word];
                if (isset($idfDictionary['transport'][$word]))
                    $scores['Fuel / Automotive'] += $tf * $idfDictionary['transport'][$word];
                if (isset($idfDictionary['supplies'][$word]))
                    $scores['Office Supplies'] += $tf * $idfDictionary['supplies'][$word];
            }

            arsort($scores);
            $predictedCategory = key($scores);
            $executionTimeMs = (microtime(true) - $startTime) * 1000;

            $actualAmount = (float) $request->input('actual_amount');
            $actualCategory = $request->input('actual_category');

            // 4. Persist real sample result
            ModelBenchmark::create([
                'sample_name' => $request->input('sample_name'),
                'raw_ocr_payload' => trim($extractedText),
                'actual_category' => $actualCategory,
                'predicted_category' => $predictedCategory,
                'actual_amount' => $actualAmount,
                'extracted_amount' => $extractedAmount,
                'is_category_correct' => ($predictedCategory === $actualCategory),
                'is_amount_correct' => (abs($extractedAmount - $actualAmount) < 0.01),
                'processing_time_ms' => round($executionTimeMs, 2),
            ]);

            return redirect()->route('manager.model-evaluation')
                ->with('success', "Live receipt processed and appended to evaluation benchmark suite.");

        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['error' => 'Live OCR Evaluation Failure: ' . $e->getMessage()]);
        }
    }
}