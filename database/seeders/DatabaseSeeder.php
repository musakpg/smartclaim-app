<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with standard corporate user tier profiles.
     */
    public function run(): void
    {
        // 👤 Entity Tier 1: Regular Corporate Employee Node (Staff)
        User::create([
            'name' => 'Musa (Staff Employee)',
            'email' => 'musa@aeroart.com',
            'password' => bcrypt('musa123'), // Global fallback login password key
            'role' => 'Staff'
        ]);

        // 🔍 Entity Tier 2: Account Ledger Auditor Node (Finance)
        User::create([
            'name' => 'Hazman (Finance Auditor)',
            'email' => 'finance@aeroart.com',
            'password' => bcrypt('hazmaan123'), // Dedicated finance access token key
            'role' => 'Finance'
        ]);

        // 👑 Entity Tier 3: Executive Approving Officer Node (Manager)
        User::create([
            'name' => 'Aero Art Manager',
            'email' => 'manager@aeroart.com',
            'password' => bcrypt('manager123'), // Dedicated executive access token key
            'role' => 'Manager'
        ]);

        // DatabaseSeeder.php atau buat seeder baru
        MileageRate::insert([
            // Kereta
            ['vehicle_type' => 'Car', 'min_km' => 0, 'max_km' => 50, 'rate' => 0.80],
            ['vehicle_type' => 'Car', 'min_km' => 51, 'max_km' => 150, 'rate' => 0.70],
            ['vehicle_type' => 'Car', 'min_km' => 151, 'max_km' => 9999, 'rate' => 0.60],
            // Motor
            ['vehicle_type' => 'Motorcycle', 'min_km' => 0, 'max_km' => 50, 'rate' => 0.50],
            ['vehicle_type' => 'Motorcycle', 'min_km' => 51, 'max_km' => 150, 'rate' => 0.40],
            ['vehicle_type' => 'Motorcycle', 'min_km' => 151, 'max_km' => 9999, 'rate' => 0.30],
        ]);
    }
}