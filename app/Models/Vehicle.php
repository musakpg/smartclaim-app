<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Vehicle extends Model
{
    use HasFactory;

    protected $primaryKey = 'vehicle_id';

    protected $fillable = [
        'user_id',
        'plate_number',
        'brand_model',
        'vehicle_type',            // 'Car' or 'Motorcycle'
        'engine_capacity',        // e.g. 1500, 150
        'ownership_type',         // 'personal' or 'company'
        'roadtax_expiry',
        'grant_document_path',
        'roadtax_document_path',
        'status',                 // 'Active', 'Inactive'
        'approval_status',        // 'Pending', 'Approved', 'Rejected'
        'rejection_reason',
        'approved_by',
        'approved_at',
        'roadtax_renewal_status', // 'None', 'Pending_Review'
    ];

    protected $casts = [
        'roadtax_expiry' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * Relationship: The staff owner of this personal vehicle.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Relationship: The manager who approved/verified this vehicle.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    /**
     * Relationship: All claims associated with this vehicle.
     */
    public function claims()
    {
        return $this->hasMany(Claim::class, 'vehicle_id', 'vehicle_id');
    }

    /**
     * Helper: Check if roadtax is strictly expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->roadtax_expiry ? Carbon::parse($this->roadtax_expiry)->isPast() : true;
    }

    /**
     * Helper: Check if roadtax is expiring within the 30-day corporate window.
     */
    public function getIsExpiringSoonAttribute(): bool
    {
        if (!$this->roadtax_expiry || $this->is_expired) {
            return false;
        }

        return Carbon::now()->diffInDays(Carbon::parse($this->roadtax_expiry), false) <= 30;
    }

    /**
     * Helper: Check if staff is authorized to edit or cancel the registration.
     */
    public function getCanBeEditedAttribute(): bool
    {
        return $this->approval_status === 'Pending' || $this->approval_status === 'Rejected';
    }

    /**
     * Helper: Check if vehicle is eligible for active mileage submission.
     */
    public function getIsEligibleForClaimAttribute(): bool
    {
        return $this->status === 'Active'
            && $this->approval_status === 'Approved'
            && !$this->is_expired;
    }
}