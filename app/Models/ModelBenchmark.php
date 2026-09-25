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
        'actual_merchant',
        'extracted_merchant',
        'actual_date',
        'extracted_date',
        'actual_tax_invoice',
        'extracted_tax_invoice',
        'is_category_correct',
        'is_amount_correct',
        'is_merchant_correct',
        'is_date_correct',
        'is_tax_invoice_correct',
        'processing_time_ms',
    ];
}