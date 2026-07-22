<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = ['user_id', 'action', 'payload', 'ip_address'];

    public static function log($action, $payload = [])
    {
        self::create([
            'user_id' => auth()->id() ?? 1, // Default ke 1 jika guest
            'action' => $action,
            'payload' => json_encode($payload),
            'ip_address' => request()->ip()
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
