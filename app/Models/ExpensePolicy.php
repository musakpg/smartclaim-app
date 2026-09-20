<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpensePolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_name',
        'monthly_budget_cap',
        'max_single_claim_limit',
        'is_active',
        'description',
    ];
}