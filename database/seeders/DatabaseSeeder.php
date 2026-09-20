<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Models\MileageRate;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with standard corporate user tier profiles.
     */
    public function run(): void
    {
        // 👤 Entity Tier 1: Regular Corporate Employee Node (Staff)
        $staff = User::create([
            'name' => 'Musa (Staff Employee)',
            'email' => 'musa@aeroart.com',
            'password' => bcrypt('musa123'),
            'role' => 'Staff'
        ]);

        // 🔍 Entity Tier 2: Account Ledger Auditor Node (Finance)
        $finance = User::create([
            'name' => 'Hazman (Finance Auditor)',
            'email' => 'finance@aeroart.com',
            'password' => bcrypt('finance123'),
            'role' => 'Finance'
        ]);

        // 👑 Entity Tier 3: Executive Approving Officer Node (Manager)
        $manager = User::create([
            'name' => 'Aero Art Manager',
            'email' => 'manager@aeroart.com',
            'password' => bcrypt('manager123'),
            'role' => 'Manager'
        ]);

        // 🚗 Entity Tier 4: Vehicles Registry
        // 1. Kereta Peribadi Staf (Layak Tuntut Mileage + Roadtax Valid)
        Vehicle::create([
            'user_id' => $staff->user_id,
            'plate_number' => 'JWA 1234',
            'brand_model' => 'Perodua Myvi 1.5 AV',
            'vehicle_type' => 'Car',
            'engine_capacity' => 1500,
            'roadtax_expiry' => now()->addMonths(8)->toDateString(),
            'ownership_type' => 'personal',
            'status' => 'Active',
        ]);

        // 2. Motor Peribadi Staf (Roadtax Mati untuk testing validation error)
        Vehicle::create([
            'user_id' => $staff->user_id,
            'plate_number' => 'JWB 5678',
            'brand_model' => 'Yamaha Y15ZR',
            'vehicle_type' => 'Motorcycle',
            'engine_capacity' => 150,
            'roadtax_expiry' => now()->subDays(10)->toDateString(), // Expired
            'ownership_type' => 'personal',
            'status' => 'Active',
        ]);

        // 3. Kenderaan Milik Syarikat Aero Art (Aset Company)
        $companyCar = Vehicle::create([
            'user_id' => null, // Milik syarikat
            'plate_number' => 'VAA 9988',
            'brand_model' => 'Toyota Hiace Panel Van',
            'vehicle_type' => 'Car',
            'engine_capacity' => 2500,
            'roadtax_expiry' => now()->addMonths(12)->toDateString(),
            'ownership_type' => 'company',
            'status' => 'Active',
        ]);

        // 📋 Entity Tier 5: Current Company Fleet Assignment (Track Who is Using Company Car)
        VehicleAssignment::create([
            'vehicle_id' => $companyCar->vehicle_id,
            'user_id' => $staff->user_id,
            'checkout_at' => now()->subHours(3),
            'checkin_at' => null, // Masih aktif di tangan staf
            'purpose' => 'Penghantaran drone & lawatan tapak klien Senai',
        ]);

        // 📊 Entity Tier 6: Mileage Tier Rates
        MileageRate::insert([
            // Kereta
            ['vehicle_type' => 'Car', 'min_km' => 0, 'max_km' => 50, 'rate' => 0.80, 'created_at' => now(), 'updated_at' => now()],
            ['vehicle_type' => 'Car', 'min_km' => 51, 'max_km' => 150, 'rate' => 0.70, 'created_at' => now(), 'updated_at' => now()],
            ['vehicle_type' => 'Car', 'min_km' => 151, 'max_km' => 9999, 'rate' => 0.60, 'created_at' => now(), 'updated_at' => now()],
            // Motor
            ['vehicle_type' => 'Motorcycle', 'min_km' => 0, 'max_km' => 50, 'rate' => 0.50, 'created_at' => now(), 'updated_at' => now()],
            ['vehicle_type' => 'Motorcycle', 'min_km' => 51, 'max_km' => 150, 'rate' => 0.40, 'created_at' => now(), 'updated_at' => now()],
            ['vehicle_type' => 'Motorcycle', 'min_km' => 151, 'max_km' => 9999, 'rate' => 0.30, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}