<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'keywords',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function policy()
    {
        return $this->hasOne(ExpensePolicy::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }
}
