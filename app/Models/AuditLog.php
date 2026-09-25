<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'claim_id',
        'action',
        'event_category',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Helper to log an audit action.
     * Code comments are strictly in English.
     */
    public static function log($action, $claimId = null, $oldValues = null, $newValues = null, $userId = null, $eventCategory = null, $modelType = null, $modelId = null)
    {
        return self::create([
            'user_id' => $userId ?? auth()->id() ?? 1,
            'claim_id' => $claimId,
            'action' => $action,
            'event_category' => $eventCategory,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'CLI'
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function claim()
    {
        return $this->belongsTo(Claim::class, 'claim_id', 'claim_id');
    }
}
