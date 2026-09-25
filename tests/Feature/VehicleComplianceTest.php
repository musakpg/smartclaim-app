<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Claim;

class VehicleComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_vehicle_registration()
    {
        $staff = User::factory()->create(['role' => 'Staff']);
        
        $grant = UploadedFile::fake()->image('geran.jpg');
        $roadtax = UploadedFile::fake()->image('roadtax.jpg');

        $response = $this->actingAs($staff)->post('/vehicles', [
            'plate_number' => 'V1234',
            'brand_model' => 'Proton Saga',
            'vehicle_type' => 'Car',
            'engine_capacity' => 1300,
            'roadtax_expiry' => now()->addDays(60)->format('Y-m-d'),
            'grant_document' => $grant,
            'roadtax_document' => $roadtax
        ]);

        $response->assertStatus(302);
        
        $vehicle = Vehicle::where('plate_number', 'V1234')->first();
        $this->assertNotNull($vehicle);
        $this->assertEquals('personal', $vehicle->ownership_type);
        $this->assertEquals('Pending', $vehicle->approval_status ?? 'Pending');
    }

    public function test_manager_vehicle_approval()
    {
        $manager = User::factory()->create(['role' => 'Manager']);
        
        $staff = User::factory()->create(['role' => 'Staff']);
        $vehicle = Vehicle::create([
            'user_id' => $staff->user_id,
            'plate_number' => 'V5678',
            'brand_model' => 'Honda',
            'vehicle_type' => 'Car',
            'engine_capacity' => 1500,
            'roadtax_expiry' => now()->addDays(60),
            'ownership_type' => 'personal',
            'approval_status' => 'Pending'
        ]);

        $response = $this->actingAs($manager)->post("/manager/vehicles/{$vehicle->vehicle_id}/approve");
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->assertEquals('Approved', $vehicle->fresh()->approval_status);
    }

    public function test_expired_roadtax_rejected_for_mileage()
    {
        $staff = User::factory()->create(['role' => 'Staff']);
        
        $vehicle = Vehicle::create([
            'user_id' => $staff->user_id,
            'plate_number' => 'EXP999',
            'brand_model' => 'Old Car',
            'vehicle_type' => 'Car',
            'engine_capacity' => 1000,
            'roadtax_expiry' => now()->subDays(1), // Expired
            'ownership_type' => 'personal',
            'status' => 'Active',
            'approval_status' => 'Approved'
        ]);

        $document = UploadedFile::fake()->image('map.jpg');
        
        $response = $this->actingAs($staff)->post('/claims/store', [
            'claim_type' => 'Mileage',
            'title' => 'Expired Claim',
            'vehicle_id' => $vehicle->vehicle_id,
            'mileage_km' => 10,
            'start_location' => 'A',
            'destination_location' => 'B',
            'transaction_date' => now()->format('Y-m-d'),
            'mileage_document' => $document,
            'business_purpose' => 'Test'
        ]);

        $response->assertSessionHasErrors('vehicle_id');
        $this->assertStringContainsString('expired', session('errors')->first('vehicle_id'));
    }
}
