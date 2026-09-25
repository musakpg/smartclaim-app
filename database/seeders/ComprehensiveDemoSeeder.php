<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Claim;
use App\Models\ClaimItem;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\ExpensePolicy;
use App\Models\Vehicle;
use App\Models\MileageRate;
use App\Models\ClaimAuditReason;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class ComprehensiveDemoSeeder extends Seeder
{
    public function run()
    {
        // 0. Clean prior claim transactions for idempotent baseline re-seeding
        Schema::disableForeignKeyConstraints();
        ClaimAuditReason::truncate();
        ClaimItem::truncate();
        AuditLog::truncate();
        \App\Models\AiLearningFeedback::truncate();
        Claim::truncate();
        Schema::enableForeignKeyConstraints();

        // 0.1 Seed Enterprise Audit Exception Codes
        $auditReasons = [
            // REVISION types
            [
                'code' => 'IMG_BLUR',
                'type' => 'REVISION',
                'title' => 'Blurry / Unreadable Receipt',
                'requires_remarks' => false,
                'is_active' => true,
            ],
            [
                'code' => 'DOC_MISSING',
                'type' => 'REVISION',
                'title' => 'Missing Supporting Document',
                'requires_remarks' => false,
                'is_active' => true,
            ],
            [
                'code' => 'AMT_MISMATCH',
                'type' => 'REVISION',
                'title' => 'Incorrect Amount / Merchant Details',
                'requires_remarks' => false,
                'is_active' => true,
            ],
            [
                'code' => 'MILEAGE_JUST',
                'type' => 'REVISION',
                'title' => 'Incomplete Mileage Justification',
                'requires_remarks' => false,
                'is_active' => true,
            ],
            [
                'code' => 'OTHER_REVISION',
                'type' => 'REVISION',
                'title' => 'Other Clarification Required',
                'requires_remarks' => true,
                'is_active' => true,
            ],
            // REJECTION types
            [
                'code' => 'POLICY_EXCEEDED',
                'type' => 'REJECTION',
                'title' => 'Policy Breach: Exceeded Ceiling Limit',
                'requires_remarks' => false,
                'is_active' => true,
            ],
            [
                'code' => 'DUP_CLAIM',
                'type' => 'REJECTION',
                'title' => 'Duplicate Claim Submission',
                'requires_remarks' => false,
                'is_active' => true,
            ],
            [
                'code' => 'PERSONAL_EXPENSE',
                'type' => 'REJECTION',
                'title' => 'Non-Claimable Personal Expense',
                'requires_remarks' => false,
                'is_active' => true,
            ],
            [
                'code' => 'GRACE_EXPIRED',
                'type' => 'REJECTION',
                'title' => 'Submission Grace Period Expired',
                'requires_remarks' => false,
                'is_active' => true,
            ],
            [
                'code' => 'OTHER_REJECTION',
                'type' => 'REJECTION',
                'title' => 'Other Violation (Specific Justification)',
                'requires_remarks' => true,
                'is_active' => true,
            ],
        ];

        foreach ($auditReasons as $reason) {
            ClaimAuditReason::create($reason);
        }

        // 1. Seed Categories
        $categories = [
            [
                'name' => 'Site Tools & Hardware (Mr. DIY, Tools, Repairs)',
                'code' => 'site-tools-hardware',
                'keywords' => 'mr diy, mr. diy, ace hardware, hardware, hammer, screw, drill, wrench, wire, tools, peralatan, pemutar skru, paku, repair',
                'description' => 'Aero Art operational purchases and hardware.'
            ],
            [
                'name' => 'Office Pantry & Amenities (Groceries, Supplies)',
                'code' => 'office-pantry-amenities',
                'keywords' => "lotus, lotus's, giant, aeon, mydin, econsave, grocery, tea, teh, nescafe, coffee, sugar, gula, mineral water, tissue, snacks, pantry",
                'description' => 'Office pantry supplies and groceries.'
            ],
            [
                'name' => 'Staff Operational Meals',
                'code' => 'staff-operational-meals',
                'keywords' => 'mcdonalds, mcd, kfc, restaurant, restoran, cafe, starbucks, dinner, lunch, breakfast, food, makanan, pizza, subway, bistro, eatery, mamak, nasi kandar',
                'description' => 'Client dining, team lunches, and entertainment.'
            ],
            [
                'name' => 'Fuel & Fleet Logistics (Corporate Fleet / Petrol)',
                'code' => 'fuel-fleet-logistics',
                'keywords' => 'petronas, shell, caltex, petron, diesel, petrol, ron95, ron97, fuel, minyak, buraqoil, primax, v-power',
                'description' => 'Fuel and fleet maintenance.'
            ],
            [
                'name' => 'Office Supplies & Stationery',
                'code' => 'office-supplies-stationery',
                'keywords' => 'popular, stationery, paper, a4, printing, ink, toner, pen, notebook, alat tulis, staples, printer, cartridge',
                'description' => 'Stationery, software, and minor office equipment.'
            ],
            [
                'name' => 'Travel & Lodging',
                'code' => 'travel-lodging',
                'keywords' => 'hotel, resort, inn, agoda, booking.com, lodging, airbnb, stay, homestay, accommodation, penginapan',
                'description' => 'Accommodations.'
            ],
            [
                'name' => 'Transportation & Toll',
                'code' => 'transportation-toll',
                'keywords' => 'touch n go, tng, rfid, toll, tol, plus, parking, grab, taxi, train, lrt, mrt, ktm, flight, airasia, bus, tiket, transit',
                'description' => 'Transport, mileage, and tolls.'
            ]
        ];

        foreach ($categories as $catData) {
            Category::updateOrCreate(['name' => $catData['name']], [
                'code' => $catData['code'],
                'keywords' => $catData['keywords'],
                'description' => $catData['description'],
                'is_active' => true
            ]);
        }

        $toolsCat = Category::where('code', 'site-tools-hardware')->first();
        $pantryCat = Category::where('code', 'office-pantry-amenities')->first();
        $mealsCat = Category::where('code', 'staff-operational-meals')->first();
        $fuelCat = Category::where('code', 'fuel-fleet-logistics')->first();
        $officeCat = Category::where('code', 'office-supplies-stationery')->first();
        $travelCat = Category::where('code', 'travel-lodging')->first();
        $transportCat = Category::where('code', 'transportation-toll')->first();

        // 2. Seed Expense Policies
        $policies = [
            ['category_id' => $toolsCat->id, 'monthly_budget_cap' => 3000.00, 'max_single_claim_limit' => 500.00, 'description' => 'Field maintenance & repairs'],
            ['category_id' => $pantryCat->id, 'monthly_budget_cap' => 1000.00, 'max_single_claim_limit' => 200.00, 'description' => 'Monthly staff pantry allocations'],
            ['category_id' => $mealsCat->id, 'monthly_budget_cap' => 1500.00, 'max_single_claim_limit' => 150.00, 'description' => 'Client dining, team lunch limits'],
            ['category_id' => $fuelCat->id, 'monthly_budget_cap' => 5000.00, 'max_single_claim_limit' => 300.00, 'description' => 'Fleet refueling limits'],
            ['category_id' => $officeCat->id, 'monthly_budget_cap' => 2000.00, 'max_single_claim_limit' => 400.00, 'description' => 'Stationery and paper supply limits'],
            ['category_id' => $travelCat->id, 'monthly_budget_cap' => 4000.00, 'max_single_claim_limit' => 800.00, 'description' => 'Outstation hotel allocations'],
            ['category_id' => $transportCat->id, 'monthly_budget_cap' => 2500.00, 'max_single_claim_limit' => 250.00, 'description' => 'Toll, parking and transit limits'],
        ];

        foreach ($policies as $pol) {
            ExpensePolicy::updateOrCreate(['category_id' => $pol['category_id']], [
                'monthly_budget_cap' => $pol['monthly_budget_cap'],
                'max_single_claim_limit' => $pol['max_single_claim_limit'],
                'description' => $pol['description'],
                'is_active' => true,
            ]);
        }

        // 3. Seed Mileage Rates
        MileageRate::updateOrCreate(['vehicle_type' => 'Car'], ['min_km' => 0, 'max_km' => null, 'rate' => 0.60]);
        MileageRate::updateOrCreate(['vehicle_type' => 'Motorcycle'], ['min_km' => 0, 'max_km' => null, 'rate' => 0.30]);

        // 4. Seed Users (RBAC)
        $manager = User::firstOrCreate(['email' => 'manager@aeroart.com'], [
            'name' => 'Dr. Azman Shah', 'password' => Hash::make('password'), 'role' => 'manager',
        ]);
        $staff1 = User::firstOrCreate(['email' => 'staff@aeroart.com'], [
            'name' => 'Ahmad Faiz', 'password' => Hash::make('password'), 'role' => 'staff',
        ]);
        $staff2 = User::firstOrCreate(['email' => 'siti@aeroart.com'], [
            'name' => 'Siti Nurhaliza', 'password' => Hash::make('password'), 'role' => 'staff',
        ]);
        $staff3 = User::firstOrCreate(['email' => 'tan@aeroart.com'], [
            'name' => 'Tan Wei Kiat', 'password' => Hash::make('password'), 'role' => 'staff',
        ]);
        $finance = User::firstOrCreate(['email' => 'finance@aeroart.com'], [
            'name' => 'Lee Chong Wei', 'password' => Hash::make('password'), 'role' => 'finance',
        ]);

        // 5. Seed Vehicles (Varied Statuses)
        $vehicle1 = Vehicle::firstOrCreate(['plate_number' => 'JKE 1234'], [
            'user_id' => $staff1->user_id, 'vehicle_type' => 'Car', 'brand_model' => 'Perodua Myvi',
            'ownership_type' => 'personal', 'approval_status' => 'Approved',
            'roadtax_expiry' => Carbon::now()->addMonths(6)->format('Y-m-d')
        ]);
        $vehicle2 = Vehicle::firstOrCreate(['plate_number' => 'BQM 9922'], [
            'user_id' => $staff2->user_id, 'vehicle_type' => 'Motorcycle', 'brand_model' => 'Honda EX5',
            'ownership_type' => 'personal', 'approval_status' => 'Pending',
            'roadtax_expiry' => Carbon::now()->addMonths(2)->format('Y-m-d')
        ]);
        $vehicle3 = Vehicle::firstOrCreate(['plate_number' => 'VBD 5511'], [
            'user_id' => $staff3->user_id, 'vehicle_type' => 'Car', 'brand_model' => 'Proton Saga',
            'ownership_type' => 'personal', 'approval_status' => 'Rejected',
            'roadtax_expiry' => Carbon::now()->subMonths(1)->format('Y-m-d')
        ]);
        $fleet1 = Vehicle::firstOrCreate(['plate_number' => 'WA 8888 A'], [
            'user_id' => $manager->user_id, 'vehicle_type' => 'Car', 'brand_model' => 'Toyota Hiace',
            'ownership_type' => 'company', 'approval_status' => 'Approved',
            'roadtax_expiry' => Carbon::now()->addMonths(10)->format('Y-m-d')
        ]);

        // 6. Seed Claims Representing Unified Lifecycle States
        // Lifecycle: SUBMITTED (Pending) -> PRE_APPROVED -> APPROVED -> DISBURSED (Reimbursed)
        // Exceptions: REJECTED, REVISION_REQUIRED

        // 6.1 State: PRE_APPROVED (Waiting for Manager Sign-off) - Mileage Claim
        $preApprovedMileage = Claim::create([
            'user_id' => $staff1->user_id, 'vehicle_id' => $vehicle1->vehicle_id,
            'title' => 'Mileage to UTHM for Project Meeting', 'claim_type' => 'Mileage', 'status' => 'Pre-Approved',
            'start_location' => 'Kuala Lumpur, Federal Territory of Kuala Lumpur, Malaysia',
            'destination_location' => 'Universiti Tun Hussein Onn Malaysia (UTHM) Kampus Cawangan Pagoh, Pagoh, Johor, Malaysia',
            'mileage_km' => 175.5, 'vehicle_type' => 'Car', 'vehicle_plate_number' => 'JKE 1234',
            'business_purpose' => 'Meeting with UTHM for new project', 'amount' => 175.5 * 0.60,
            'transaction_date' => Carbon::now()->subDays(5)->format('Y-m-d'), 'category_id' => $travelCat->id,
            'predicted_category' => 'Travel & Lodging',
            'created_at' => Carbon::now()->subDays(4),
            'updated_at' => Carbon::now()->subDays(2)
        ]);
        AuditLog::create([
            'claim_id' => $preApprovedMileage->claim_id, 'user_id' => $staff1->user_id, 'action' => 'Claim Submitted',
            'new_values' => ['status' => 'Pending'],
            'created_at' => Carbon::now()->subDays(4), 'updated_at' => Carbon::now()->subDays(4)
        ]);
        AuditLog::create([
            'claim_id' => $preApprovedMileage->claim_id, 'user_id' => $finance->user_id, 'action' => 'Claim Pre-Approved',
            'old_values' => ['status' => 'Pending'],
            'new_values' => ['status' => 'Pre-Approved', 'remarks' => 'Verified route and mileage distance parameters.'],
            'created_at' => Carbon::now()->subDays(2), 'updated_at' => Carbon::now()->subDays(2)
        ]);

        // 6.2 State: PRE_APPROVED (Waiting for Manager Sign-off) - High Risk Receipt Claim
        $fraudClaim = Claim::create([
            'user_id' => $staff3->user_id, 'title' => 'High End Dinner', 'claim_type' => 'Receipt',
            'status' => 'Pre-Approved', 'merchant_name' => 'Nobu KL',
            'receipt_invoice_no' => 'INV-9999', 'amount' => 450.00,
            'transaction_date' => Carbon::now()->subDays(1)->format('Y-m-d'),
            'payment_method' => 'Card', 'category_id' => $mealsCat->id,
            'predicted_category' => 'Staff Operational Meals',
            'business_purpose' => 'Dinner with client', 'receipt_image_path' => 'receipts/default.png',
            'fraud_flags' => [['flag_type' => 'Weekend/Late Night Transaction', 'severity' => 'High', 'description' => 'Transaction occurred on a weekend outside business hours.']],
            'risk_score' => 85, 'is_policy_violation' => true,
            'policy_violation_reason' => 'Amount RM 450 exceeds maximum allowed RM 150 for Meals & Entertainment.',
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDay()
        ]);
        AuditLog::create([
            'claim_id' => $fraudClaim->claim_id, 'user_id' => $staff3->user_id, 'action' => 'Claim Submitted',
            'new_values' => ['status' => 'Pending'],
            'created_at' => Carbon::now()->subDays(2), 'updated_at' => Carbon::now()->subDays(2)
        ]);
        AuditLog::create([
            'claim_id' => $fraudClaim->claim_id, 'user_id' => $finance->user_id, 'action' => 'Claim Pre-Approved',
            'old_values' => ['status' => 'Pending'],
            'new_values' => ['status' => 'Pre-Approved', 'remarks' => 'Pre-approved with fraud advisory for manager decision.'],
            'created_at' => Carbon::now()->subDay(), 'updated_at' => Carbon::now()->subDay()
        ]);

        // 6.3 State: PENDING (Submitted, Waiting for Finance Audit)
        $submittedClaim = Claim::create([
            'user_id' => $staff1->user_id, 'title' => 'Site Tools from Mr. DIY', 'claim_type' => 'Receipt',
            'status' => 'Pending', 'merchant_name' => 'MR D.I.Y. Sdn Bhd',
            'receipt_invoice_no' => 'DIY-77881', 'amount' => 125.80,
            'transaction_date' => Carbon::now()->subDays(2)->format('Y-m-d'),
            'payment_method' => 'Cash', 'category_id' => $toolsCat->id,
            'predicted_category' => 'Site Tools & Hardware (Mr. DIY, Tools, Repairs)',
            'business_purpose' => 'Field repair screws and wire strippers',
            'receipt_image_path' => 'receipts/default.png',
            'risk_score' => 0, 'is_policy_violation' => false,
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDays(2)
        ]);
        ClaimItem::create(['claim_id' => $submittedClaim->claim_id, 'item_name' => 'Screwdriver Set', 'quantity' => 2, 'unit_price' => 35.00, 'subtotal' => 70.00]);
        ClaimItem::create(['claim_id' => $submittedClaim->claim_id, 'item_name' => 'Heavy Duty Wire Stripper', 'quantity' => 1, 'unit_price' => 55.80, 'subtotal' => 55.80]);
        AuditLog::create([
            'claim_id' => $submittedClaim->claim_id, 'user_id' => $staff1->user_id, 'action' => 'Claim Submitted',
            'new_values' => ['status' => 'Pending'],
            'created_at' => Carbon::now()->subDays(2), 'updated_at' => Carbon::now()->subDays(2)
        ]);

        // 6.4 State: APPROVED (Manager Signed-off, Waiting for Finance Disbursement)
        $approvedClaim = Claim::create([
            'user_id' => $staff2->user_id, 'title' => 'Client Lunch Briefing', 'claim_type' => 'Receipt',
            'status' => 'Approved', 'merchant_name' => 'Starbucks Coffee',
            'receipt_invoice_no' => 'SBX-4421', 'amount' => 68.00,
            'transaction_date' => Carbon::now()->subDays(4)->format('Y-m-d'),
            'payment_method' => 'Card', 'category_id' => $mealsCat->id,
            'predicted_category' => 'Staff Operational Meals',
            'business_purpose' => 'Quarterly progress review with key partner',
            'receipt_image_path' => 'receipts/default.png',
            'risk_score' => 0, 'is_policy_violation' => false,
            'created_at' => Carbon::now()->subDays(4),
            'updated_at' => Carbon::now()->subDays(1)
        ]);
        AuditLog::create([
            'claim_id' => $approvedClaim->claim_id, 'user_id' => $staff2->user_id, 'action' => 'Claim Submitted',
            'new_values' => ['status' => 'Pending'],
            'created_at' => Carbon::now()->subDays(4), 'updated_at' => Carbon::now()->subDays(4)
        ]);
        AuditLog::create([
            'claim_id' => $approvedClaim->claim_id, 'user_id' => $finance->user_id, 'action' => 'Claim Pre-Approved',
            'old_values' => ['status' => 'Pending'], 'new_values' => ['status' => 'Pre-Approved'],
            'created_at' => Carbon::now()->subDays(3), 'updated_at' => Carbon::now()->subDays(3)
        ]);
        AuditLog::create([
            'claim_id' => $approvedClaim->claim_id, 'user_id' => $manager->user_id, 'action' => 'CLAIM_Approved',
            'old_values' => ['status' => 'Pre-Approved'],
            'new_values' => ['status' => 'Approved', 'signed_by' => 'Dr. Azman Shah', 'remarks' => 'Authorized. Please disburse via bank transfer.'],
            'created_at' => Carbon::now()->subDays(1), 'updated_at' => Carbon::now()->subDays(1)
        ]);

        // 6.5 State: REIMBURSED (Disbursed & Completed)
        $reimbursedClaim = Claim::create([
            'user_id' => $staff2->user_id, 'title' => 'Stationery Refill', 'claim_type' => 'Receipt',
            'status' => 'Reimbursed', 'merchant_name' => 'Popular Bookstore',
            'receipt_invoice_no' => 'POP-1029', 'amount' => 55.00,
            'transaction_date' => Carbon::now()->subDays(10)->format('Y-m-d'),
            'payment_method' => 'Cash', 'category_id' => $officeCat->id,
            'predicted_category' => 'Office Supplies & Stationery',
            'business_purpose' => 'Office A4 paper and pens', 'receipt_image_path' => 'receipts/default.png',
            'fraud_flags' => [], 'risk_score' => 5, 'is_policy_violation' => false,
            'paid_at' => Carbon::now()->subDays(7),
            'payment_reference' => 'TRX-MYR-20260918-004',
            'created_at' => Carbon::now()->subDays(10),
            'updated_at' => Carbon::now()->subDays(7)
        ]);
        ClaimItem::create(['claim_id' => $reimbursedClaim->claim_id, 'item_name' => 'A4 Paper', 'quantity' => 2, 'unit_price' => 15.00, 'subtotal' => 30.00]);
        ClaimItem::create(['claim_id' => $reimbursedClaim->claim_id, 'item_name' => 'Pens', 'quantity' => 5, 'unit_price' => 5.00, 'subtotal' => 25.00]);
        AuditLog::create([
            'claim_id' => $reimbursedClaim->claim_id, 'user_id' => $manager->user_id, 'action' => 'CLAIM_Approved',
            'new_values' => ['status' => 'Approved', 'remarks' => 'Verified and approved.']
        ]);
        AuditLog::create([
            'claim_id' => $reimbursedClaim->claim_id, 'user_id' => $finance->user_id, 'action' => 'Claim Disbursed',
            'new_values' => ['status' => 'Reimbursed', 'payment_reference' => 'TRX-MYR-20260918-004', 'remarks' => 'Disbursed via corporate online banking.']
        ]);

        // 6.6 State: REJECTED (With Mandatory Reason)
        $rejectedClaim = Claim::create([
            'user_id' => $staff3->user_id, 'title' => 'Personal Entertainment Expense', 'claim_type' => 'Receipt',
            'status' => 'Rejected', 'merchant_name' => 'Golden Screen Cinemas',
            'receipt_invoice_no' => 'GSC-99124', 'amount' => 75.00,
            'transaction_date' => Carbon::now()->subDays(6)->format('Y-m-d'),
            'payment_method' => 'Card', 'category_id' => $mealsCat->id,
            'predicted_category' => 'Staff Operational Meals',
            'business_purpose' => 'Weekend movie outing',
            'receipt_image_path' => 'receipts/default.png',
            'rejection_reason' => 'Non-Claimable Personal Expense',
            'remarks' => 'Cinema and personal leisure outings are strictly excluded from reimbursable business expenses.',
            'risk_score' => 90, 'is_policy_violation' => true,
            'policy_violation_reason' => 'Cinema and personal leisure outings are strictly non-claimable per policy.',
            'created_at' => Carbon::now()->subDays(6),
            'updated_at' => Carbon::now()->subDays(5)
        ]);
        AuditLog::create([
            'claim_id' => $rejectedClaim->claim_id, 'user_id' => $manager->user_id, 'action' => 'CLAIM_Rejected',
            'old_values' => ['status' => 'Pre-Approved'],
            'new_values' => [
                'status' => 'Rejected',
                'signed_by' => 'Dr. Azman Shah',
                'rejection_reason' => 'Non-Claimable Personal Expense',
                'remarks' => 'Policy violation: Personal cinema outings are strictly excluded from reimbursable business expenses.'
            ],
            'created_at' => Carbon::now()->subDays(5),
            'updated_at' => Carbon::now()->subDays(5)
        ]);

        // 6.7 State: REVISION_REQUIRED (With Actionable Feedback)
        $revisionClaim = Claim::create([
            'user_id' => $staff1->user_id, 'title' => 'Client Dinner Meeting', 'claim_type' => 'Receipt',
            'status' => 'REVISION_REQUIRED', 'merchant_name' => 'Secret Recipe',
            'receipt_invoice_no' => 'SR-3301', 'amount' => 140.00,
            'transaction_date' => Carbon::now()->subDays(3)->format('Y-m-d'),
            'payment_method' => 'Card', 'category_id' => $mealsCat->id,
            'predicted_category' => 'Staff Operational Meals',
            'business_purpose' => 'Dinner discussion with client representative',
            'receipt_image_path' => 'receipts/default.png',
            'revision_reason' => 'Missing Supporting Document',
            'remarks' => 'Please attach the official client attendee list and tax receipt with breakdown before sign-off can proceed.',
            'risk_score' => 20, 'is_policy_violation' => false,
            'created_at' => Carbon::now()->subDays(3),
            'updated_at' => Carbon::now()->subDays(2)
        ]);
        AuditLog::create([
            'claim_id' => $revisionClaim->claim_id, 'user_id' => $manager->user_id, 'action' => 'CLAIM_REVISION_REQUIRED',
            'old_values' => ['status' => 'Pre-Approved'],
            'new_values' => [
                'status' => 'REVISION_REQUIRED',
                'signed_by' => 'Dr. Azman Shah',
                'revision_reason' => 'Missing Supporting Document',
                'remarks' => 'Please attach the official client attendee list and tax receipt with breakdown before sign-off can proceed.'
            ],
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDays(2)
        ]);

        // 7. Seed Active Learning Feedbacks
        \App\Models\AiLearningFeedback::create([
            'user_id' => $manager->user_id,
            'merchant_name' => 'BHPetrol',
            'predicted_category' => 'General',
            'corrected_category' => 'fuel-fleet-logistics',
            'raw_text_sample' => 'BHPetrol Station RM50.00 RON95',
            'extracted_keywords' => ['bhpetrol', 'ron95', 'station'],
            'is_applied' => true
        ]);

        \App\Models\AiLearningFeedback::create([
            'user_id' => $staff1->user_id,
            'merchant_name' => 'IKEA Restaurant',
            'predicted_category' => 'Site Tools & Hardware',
            'corrected_category' => 'staff-operational-meals',
            'raw_text_sample' => 'IKEA Restaurant Swedish Meatballs RM20.00',
            'extracted_keywords' => ['ikea', 'restaurant', 'meatballs', 'swedish'],
            'is_applied' => true
        ]);
    }
}
