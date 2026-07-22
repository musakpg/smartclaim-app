<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable {
    use Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'user_id';
    
    protected $fillable = [
        'name', 'email', 'password', 'role'
    ];

    protected $hidden = [
        'password',
    ];

    // Hubungan Relational: Seorang user boleh hantar banyak tuntutan resit
    public function claims() {
        return $this->hasMany(Claim::class, 'user_id', 'user_id');
    }
}