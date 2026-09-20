<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MileageRate extends Model
{
    use HasFactory;

    protected $table = 'mileage_rates';

    protected $fillable = [
        'vehicle_type',
        'min_km',
        'max_km',
        'rate',
    ];

    protected $casts = [
        'min_km' => 'integer',
        'max_km' => 'integer',
        'rate' => 'decimal:2',
    ];
}