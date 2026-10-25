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
        'predicted_category',
        'actual_category',
        'extracted_text',
        'is_discrepancy',
        'source',
    ];

    public function setPredictedCategoryAttribute($value): void
    {
        $this->attributes['predicted_value'] = $value;
        if (empty($this->attributes['field_name'])) {
            $this->attributes['field_name'] = 'category';
        }
    }

    public function setActualCategoryAttribute($value): void
    {
        $this->attributes['actual_value'] = $value;
        if (empty($this->attributes['field_name'])) {
            $this->attributes['field_name'] = 'category';
        }
    }

    public function setExtractedTextAttribute($value): void
    {
        $this->attributes['raw_text_sample'] = $value;
    }

    public function setIsDiscrepancyAttribute($value): void
    {
        $this->attributes['correction_status'] = $value ? 'corrected_by_staff' : 'accepted_by_staff';
    }

    public function setSourceAttribute($value): void
    {
        if (empty($this->attributes['receipt_reference'])) {
            $this->attributes['receipt_reference'] = strtoupper((string) $value);
        }
    }

    public function getPredictedCategoryAttribute(): ?string
    {
        return $this->predicted_value;
    }

    public function getActualCategoryAttribute(): ?string
    {
        return $this->actual_value;
    }

    public function getExtractedTextAttribute(): ?string
    {
        return $this->raw_text_sample;
    }

    public function getIsDiscrepancyAttribute(): bool
    {
        return $this->correction_status === 'corrected_by_staff';
    }

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
