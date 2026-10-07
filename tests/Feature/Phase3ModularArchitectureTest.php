<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Claim;
use App\Models\Category;
use App\Services\ReceiptOcrService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class Phase3ModularArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Category::create([
            'name' => 'Meals & Entertainment',
            'code' => 'MEAL',
            'keywords' => 'food,lunch,dinner,restaurant,cafe,kfc,mcdonald',
            'is_active' => true,
        ]);
    }

    public function test_receipt_ocr_service_extraction_and_heuristics()
    {
        $ocrService = app(ReceiptOcrService::class);
        $this->assertInstanceOf(ReceiptOcrService::class, $ocrService);

        // Test date parser heuristic
        $parsedDate = $ocrService->extractTransactionDate("Tarikh: 15/08/2026 Resit Rasmi");
        $this->assertEquals('2026-08-15', $parsedDate);

        // Test amount parser heuristic
        $amountData = $ocrService->extractAmount("SUBTOTAL RM 45.00\nTOTAL RM 52.50\nCASH RM 60.00");
        $this->assertEquals('52.50', $amountData['amount']);

        // Test merchant detector heuristic
        $lines = ["WELCOME TO PETRONAS BUKIT JALIL", "KUALA LUMPUR"];
        $parsedMerchant = $ocrService->extractMerchant($lines);
        $this->assertStringContainsString('Petronas', $parsedMerchant);
    }

    public function test_staff_claim_submission_controller_routes()
    {
        $staff = User::factory()->create(['role' => 'Staff']);

        $response = $this->actingAs($staff)->get(route('claims.create'));
        $response->assertStatus(200);

        $response = $this->actingAs($staff)->get(route('claims.history'));
        $response->assertStatus(200);

        // Duplicate check API
        $response = $this->actingAs($staff)->postJson(route('claims.checkDuplicate'), [
            'amount' => 50.00,
            'date' => '2026-08-15',
            'merchant' => 'Petronas',
            'invoice_no' => 'INV-9999',
        ]);
        $response->assertStatus(200);
        $response->assertJson(['duplicate' => false]);
    }

    public function test_manager_claim_verification_controller_routes()
    {
        $manager = User::factory()->create(['role' => 'Manager']);

        $response = $this->actingAs($manager)->get(route('manager.verification'));
        $response->assertStatus(200);

        $response = $this->actingAs($manager)->get(route('manager.reports.sla_analytics'));
        $response->assertStatus(200);
    }

    public function test_finance_auditing_and_disbursement_controller_routes()
    {
        $finance = User::factory()->create(['role' => 'Finance']);

        $response = $this->actingAs($finance)->get(route('finance.auditing'));
        $response->assertStatus(200);

        $response = $this->actingAs($finance)->get(route('finance.disbursement'));
        $response->assertStatus(200);
    }

    public function test_unified_pdf_download_route_uses_export_controller()
    {
        $staff = User::factory()->create(['role' => 'Staff']);

        $claim = Claim::create([
            'user_id' => $staff->user_id,
            'title' => 'Dinner at KFC Subang',
            'claim_type' => 'Receipt',
            'predicted_category' => 'Meals & Entertainment',
            'merchant_name' => 'KFC Subang',
            'amount' => 45.00,
            'status' => 'Approved',
            'transaction_date' => now()->toDateString(),
        ]);

        // Test both unified routes: download-pdf and voucher-pdf
        $response1 = $this->actingAs($staff)->get(route('claims.voucher_pdf', ['id' => $claim->claim_id]));
        $response1->assertStatus(200);

        $response2 = $this->actingAs($staff)->get(route('claims.download_pdf', ['id' => $claim->claim_id]));
        $response2->assertStatus(200);
    }
}
