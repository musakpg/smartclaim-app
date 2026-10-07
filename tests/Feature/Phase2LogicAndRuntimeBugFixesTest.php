<?php

namespace Tests\Feature;

use App\Models\Claim;
use App\Models\InAppNotification;
use App\Models\MileageRate;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase2LogicAndRuntimeBugFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_mileage_claim_resubmission_succeeds_and_updates_calculated_amount()
    {
        Storage::fake('private');

        $staff = User::factory()->create([
            'role' => 'Staff',
            'is_active' => true,
        ]);

        $vehicle = Vehicle::create([
            'user_id' => $staff->user_id,
            'plate_number' => 'WXY8888',
            'brand_model' => 'Toyota Vios',
            'vehicle_type' => 'Car',
            'engine_capacity' => 1500,
            'roadtax_expiry' => now()->addDays(90),
            'ownership_type' => 'personal',
            'status' => 'Active',
            'approval_status' => 'Approved',
        ]);

        // Insert mileage rate with the correct column name 'rate'
        MileageRate::create([
            'vehicle_type' => 'Car',
            'min_km' => 0,
            'max_km' => 500,
            'rate' => 0.85,
        ]);

        $claim = Claim::create([
            'user_id' => $staff->user_id,
            'vehicle_id' => $vehicle->vehicle_id,
            'claim_type' => 'Mileage',
            'title' => 'Client Onsite Meeting',
            'merchant_name' => 'Aero Art Mileage (Car)',
            'amount' => 42.50,
            'mileage_km' => 50.0,
            'start_location' => 'HQ',
            'destination_location' => 'Client Site',
            'vehicle_plate_number' => $vehicle->plate_number,
            'vehicle_type' => 'Car',
            'payment_method' => 'Allowance',
            'status' => 'REVISION_REQUIRED',
            'revision_reason' => 'Please provide updated route odometer proof.',
            'transaction_date' => now()->format('Y-m-d'),
            'business_purpose' => 'Initial pitch',
        ]);

        $newDocument = UploadedFile::fake()->image('odometer_updated.jpg');

        $response = $this->actingAs($staff)->put("/claims/{$claim->claim_id}/resubmit", [
            'title' => 'Client Onsite Meeting (Updated Route)',
            'vehicle_id' => $vehicle->vehicle_id,
            'mileage_km' => 100.0,
            'start_location' => 'HQ',
            'destination_location' => 'Client Site Cyberjaya',
            'transaction_date' => now()->format('Y-m-d'),
            'mileage_document' => $newDocument,
            'business_purpose' => 'Initial pitch and contract signing',
            'resubmission_notes' => 'Attached updated odometer and map route.',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('claims.history'));

        $freshClaim = $claim->fresh();
        $this->assertEquals('Pending', $freshClaim->status);
        $this->assertEquals(85.00, (float) $freshClaim->amount); // 100 km * 0.85 rate
        $this->assertEquals(100.0, (float) $freshClaim->mileage_km);
        $this->assertNotNull($freshClaim->receipt_image_path);
        Storage::disk('private')->assertExists($freshClaim->receipt_image_path);
    }

    public function test_claim_status_update_notifications_display_genuine_claim_amount_not_zero()
    {
        $staff = User::factory()->create([
            'role' => 'Staff',
            'name' => 'John Doe',
        ]);

        $manager = User::factory()->create([
            'role' => 'Manager',
            'name' => 'Manager Alice',
        ]);

        $claim = Claim::create([
            'user_id' => $staff->user_id,
            'claim_type' => 'Receipt',
            'title' => 'Project Dinner',
            'merchant_name' => 'Sushi Tei',
            'amount' => 245.50,
            'payment_method' => 'Credit Card',
            'status' => 'Pending',
            'transaction_date' => now()->format('Y-m-d'),
            'business_purpose' => 'Client dinner meeting',
        ]);

        // Manager approves the claim
        $response = $this->actingAs($manager)->post("/manager/claims/{$claim->claim_id}/status", [
            'status' => 'Approved',
            'remarks' => 'Approved as per company entertainment policy.',
        ]);

        $response->assertStatus(302);

        // Verify staff received notification with the genuine claim total
        $notification = InAppNotification::where('user_id', $staff->user_id)
            ->where('title', 'Claim Status: Approved')
            ->latest()
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('RM 245.50', $notification->message);
        $this->assertStringNotContainsString('RM 0.00', $notification->message);
    }

    public function test_claim_status_update_pre_approved_notifies_with_genuine_claim_amount()
    {
        $staff = User::factory()->create([
            'role' => 'Staff',
            'name' => 'Jane Smith',
        ]);

        $finance = User::factory()->create([
            'role' => 'Finance',
            'name' => 'Auditor Bob',
        ]);

        $manager = User::factory()->create([
            'role' => 'Manager',
            'name' => 'Manager Charlie',
        ]);

        $claim = Claim::create([
            'user_id' => $staff->user_id,
            'claim_type' => 'Receipt',
            'title' => 'Hardware Purchase',
            'merchant_name' => 'All IT Hypermarket',
            'amount' => 450.00,
            'payment_method' => 'Company Card',
            'status' => 'Pending',
            'transaction_date' => now()->format('Y-m-d'),
            'business_purpose' => 'Monitor for design workstation',
        ]);

        // Finance audits and marks claim as Pre-Approved via finance route
        $response = $this->actingAs($finance)->post("/finance/claims/{$claim->claim_id}/status", [
            'status' => 'Pre-Approved',
            'remarks' => 'Audited receipts conform to standard IT budget.',
        ]);

        $response->assertStatus(302);

        // Verify manager received escalation notification with the genuine claim total
        $managerNotification = InAppNotification::where('user_id', $manager->user_id)
            ->where('title', "Claim Escalated for Approval: #CLM-{$claim->claim_id}")
            ->latest()
            ->first();

        $this->assertNotNull($managerNotification);
        $this->assertStringContainsString('RM 450.00', $managerNotification->message);
        $this->assertStringNotContainsString('RM 0.00', $managerNotification->message);
    }

    public function test_unauthenticated_requests_do_not_fallback_to_user_id_1()
    {
        // Notification endpoints should return 401 when unauthenticated
        $this->getJson('/api/notifications/latest')->assertStatus(401);
        $this->postJson('/api/notifications/mark-read')->assertStatus(401);

        // Protected staff endpoints should redirect to login or return 401
        $claimCreateResponse = $this->get('/claims/create');
        $this->assertTrue(in_array($claimCreateResponse->status(), [302, 401]));

        $vehicleIndexResponse = $this->get('/vehicles');
        $this->assertTrue(in_array($vehicleIndexResponse->status(), [302, 401]));

        $cashAdvanceResponse = $this->post('/cash-advances', [
            'title' => 'Unauthorized Advance',
            'requested_amount' => 500,
            'required_date' => now()->addDays(5)->format('Y-m-d'),
            'purpose' => 'Testing fallback security',
        ]);
        $this->assertTrue(in_array($cashAdvanceResponse->status(), [302, 401]));
    }

    public function test_services_config_keys_are_configured()
    {
        $this->assertArrayHasKey('google_vision', config('services'));
        $this->assertArrayHasKey('api_key', config('services.google_vision'));

        $this->assertArrayHasKey('google_maps', config('services'));
        $this->assertArrayHasKey('api_key', config('services.google_maps'));
    }
}
