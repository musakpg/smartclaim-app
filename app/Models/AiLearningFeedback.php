<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiLearningFeedback extends Model
{
    use HasFactory;

    // Paksa Eloquent guna nama table ini:
    protected $table = 'ai_learning_feedbacks';

    protected $fillable = [
        'user_id',
        'merchant_name',
        'predicted_category',
        'corrected_category',
        'raw_text_sample',
        'extracted_keywords',
        'is_applied',
    ];

    protected $casts = [
        'extracted_keywords' => 'array',
        'is_applied' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}