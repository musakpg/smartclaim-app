<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;
use App\Models\Claim;
use App\Models\Vehicle;

class Phase1SecurityProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_serve_file_blocks_unauthenticated_users()
    {
        // Unauthenticated web request is redirected to login
        $response = $this->get('/files/receipts/nonexistent.jpg');
        $response->assertRedirect(route('login'));

        // Unauthenticated JSON request receives unauthorized status
        $jsonResponse = $this->getJson('/files/receipts/nonexistent.jpg');
        $jsonResponse->assertStatus(401);
    }

    public function test_serve_file_blocks_staff_from_accessing_other_users_private_documents()
    {
        Storage::fake('private');
        Storage::disk('private')->put('receipts/secret_receipt.jpg', 'fake-image-content');

        $staff1 = User::factory()->create(['role' => 'Staff']);
        $staff2 = User::factory()->create(['role' => 'Staff']);

        $claim = Claim::create([
            'user_id' => $staff1->user_id,
            'title' => 'Staff 1 Medical Claim',
            'amount' => 150.00,
            'merchant_name' => 'Clinic',
            'receipt_image_path' => 'receipts/secret_receipt.jpg',
            'status' => 'Pending',
        ]);

        // Staff 2 attempts to access Staff 1's private receipt (IDOR attempt)
        $response = $this->actingAs($staff2)->get('/files/receipts/secret_receipt.jpg');
        $response->assertStatus(403);
    }

    public function test_serve_file_allows_owner_to_access_own_documents()
    {
        Storage::fake('private');
        Storage::disk('private')->put('receipts/my_receipt.jpg', 'fake-image-content');

        $staff = User::factory()->create(['role' => 'Staff']);

        $claim = Claim::create([
            'user_id' => $staff->user_id,
            'title' => 'My Travel Expense',
            'amount' => 75.00,
            'merchant_name' => 'Fuel Station',
            'receipt_image_path' => 'receipts/my_receipt.jpg',
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($staff)->get('/files/receipts/my_receipt.jpg');
        $response->assertStatus(200);
    }

    public function test_serve_file_allows_manager_and_finance_to_access_private_documents()
    {
        Storage::fake('private');
        Storage::disk('private')->put('receipts/audit_target.jpg', 'fake-image-content');

        $staff = User::factory()->create(['role' => 'Staff']);
        $manager = User::factory()->create(['role' => 'Manager']);
        $finance = User::factory()->create(['role' => 'Finance']);

        $claim = Claim::create([
            'user_id' => $staff->user_id,
            'title' => 'Client Lunch',
            'amount' => 120.00,
            'merchant_name' => 'Restaurant',
            'receipt_image_path' => 'receipts/audit_target.jpg',
            'status' => 'Pending',
        ]);

        // Manager access
        $managerResponse = $this->actingAs($manager)->get('/files/receipts/audit_target.jpg');
        $managerResponse->assertStatus(200);

        // Finance auditor access
        $financeResponse = $this->actingAs($finance)->get('/files/receipts/audit_target.jpg');
        $financeResponse->assertStatus(200);
    }

    public function test_serve_file_returns_404_when_file_not_on_disk()
    {
        Storage::fake('private');

        $staff = User::factory()->create(['role' => 'Staff']);

        $claim = Claim::create([
            'user_id' => $staff->user_id,
            'title' => 'Missing File Claim',
            'amount' => 50.00,
            'merchant_name' => 'Store',
            'receipt_image_path' => 'receipts/deleted_file.jpg',
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($staff)->get('/files/receipts/deleted_file.jpg');
        $response->assertStatus(404);
    }

    public function test_vehicle_uploads_stored_on_private_disk()
    {
        Storage::fake('private');
        Storage::fake('public');

        $staff = User::factory()->create(['role' => 'Staff']);
        $grant = UploadedFile::fake()->image('grant_doc.jpg');
        $roadtax = UploadedFile::fake()->image('roadtax_doc.jpg');

        $response = $this->actingAs($staff)->post('/vehicles', [
            'plate_number' => 'VAA1234',
            'brand_model' => 'Proton Saga',
            'vehicle_type' => 'Car',
            'engine_capacity' => 1300,
            'roadtax_expiry' => now()->addDays(60)->format('Y-m-d'),
            'grant_document' => $grant,
            'roadtax_document' => $roadtax,
        ]);

        $response->assertStatus(302);

        $vehicle = Vehicle::where('plate_number', 'VAA1234')->first();
        $this->assertNotNull($vehicle);

        // Verify stored on private disk, NOT public disk
        Storage::disk('private')->assertExists($vehicle->grant_document_path);
        Storage::disk('private')->assertExists($vehicle->roadtax_document_path);
        Storage::disk('public')->assertMissing($vehicle->grant_document_path);
        Storage::disk('public')->assertMissing($vehicle->roadtax_document_path);
    }

    public function test_logout_endpoint_requires_post_method()
    {
        $staff = User::factory()->create(['role' => 'Staff']);

        // GET /logout should be rejected (405 Method Not Allowed)
        $getResponse = $this->actingAs($staff)->get('/logout');
        $getResponse->assertStatus(405);

        // POST /logout should terminate session and redirect to login
        $postResponse = $this->actingAs($staff)->post('/logout');
        $postResponse->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_test_push_diagnostic_route_protected()
    {
        $staff = User::factory()->create(['role' => 'Staff']);
        $manager = User::factory()->create(['role' => 'Manager']);

        // Unauthenticated access blocked
        $guestResponse = $this->get('/test-push');
        $guestResponse->assertRedirect(route('login'));

        // Staff access blocked by role middleware (403)
        $staffResponse = $this->actingAs($staff)->get('/test-push');
        $staffResponse->assertStatus(403);

        // Manager access permitted
        $managerResponse = $this->actingAs($manager)->get('/test-push');
        $managerResponse->assertStatus(200);
    }
}
