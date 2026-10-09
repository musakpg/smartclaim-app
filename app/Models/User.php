<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasPushSubscriptions;

    protected $table = 'users';
    protected $primaryKey = 'user_id';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'activation_token',
        'bank_name',
        'bank_account_no',
        'bank_account_holder',
        'is_demo',
    ];

    protected $casts = [
        'is_demo' => 'boolean',
    ];

    protected $hidden = [
        'password',
    ];

    // Nyatakan langganan peranti untuk penghantaran Web Push
    public function routeNotificationForWebPush()
    {
        return $this->pushSubscriptions;
    }

    // Hubungan Relational: Seorang user boleh hantar banyak tuntutan (Receipt / Mileage)
    public function claims()
    {
        return $this->hasMany(Claim::class, 'user_id', 'user_id');
    }

    // Hubungan Relational: Kenderaan peribadi yang didaftarkan atas nama staf ini
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'user_id', 'user_id');
    }

    // Mengesan sekiranya staf sedang memegang/memandu mana-mana kereta syarikat sekarang
    public function activeVehicleAssignment()
    {
        return $this->hasOne(VehicleAssignment::class, 'user_id', 'user_id')->whereNull('checkin_at');
    }

    // Sejarah keseluruhan kenderaan syarikat yang pernah dipinjam oleh staf
    public function vehicleAssignments()
    {
        return $this->hasMany(VehicleAssignment::class, 'user_id', 'user_id');
    }

    public function cashAdvances()
    {
        return $this->hasMany(CashAdvance::class, 'user_id', 'user_id');
    }

    public function activeCashAdvance()
    {
        return $this->hasOne(CashAdvance::class, 'user_id', 'user_id')
                    ->whereIn('status', ['DISBURSED_ACTIVE', 'PARTIALLY_RECONCILED'])
                    ->where('remaining_balance', '>', 0)
                    ->latestOfMany('advance_id');
    }
}