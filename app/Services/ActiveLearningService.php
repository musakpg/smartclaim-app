<?php

namespace App\Services;

use App\Models\AiLearningFeedback;
use Illuminate\Support\Facades\Log;

class ActiveLearningService
{
    /**
     * Map target category labels to dictionary keys.
     */
    private static $categoryKeyMap = [
        'Meals & Entertainment' => 'meals',
        'Fuel / Automotive' => 'transport',
        'Office Supplies' => 'supplies',
        'Accommodations' => 'accommodations'
    ];

    /**
     * Detect discrepancies and log human corrections for active dictionary adaptation.
     */
    public static function processFeedback(?int $userId, ?string $merchant, string $predicted, string $corrected, ?string $rawText): void
    {
        if (empty($predicted) || empty($corrected) || $predicted === $corrected || $predicted === 'Unassigned') {
            return;
        }

        try {
            // Extract meaningful tokens (word length > 3, exclude numbers and generic terms)
            $cleanText = strtolower($rawText ?? '');
            $tokens = preg_split('/[^a-z]/', $cleanText, -1, PREG_SPLIT_NO_EMPTY);

            $stopwords = ['total', 'amount', 'invoice', 'receipt', 'cash', 'change', 'date', 'time', 'card', 'payment', 'thank', 'welcome', 'kuala', 'lumpur', 'johor', 'selangor', 'sdn', 'bhd'];
            $meaningfulTokens = array_values(array_unique(array_filter($tokens, fn($t) => strlen($t) >= 4 && !in_array($t, $stopwords))));

            AiLearningFeedback::create([
                'user_id' => $userId,
                'merchant_name' => $merchant,
                'predicted_category' => $predicted,
                'corrected_category' => $corrected,
                'raw_text_sample' => substr($rawText ?? '', 0, 1000),
                'extracted_keywords' => array_slice($meaningfulTokens, 0, 15),
                'is_applied' => true
            ]);

            Log::info("Active Learning Loop: Captured correction [{$predicted} -> {$corrected}] from merchant [{$merchant}].");
        } catch (\Throwable $e) {
            Log::error("Active Learning Error: " . $e->getMessage());
        }
    }

    /**
     * Fetch dynamic weights learned from user corrections to blend with base TF-IDF dictionary.
     */
    public static function getDynamicKeywordWeights(): array
    {
        $feedbacks = AiLearningFeedback::where('is_applied', true)->get();
        $weights = [
            'meals' => [],
            'transport' => [],
            'supplies' => []
        ];

        foreach ($feedbacks as $fb) {
            $catKey = self::$categoryKeyMap[$fb->corrected_category] ?? null;
            if (!$catKey || !isset($weights[$catKey]))
                continue;

            $words = $fb->extracted_keywords ?? [];
            foreach ($words as $word) {
                $weights[$catKey][$word] = ($weights[$catKey][$word] ?? 2.0) + 0.5;
            }
        }

        return $weights;
    }
}