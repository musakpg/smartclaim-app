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
        'vehicle_id',            // ✅ Ditambah
        'claim_type',
        'title',
        'merchant_name',         // Extracted via Google Vision OCR
        'location_address',       // Branch Address
        'receipt_invoice_no',     // Receipt Reference Code
        'transaction_date',       // Extracted via Google Vision OCR
        'receipt_image_path',
        'extracted_raw_text',
        'predicted_category',
        'category_id',
        'business_purpose',       // User justification notes
        'vehicle_plate_number',   // Plate number snapshot
        'amount',
        'payment_method',         // Cash, Card, e-Wallet, Touch'n Go, Allowance
        'status',
        'estimated_payout_date', // ✅ Ditambah (SLA Tracking)
        'mileage_km',
        'vehicle_type',
        'start_location',
        'destination_location',
        'is_policy_violation',
        'policy_violation_reason',
        'revision_reason',
        'rejection_reason',
        'remarks',
        'receipt_image_hash',
        'risk_score',
        'fraud_flags',
        'exif_date_taken',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'estimated_payout_date' => 'date',
        'amount' => 'decimal:2',
        'mileage_km' => 'decimal:2',
        'fraud_flags' => 'array',
        'is_policy_violation' => 'boolean',
        'risk_score' => 'integer',
    ];

    /**
     * One-to-Many Relationship Configuration.
     */
    public function items()
    {
        return $this->hasMany(ClaimItem::class, 'claim_id', 'claim_id');
    }

    /**
     * Relationship linking the claim voucher back to its submitting staff employee.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Relationship linking the claim voucher to the chosen vehicle.
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'vehicle_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'claim_id', 'claim_id')->orderBy('created_at', 'asc');
    }

    public function getCalculatedAmountAttribute()
    {
        if ($this->claim_type !== 'Mileage') {
            return $this->amount;
        }

        $km = (float) $this->mileage_km;
        $rate = $this->vehicle_type === 'Motorcycle' ? 0.30 : 0.60;
        
        return $km * $rate;
    }
    public function reimburser()
    {
        return $this->belongsTo(User::class, 'reimbursed_by', 'user_id');
    }

    public function cashAdvance()
    {
        return $this->belongsTo(CashAdvance::class, 'cash_advance_id', 'advance_id');
    }

    public function getEstimatedCompletionAtAttribute()
    {
        return app(\App\Services\SlaTrackingService::class)->getEstimatedCompletionAt($this);
    }

    public function getSlaStatusAttribute()
    {
        return app(\App\Services\SlaTrackingService::class)->getSlaStatus($this);
    }

    public function getTimeRemainingHumanAttribute()
    {
        return app(\App\Services\SlaTrackingService::class)->getTimeRemainingHuman($this);
    }
}
