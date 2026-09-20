<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashAdvance extends Model
{
    use HasFactory;

    protected $primaryKey = 'advance_id';

    protected $fillable = [
        'user_id',
        'title',
        'purpose',
        'requested_amount',
        'settled_amount',
        'remaining_balance',
        'required_date',
        'status',
        'manager_remarks',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'settled_amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'required_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function claims()
    {
        return $this->hasMany(Claim::class, 'cash_advance_id', 'advance_id');
    }
}