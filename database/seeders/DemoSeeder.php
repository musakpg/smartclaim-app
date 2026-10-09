<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Claim;
use App\Models\ClaimItem;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds for isolated Demo Mode.
     */
    public function run(): void
    {
        // 1. Prepare Physical Sample Receipt Assets
        $this->ensureReceiptAssetsExist();

        // 2. Create or Update Dedicated Demo Users
        $staff = User::updateOrCreate(
            ['email' => 'demo-staff@smartclaim.com'],
            [
                'name' => 'Demo Staff',
                'password' => Hash::make('demo1234'),
                'role' => 'Staff',
                'is_active' => true,
                'is_demo' => true,
                'bank_name' => 'Maybank',
                'bank_account_no' => '114012345678',
                'bank_account_holder' => 'Demo Staff',
            ]
        );

        $finance = User::updateOrCreate(
            ['email' => 'demo-finance@smartclaim.com'],
            [
                'name' => 'Demo Finance',
                'password' => Hash::make('demo1234'),
                'role' => 'Finance',
                'is_active' => true,
                'is_demo' => true,
                'bank_name' => 'CIMB Bank',
                'bank_account_no' => '880192837465',
                'bank_account_holder' => 'Demo Finance',
            ]
        );

        $manager = User::updateOrCreate(
            ['email' => 'demo-manager@smartclaim.com'],
            [
                'name' => 'Demo Manager',
                'password' => Hash::make('demo1234'),
                'role' => 'Manager',
                'is_active' => true,
                'is_demo' => true,
                'bank_name' => 'Public Bank',
                'bank_account_no' => '339182746501',
                'bank_account_holder' => 'Demo Manager',
            ]
        );

        // Clean existing demo claims to ensure idempotent seeding
        $existingDemoClaimIds = Claim::where('is_demo', true)->pluck('claim_id');
        ClaimItem::whereIn('claim_id', $existingDemoClaimIds)->delete();
        Claim::where('is_demo', true)->delete();

        // Retrieve Category mappings
        $pantryCategory = Category::where('code', 'office-pantry-amenities')->first()
            ?? Category::find(2);
        $fuelCategory = Category::where('code', 'fuel-fleet-logistics')->first()
            ?? Category::find(4);
        $transportCategory = Category::where('code', 'transportation-toll')->first()
            ?? Category::find(7);

        // -------------------------------------------------------------
        // CLAIM 1: Pending & Flagged - Amount Inflation (resit 1.jpeg)
        // -------------------------------------------------------------
        $resit1Path = public_path('storage/receipts/resit_1.jpeg');
        $hash1 = file_exists($resit1Path)
            ? hash_file('sha256', $resit1Path)
            : '50e9068305e6b2db2f9b7e17b94480440afa53f811768e2a20e6a9bb125c635b';

        $claim1 = Claim::create([
            'user_id' => $staff->user_id,
            'is_demo' => true,
            'claim_type' => 'Receipt',
            'title' => 'Office Pantry & Essential Supplies',
            'merchant_name' => 'Kedai Runcit Mulia Sejati Baru',
            'location_address' => 'Lot 6061 Jalan Imam, Kg Sg Ramal Dalam, 43000 Kajang, Selangor',
            'receipt_invoice_no' => '#207905',
            'transaction_date' => '2026-05-15',
            'receipt_image_path' => 'receipts/resit_1.jpeg',
            'receipt_image_hash' => $hash1,
            'category_id' => $pantryCategory?->id ?? 2,
            'predicted_category' => $pantryCategory?->name ?? 'Office Pantry & Amenities (Groceries, Supplies)',
            'amount' => 130.26, // +30% inflation above visual OCR 100.20
            'payment_method' => 'Cash',
            'status' => 'Pending',
            'risk_score' => 30,
            'fraud_flags' => [
                [
                    'flag_type' => 'AMOUNT_INFLATION',
                    'severity' => 'warning',
                    'title' => 'Amount Inflation',
                    'description' => 'User submitted RM 130.26 but OCR read RM 100.20 (+RM 30.06).'
                ]
            ],
            'business_purpose' => 'Urgent office pantry restocking and essential cleaning goods for Kajang branch team.',
            'extracted_raw_text' => "KEDAI RUNCIT MULIA SEJATI BARU\nLOT. 6061 JALAN IMAM,\nKG SG RAMAL DALAM,\n43000 KAJANG, SELANGOR.\nTERIMA KASIH\n#207905 15/05/2026 19:21\n1x 61.70 AYAM RM61.70\n3x 3.00 BRG DAPUR RM9.00\n1x 5.30 BRG DAPUR RM5.30\n1x 4.90 BRG DAPUR RM4.90\n1x 8.80 BRG DAPUR RM8.80\n3x 3.50 BRG DAPUR RM10.50\nCASH RM100.20",
            'estimated_payout_date' => Carbon::now()->addWeekdays(5)->toDateString(),
        ]);

        $items1 = [
            ['name' => 'Ayam Segar', 'qty' => 1, 'price' => 61.70, 'subtotal' => 61.70],
            ['name' => 'Barang Dapur A', 'qty' => 3, 'price' => 3.00, 'subtotal' => 9.00],
            ['name' => 'Barang Dapur B', 'qty' => 1, 'price' => 5.30, 'subtotal' => 5.30],
            ['name' => 'Barang Dapur C', 'qty' => 1, 'price' => 4.90, 'subtotal' => 4.90],
            ['name' => 'Barang Dapur D', 'qty' => 1, 'price' => 8.80, 'subtotal' => 8.80],
            ['name' => 'Barang Dapur E', 'qty' => 3, 'price' => 3.50, 'subtotal' => 10.50],
        ];
        foreach ($items1 as $item) {
            ClaimItem::create([
                'claim_id' => $claim1->claim_id,
                'item_name' => $item['name'],
                'quantity' => $item['qty'],
                'unit_price' => $item['price'],
                'subtotal' => $item['subtotal'],
            ]);
        }

        // -------------------------------------------------------------
        // CLAIM 2: Pre-Approved - Clean (resit 2.jpeg)
        // -------------------------------------------------------------
        $resit2Path = public_path('storage/receipts/resit_2.jpeg');
        $hash2 = file_exists($resit2Path)
            ? hash_file('sha256', $resit2Path)
            : '9367d3a9f5d9a3bf68034c4aef489ffcf4ecb5ea4eaa2bac7b5962205c93eca2';

        $claim2 = Claim::create([
            'user_id' => $staff->user_id,
            'is_demo' => true,
            'claim_type' => 'Receipt',
            'title' => 'Fuel Allowance - Site Client Consultation',
            'merchant_name' => 'Petronas (Wanimas Enterprise)',
            'location_address' => 'PS Sek. 16 Bdr. Baru Bangi, 43650 Bangi, Selangor',
            'receipt_invoice_no' => '5fd17d',
            'transaction_date' => '2026-05-14',
            'receipt_image_path' => 'receipts/resit_2.jpeg',
            'receipt_image_hash' => $hash2,
            'category_id' => $fuelCategory?->id ?? 4,
            'predicted_category' => $fuelCategory?->name ?? 'Fuel & Fleet Logistics (Corporate Fleet / Petrol)',
            'amount' => 50.00, // Exact matching OCR amount
            'payment_method' => 'Cash',
            'status' => 'Pre-Approved',
            'risk_score' => 0,
            'fraud_flags' => [],
            'business_purpose' => 'Fuel top-up for corporate pool vehicle traveling to client briefing in Bangi.',
            'extracted_raw_text' => "PETRONAS\nWANIMAS ENTERPRISE\nPS Sek.16 Bdr.Baru Bangi\nDATE : 14 MAY 2026 14:51:33\nINV NO. 5fd17d\nPrimax 95 RM 50.00\nGrand Total 50.00\nAmount Paid (Cash) 50.00",
            'estimated_payout_date' => Carbon::now()->addWeekdays(3)->toDateString(),
        ]);

        ClaimItem::create([
            'claim_id' => $claim2->claim_id,
            'item_name' => 'Primax 95 Petrol (Subsidised)',
            'quantity' => 1,
            'unit_price' => 50.00,
            'subtotal' => 50.00,
        ]);

        // -------------------------------------------------------------
        // CLAIM 3: Approved / Reimbursed - Historical Audit Entry (resit 8.jpeg)
        // -------------------------------------------------------------
        $resit8Path = public_path('storage/receipts/resit_8.jpeg');
        $hash8 = file_exists($resit8Path)
            ? hash_file('sha256', $resit8Path)
            : '83febc3d48c553a674a2b4bb8c9e41e97b31693da48927b54410d149565c78ea';

        $claim3 = Claim::create([
            'user_id' => $staff->user_id,
            'is_demo' => true,
            'claim_type' => 'Receipt',
            'title' => 'Touch n Go Card Reload - Highway Transit',
            'merchant_name' => "Besjaya Enterprise (Touch 'n Go)",
            'location_address' => 'KM 0.7, Lebuhraya Sg.Besi, 43300 Seri Kembangan, Selangor',
            'receipt_invoice_no' => 'NU301010860678',
            'transaction_date' => '2026-06-10',
            'receipt_image_path' => 'receipts/resit_8.jpeg',
            'receipt_image_hash' => $hash8,
            'category_id' => $transportCategory?->id ?? 7,
            'predicted_category' => $transportCategory?->name ?? 'Transportation & Toll',
            'amount' => 20.00,
            'payment_method' => 'Cash',
            'status' => 'Reimbursed',
            'risk_score' => 0,
            'fraud_flags' => [],
            'reimbursed_by' => $finance->user_id,
            'reimbursed_at' => Carbon::parse('2026-06-11 11:30:00'),
            'payment_reference' => 'DEMO-EFT-99201',
            'business_purpose' => 'Highway toll balance reload for inter-branch hardware transfer.',
            'extracted_raw_text' => "Besjaya Enterprise\nKM 0.7, Lebuhraya Sg.Besi, Seri Kembangan\n10/06/2026 18:00:13\nTouch 'n Go Reload 20\nRM20.00\nTXN Ref: NU301010860678\nThank You",
            'estimated_payout_date' => '2026-06-11',
        ]);

        ClaimItem::create([
            'claim_id' => $claim3->claim_id,
            'item_name' => "Touch 'n Go Electronic Reload",
            'quantity' => 1,
            'unit_price' => 20.00,
            'subtotal' => 20.00,
        ]);
    }

    /**
     * Ensure physical receipt images exist in public and storage destinations.
     */
    protected function ensureReceiptAssetsExist(): void
    {
        $fileMappings = [
            'resit 1.jpeg' => 'resit_1.jpeg',
            'resit 2.jpeg' => 'resit_2.jpeg',
            'resit 8.jpeg' => 'resit_8.jpeg',
        ];

        $targetDirs = [
            public_path('storage/receipts'),
            storage_path('app/public/receipts'),
            storage_path('app/private/receipts'),
        ];

        foreach ($targetDirs as $dir) {
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0777, true, true);
            }
        }

        foreach ($fileMappings as $sourceFile => $targetFile) {
            // Check candidate source locations
            $sourceCandidates = [
                public_path("storage/receipts/{$sourceFile}"),
                storage_path("app/public/receipts/{$sourceFile}"),
                public_path("storage/receipts/{$targetFile}"),
                storage_path("app/public/receipts/{$targetFile}"),
            ];

            $validSource = null;
            foreach ($sourceCandidates as $candidate) {
                if (File::exists($candidate)) {
                    $validSource = $candidate;
                    break;
                }
            }

            if ($validSource) {
                foreach ($targetDirs as $dir) {
                    $targetPath = "{$dir}/{$targetFile}";
                    $targetPathJpg = "{$dir}/" . str_replace('.jpeg', '.jpg', $targetFile);
                    File::copy($validSource, $targetPath);
                    File::copy($validSource, $targetPathJpg);
                }
            }
        }
    }
}
