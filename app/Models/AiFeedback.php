<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiFeedback extends Model
{
    use HasFactory;

    protected $table = 'ai_feedback';

    protected $fillable = [
        'claim_id',
        'receipt_reference',
        'user_id',
        'field_name',
        'predicted_value',
        'actual_value',
        'correction_status',
        'confidence_score',
        'raw_text_sample',
    ];

    protected $casts = [
        'confidence_score' => 'float',
    ];

    public function claim()
    {
        return $this->belongsTo(Claim::class, 'claim_id', 'claim_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Record a discrepancy entry if predicted differs from user-corrected value.
     */
    public static function recordDiscrepancy(
        ?int $claimId,
        ?string $receiptReference,
        ?int $userId,
        string $fieldName,
        ?string $predictedValue,
        ?string $actualValue,
        ?float $confidenceScore = 88.50,
        ?string $rawTextSample = null
    ): ?self {
        $cleanPredicted = trim((string) $predictedValue);
        $cleanActual = trim((string) $actualValue);

        if ($cleanPredicted === '' || $cleanActual === '' || $cleanPredicted === $cleanActual) {
            return null;
        }

        return self::create([
            'claim_id' => $claimId,
            'receipt_reference' => $receiptReference,
            'user_id' => $userId,
            'field_name' => $fieldName,
            'predicted_value' => $cleanPredicted,
            'actual_value' => $cleanActual,
            'correction_status' => 'corrected_by_staff',
            'confidence_score' => $confidenceScore ?? 88.50,
            'raw_text_sample' => $rawTextSample ? substr($rawTextSample, 0, 1000) : null,
        ]);
    }
}
