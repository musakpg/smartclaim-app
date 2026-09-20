<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleAssignment extends Model
{
    use HasFactory;

    // Nyatakan Custom Primary Key
    protected $primaryKey = 'assignment_id';

    protected $fillable = [
        'vehicle_id',
        'user_id',
        'checkout_at',
        'checkin_at',
        'purpose'
    ];

    protected $casts = [
        'checkout_at' => 'datetime',
        'checkin_at' => 'datetime',
    ];

    // Hubungan ke Vehicle
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'vehicle_id');
    }

    // Hubungan ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}