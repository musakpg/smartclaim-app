<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Claim;
use App\Models\Category;
use App\Models\CashAdvance;
use App\Services\ReceiptOcrService;

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

    public function test_staff_controllers_routes()
    {
        $staff = User::factory()->create(['role' => 'Staff']);

        // StaffDashboardController
        $response = $this->actingAs($staff)->get(route('dashboard'));
        $response->assertStatus(200);

        // ClaimSubmissionController
        $response = $this->actingAs($staff)->get(route('claims.create'));
        $response->assertStatus(200);

        $response = $this->actingAs($staff)->get(route('claims.history'));
        $response->assertStatus(200);

        $response = $this->actingAs($staff)->get(route('reimbursement.index'));
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

        // Staff\VehicleController
        $response = $this->actingAs($staff)->get(route('vehicles.index'));
        $response->assertStatus(200);

        // Staff\CashAdvanceRequestController
        $response = $this->actingAs($staff)->get(route('advances.index'));
        $response->assertStatus(200);

        // UserProfileController & PolicyConfigController
        $response = $this->actingAs($staff)->get(route('profile.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($staff)->get(route('policy.index'));
        $response->assertStatus(200);
    }

    public function test_manager_controllers_routes()
    {
        $manager = User::factory()->create(['role' => 'Manager']);

        // ManagerDashboardController
        $response = $this->actingAs($manager)->get(route('manager.dashboard'));
        $response->assertStatus(200);

        $response = $this->actingAs($manager)->get(route('manager.reports'));
        $response->assertStatus(200);

        $response = $this->actingAs($manager)->get(route('manager.profile'));
        $response->assertStatus(200);

        // ClaimVerificationController
        $response = $this->actingAs($manager)->get(route('manager.verification'));
        $response->assertStatus(200);

        $response = $this->actingAs($manager)->get(route('manager.reports.sla_analytics'));
        $response->assertStatus(200);

        // CompanyFleetController
        $response = $this->actingAs($manager)->get(route('manager.vehicles'));
        $response->assertStatus(200);

        // UserManagementController
        $response = $this->actingAs($manager)->get(route('manager.user_management'));
        $response->assertStatus(200);

        // AuditLogController
        $response = $this->actingAs($manager)->get(route('manager.audit_logs'));
        $response->assertStatus(200);

        // PriceIntelligenceController
        $response = $this->actingAs($manager)->get(route('manager.price_intelligence'));
        $response->assertStatus(200);

        // CashAdvanceApprovalController
        $response = $this->actingAs($manager)->get(route('manager.advances.index'));
        $response->assertStatus(200);
    }

    public function test_finance_controllers_routes()
    {
        $finance = User::factory()->create(['role' => 'Finance']);

        // FinanceDashboardController
        $response = $this->actingAs($finance)->get(route('finance.dashboard'));
        $response->assertStatus(200);

        $response = $this->actingAs($finance)->get(route('finance.reports'));
        $response->assertStatus(200);

        $response = $this->actingAs($finance)->get(route('finance.profile'));
        $response->assertStatus(200);

        $response = $this->actingAs($finance)->get(route('finance.staff_directory'));
        $response->assertStatus(200);

        // ClaimAuditingController
        $response = $this->actingAs($finance)->get(route('finance.auditing'));
        $response->assertStatus(200);

        // DisbursementController
        $response = $this->actingAs($finance)->get(route('finance.disbursement'));
        $response->assertStatus(200);

        // CashAdvanceReconciliationController
        $response = $this->actingAs($finance)->get(route('finance.cash-advances.index'));
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
