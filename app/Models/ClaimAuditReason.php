<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClaimAuditReason extends Model
{
    use HasFactory;

    protected $table = 'claim_audit_reasons';

    protected $fillable = [
        'code',
        'type',
        'title',
        'requires_remarks',
        'is_active',
    ];

    protected $casts = [
        'requires_remarks' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active revision reasons.
     */
    public function scopeRevisions($query)
    {
        return $query->where('type', 'REVISION')->where('is_active', true);
    }

    /**
     * Scope for active rejection reasons.
     */
    public function scopeRejections($query)
    {
        return $query->where('type', 'REJECTION')->where('is_active', true);
    }
}
