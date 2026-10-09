<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Claim;
use App\Services\ReceiptOcrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

class DemoModeTest extends TestCase
{
    use RefreshDatabase;

    protected User $demoStaff;
    protected User $demoFinance;
    protected User $demoManager;
    protected User $realStaff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->demoStaff = User::updateOrCreate(
            ['email' => 'demo-staff@smartclaim.com'],
            [
                'name' => 'Demo Staff',
                'password' => Hash::make('demo1234'),
                'role' => 'Staff',
                'is_active' => true,
                'is_demo' => true,
            ]
        );

        $this->demoFinance = User::updateOrCreate(
            ['email' => 'demo-finance@smartclaim.com'],
            [
                'name' => 'Demo Finance',
                'password' => Hash::make('demo1234'),
                'role' => 'Finance',
                'is_active' => true,
                'is_demo' => true,
            ]
        );

        $this->demoManager = User::updateOrCreate(
            ['email' => 'demo-manager@smartclaim.com'],
            [
                'name' => 'Demo Manager',
                'password' => Hash::make('demo1234'),
                'role' => 'Manager',
                'is_active' => true,
                'is_demo' => true,
            ]
        );

        $this->realStaff = User::updateOrCreate(
            ['email' => 'real-staff@smartclaim.com'],
            [
                'name' => 'Real Staff',
                'password' => Hash::make('password123'),
                'role' => 'Staff',
                'is_active' => true,
                'is_demo' => false,
            ]
        );
    }

    public function test_demo_users_can_authenticate_with_default_password()
    {
        $response = $this->post(route('login.process'), [
            'email' => 'demo-staff@smartclaim.com',
            'password' => 'demo1234',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->demoStaff);
    }

    public function test_mutating_actions_are_blocked_for_demo_users()
    {
        $this->actingAs($this->demoStaff);

        $response = $this->post(route('claims.store'), [
            'title' => 'Test Mutating Claim',
            'amount' => 150.00,
            'claim_type' => 'Receipt',
        ]);

        $response->assertSessionHas('warning', 'Demo Mode: Action is restricted to Read-Only to preserve showcase audit data.');
    }

    public function test_mutating_ajax_requests_receive_403_for_demo_users()
    {
        $this->actingAs($this->demoManager);

        $claim = Claim::create([
            'user_id' => $this->demoStaff->user_id,
            'is_demo' => true,
            'claim_type' => 'Receipt',
            'title' => 'Ajax Action Test',
            'amount' => 50.00,
            'status' => 'Pending',
        ]);

        $response = $this->postJson(route('manager.claims.status', ['id' => $claim->claim_id]), [
            'status' => 'Approved',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Demo Mode: Action is restricted to Read-Only to preserve showcase audit data.',
        ]);
    }

    public function test_demo_and_production_claims_are_strictly_isolated()
    {
        $demoClaim = Claim::create([
            'user_id' => $this->demoStaff->user_id,
            'is_demo' => true,
            'claim_type' => 'Receipt',
            'title' => 'Demo Isolated Claim',
            'amount' => 99.00,
            'status' => 'Pending',
        ]);

        $prodClaim = Claim::create([
            'user_id' => $this->realStaff->user_id,
            'is_demo' => false,
            'claim_type' => 'Receipt',
            'title' => 'Production Live Claim',
            'amount' => 200.00,
            'status' => 'Pending',
        ]);

        // Demo Finance sees Demo Claim and NOT Production Claim
        $this->actingAs($this->demoFinance);
        $responseDemo = $this->get(route('finance.auditing'));
        $responseDemo->assertSee('Demo Isolated Claim');
        $responseDemo->assertDontSee('Production Live Claim');

        // Real Finance sees Production Claim and NOT Demo Claim
        $realFinance = User::where('role', 'Finance')->where('is_demo', false)->first();
        if ($realFinance) {
            $this->actingAs($realFinance);
            $responseProd = $this->get(route('finance.auditing'));
            $responseProd->assertSee('Production Live Claim');
            $responseProd->assertDontSee('Demo Isolated Claim');
        }
    }

    public function test_receipt_ocr_service_bypasses_api_for_demo_users()
    {
        $this->actingAs($this->demoStaff);

        $file = UploadedFile::fake()->image('test_receipt.jpg');
        $service = new ReceiptOcrService();
        $result = $service->scanReceipt($file);

        $this->assertTrue($result['success']);
        $this->assertEquals('Kedai Runcit Mulia Sejati Baru', $result['merchant_name']);
        $this->assertEquals(100.20, $result['amount']);
    }

    public function test_demo_staff_has_verified_active_vehicle_for_mileage_claims()
    {
        $this->seed(\Database\Seeders\DemoSeeder::class);

        $vehicle = \App\Models\Vehicle::where('plate_number', 'VCE 2024')->first();
        $this->assertNotNull($vehicle);
        $this->assertEquals('Proton X50 1.5 TGDi', $vehicle->brand_model);
        $this->assertEquals('Approved', $vehicle->approval_status);
        $this->assertEquals('Active', $vehicle->status);
        $this->assertEquals('personal', $vehicle->ownership_type);

        $this->actingAs($this->demoStaff);
        $response = $this->get(route('claims.create'));
        $response->assertStatus(200);
        $response->assertSee('VCE 2024');
        $response->assertSee('Proton X50 1.5 TGDi');
    }

    public function test_demo_banner_and_simulated_ocr_notice_rendered_for_demo_users()
    {
        $this->actingAs($this->demoStaff);

        // Claims create view shows both the global demo banner and the simulated OCR notice
        $response = $this->get(route('claims.create'));
        $response->assertStatus(200);
        $response->assertSee('Demo Mode (Read-Only) — Viewing isolated sample environment.');
        $response->assertSee('Demo Mode Notice — Simulated OCR Active:');

        // Manager portal also renders the global demo banner
        $this->actingAs($this->demoManager);
        $managerResponse = $this->get(route('manager.dashboard'));
        $managerResponse->assertStatus(200);
        $managerResponse->assertSee('Demo Mode (Read-Only) — Viewing isolated sample environment.');
    }
}

