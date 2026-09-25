<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpensePolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'monthly_budget_cap',
        'max_single_claim_limit',
        'is_active',
        'description',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}