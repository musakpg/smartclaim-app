<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClaimItem extends Model
{
    use HasFactory;

    // Mass-assignment guards for itemized line breakdowns
    protected $fillable = [
        'claim_id', 
        'item_name', 
        'quantity', 
        'unit_price', 
        'subtotal'
    ];

    /**
     * Inverse Relationship Configuration.
     * Links the item breakdown line back to its parent claim record voucher.
     */
    public function claim()
    {
        return $this->belongsTo(Claim::class, 'claim_id', 'claim_id');
    }
}