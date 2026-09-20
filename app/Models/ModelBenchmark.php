<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelBenchmark extends Model
{
    use HasFactory;

    protected $primaryKey = 'benchmark_id';

    protected $fillable = [
        'sample_name',
        'raw_ocr_payload',
        'actual_category',
        'predicted_category',
        'actual_amount',
        'extracted_amount',
        'is_category_correct',
        'is_amount_correct',
        'processing_time_ms',
    ];
}