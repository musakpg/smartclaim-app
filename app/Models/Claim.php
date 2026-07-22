<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    use HasFactory;

    // Explicitly declare primary key override to match schema table layout
    protected $primaryKey = 'claim_id';

    // Mass-assignment guards enabled for secure transactional database entries
    protected $fillable = [
        'user_id',
        'claim_type',
        'title',
        'merchant_name',        // Extracted via Google Vision OCR
        'location_address',      // NEW: Branch Address
        'receipt_invoice_no',    // NEW: Receipt Hash Reference Code
        'transaction_date',      // Extracted via Google Vision OCR
        'receipt_image_path',
        'extracted_raw_text',
        'predicted_category',
        'business_purpose',      // NEW: User justification notes
        'vehicle_plate_number',  // NEW: Required field for Fuel category claims
        'amount',
        'payment_method',        // NEW: Cash, Card, e-Wallet, Touch'n Go
        'status',
        'mileage_km',  // ✅ Added
        'vehicle_type', // ✅ Added
        'start_location',       // ✅ Added
        'destination_location', // ✅ Added
    ];
    /**
     * One-to-Many Relationship Configuration.
     * Defines that one claim voucher can have multiple itemized breakdown lines.
     * Overrides the default foreign key lookup to match your 'claim_id' primary key.
     */
    public function items()
    {
        return $this->hasMany(ClaimItem::class, 'claim_id', 'claim_id');
    }

    /**
     * Relationship anchor linking the claim voucher back to its submitting staff employee.
     * Maps strictly to your custom 'user_id' primary key constraint.
     */
    public function user()
    {
        // Tells Laravel that a claim belongs to a single User record
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getCalculatedAmountAttribute()
    {
        if ($this->claim_type !== 'Mileage')
            return $this->amount;

        $km = $this->mileage_km;
        $type = $this->vehicle_type; // Pastikan ada column vehicle_type di table claims

        $rates = \App\Models\MileageRate::where('vehicle_type', $type)->get();
        $total = 0;

        // Logik kira-kira
        if ($km <= 50) {
            $total = $km * $rates->where('max_km', 50)->first()->rate;
        } elseif ($km <= 150) {
            $total = (50 * $rates->where('max_km', 50)->first()->rate) +
                (($km - 50) * $rates->where('max_km', 150)->first()->rate);
        } else {
            $total = (50 * $rates->where('max_km', 50)->first()->rate) +
                (100 * $rates->where('max_km', 150)->first()->rate) +
                (($km - 150) * $rates->where('max_km', 9999)->first()->rate);
        }

        return $total;
    }
}