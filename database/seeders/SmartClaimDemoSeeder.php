<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Claim;
use App\Models\ClaimItem;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class SmartClaimDemoSeeder extends Seeder
{
    public function run()
    {
        // 3 Staff accounts
        $staff1 = User::firstOrCreate(
            ['email' => 'ahmad.rizqi@aeroart.com'],
            [
                'name' => 'Ahmad Rizqi',
                'password' => Hash::make('password'),
                'role' => 'staff',
            ]
        );

        $staff2 = User::firstOrCreate(
            ['email' => 'siti.nur@aeroart.com'],
            [
                'name' => 'Siti Nurhaliza',
                'password' => Hash::make('password'),
                'role' => 'staff',
            ]
        );

        $staff3 = User::firstOrCreate(
            ['email' => 'weikit.wong@aeroart.com'],
            [
                'name' => 'Wong Wei Kit',
                'password' => Hash::make('password'),
                'role' => 'staff',
            ]
        );

        // 1 Manager account
        $manager = User::firstOrCreate(
            ['email' => 'manager@aeroart.com'],
            [
                'name' => 'Faizal Tahir',
                'password' => Hash::make('password'),
                'role' => 'manager',
            ]
        );

        // 1 Finance account
        $finance = User::firstOrCreate(
            ['email' => 'finance@aeroart.com'],
            [
                'name' => 'Lee Chong Wei',
                'password' => Hash::make('password'),
                'role' => 'finance',
            ]
        );

        // 2 Mileage claims (KL to UTHM Pagoh with varied vehicle statuses)
        $mileage1 = Claim::create([
            'user_id' => $staff1->user_id,
            'title' => 'Mileage to UTHM for Project Meeting',
            'claim_type' => 'Mileage',
            'status' => 'Pending',
            'start_location' => 'Kuala Lumpur, Federal Territory of Kuala Lumpur, Malaysia',
            'destination_location' => 'Universiti Tun Hussein Onn Malaysia (UTHM) Kampus Cawangan Pagoh, Pagoh, Johor, Malaysia',
            'mileage_km' => 175.5,
            'vehicle_type' => 'Car',
            'vehicle_plate_number' => 'JKE 1234',
            'business_purpose' => 'Meeting with UTHM for new project',
            'amount' => 0.00,
            'transaction_date' => Carbon::now()->subDays(2)->format('Y-m-d'),
        ]);
        
        $mileage2 = Claim::create([
            'user_id' => $staff2->user_id,
            'title' => 'Site Visit to Melaka Branch',
            'claim_type' => 'Mileage',
            'status' => 'Approved',
            'start_location' => 'Shah Alam, Selangor, Malaysia',
            'destination_location' => 'Aero Art Transport Branch, Melaka, Malaysia',
            'mileage_km' => 140.2,
            'vehicle_type' => 'Motorcycle',
            'vehicle_plate_number' => 'BQM 9922',
            'business_purpose' => 'Site visit for logistics setup',
            'amount' => 0.00,
            'transaction_date' => Carbon::now()->subDays(5)->format('Y-m-d'),
        ]);

        AuditLog::create([
            'claim_id' => $mileage2->claim_id,
            'user_id' => $manager->user_id,
            'action' => 'Status changed to Approved',
            'new_values' => ['remarks' => 'Distance verified and within limits.'],
        ]);

        // 4 Receipt claims (with images)
        $receipt1 = Claim::create([
            'user_id' => $staff1->user_id,
            'title' => 'Fuel for Company Trip',
            'claim_type' => 'Receipt',
            'status' => 'Pending',
            'merchant_name' => 'Petronas Station',
            'receipt_invoice_no' => 'INV-PTR-001',
            'predicted_category' => 'Fuel/Petrol',
            'payment_method' => 'Corporate Card',
            'amount' => 60.50,
            'transaction_date' => Carbon::now()->subDays(1)->format('Y-m-d'),
            'location_address' => 'Petronas, Pagoh, Johor',
            'business_purpose' => 'Fuel for company trip',
            'receipt_image_path' => 'demo/petronas_receipt.jpg',
            'is_policy_violation' => false,
            'risk_score' => 10,
        ]);
        ClaimItem::create(['claim_id' => $receipt1->claim_id, 'item_name' => 'RON95', 'quantity' => 1, 'unit_price' => 60.50, 'subtotal' => 60.50]);

        $receipt2 = Claim::create([
            'user_id' => $staff3->user_id,
            'title' => 'Office Stationery Restock',
            'claim_type' => 'Receipt',
            'status' => 'Approved',
            'merchant_name' => 'Stationery World',
            'receipt_invoice_no' => 'SW-2023-11',
            'predicted_category' => 'Office Supplies',
            'payment_method' => 'Cash',
            'amount' => 125.00,
            'transaction_date' => Carbon::now()->subDays(10)->format('Y-m-d'),
            'location_address' => 'Subang Jaya, Selangor',
            'business_purpose' => 'Office stationery restock',
            'receipt_image_path' => 'demo/stationery_receipt.jpg',
            'is_policy_violation' => false,
            'risk_score' => 5,
        ]);
        ClaimItem::create(['claim_id' => $receipt2->claim_id, 'item_name' => 'A4 Paper Bundle', 'quantity' => 5, 'unit_price' => 20.00, 'subtotal' => 100.00]);
        ClaimItem::create(['claim_id' => $receipt2->claim_id, 'item_name' => 'Pens', 'quantity' => 5, 'unit_price' => 5.00, 'subtotal' => 25.00]);

        AuditLog::create([
            'claim_id' => $receipt2->claim_id,
            'user_id' => $manager->user_id,
            'action' => 'Status changed to Approved',
            'new_values' => ['remarks' => 'Necessary for operations.'],
        ]);

        $receipt3 = Claim::create([
            'user_id' => $staff2->user_id,
            'title' => 'Client Meeting Coffee',
            'claim_type' => 'Receipt',
            'status' => 'Rejected',
            'merchant_name' => 'Starbucks Coffee',
            'receipt_invoice_no' => 'SB-12345',
            'predicted_category' => 'Meals & Entertainment',
            'payment_method' => 'Personal Card',
            'amount' => 85.00,
            'transaction_date' => Carbon::now()->subDays(3)->format('Y-m-d'),
            'location_address' => 'KL Sentral, Kuala Lumpur',
            'business_purpose' => 'Client meeting coffee',
            'receipt_image_path' => 'demo/starbucks_receipt.jpg',
            'is_policy_violation' => true,
            'policy_violation_reason' => 'Exceeds meal allowance limit for single attendee.',
            'risk_score' => 75,
            'fraud_flags' => [
                ['flag_type' => 'LIMIT_BREACH', 'severity' => 'warning', 'title' => 'High Meal Amount', 'description' => 'The amount RM85 exceeds standard RM50 limit for one person.']
            ]
        ]);
        ClaimItem::create(['claim_id' => $receipt3->claim_id, 'item_name' => 'Coffee & Pastries', 'quantity' => 1, 'unit_price' => 85.00, 'subtotal' => 85.00]);
        
        AuditLog::create([
            'claim_id' => $receipt3->claim_id,
            'user_id' => $manager->user_id,
            'action' => 'Status changed to Rejected',
            'new_values' => ['remarks' => 'Amount exceeds the daily meal allowance limit. Please provide justification.'],
        ]);

        $receipt4 = Claim::create([
            'user_id' => $staff1->user_id,
            'title' => 'New Office Desk for Branch',
            'claim_type' => 'Receipt',
            'status' => 'Pre-Approved',
            'merchant_name' => 'IKEA Corporate',
            'receipt_invoice_no' => 'IKEA-99812',
            'predicted_category' => 'Office Equipment',
            'payment_method' => 'Bank Transfer',
            'amount' => 450.00,
            'transaction_date' => Carbon::now()->subDays(4)->format('Y-m-d'),
            'location_address' => 'Damansara, Selangor',
            'business_purpose' => 'New desk for branch office',
            'receipt_image_path' => 'demo/ikea_receipt.jpg',
            'is_policy_violation' => false,
            'risk_score' => 20,
        ]);
        ClaimItem::create(['claim_id' => $receipt4->claim_id, 'item_name' => 'Office Desk', 'quantity' => 1, 'unit_price' => 450.00, 'subtotal' => 450.00]);
        
        AuditLog::create([
            'claim_id' => $receipt4->claim_id,
            'user_id' => $manager->user_id,
            'action' => 'Status changed to Pre-Approved',
            'new_values' => ['remarks' => 'Manager approved, awaiting finance audit.'],
        ]);
    }
}
