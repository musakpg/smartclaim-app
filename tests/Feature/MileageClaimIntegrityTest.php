<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Claim;

class MileageClaimIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_mileage_claim_integrity()
    {
        $staff = User::factory()->create(['role' => 'Staff', 'is_active' => true]);
        
        $vehicle = Vehicle::create([
            'user_id' => $staff->user_id,
            'plate_number' => 'ABC1234',
            'brand_model' => 'Honda Civic',
            'vehicle_type' => 'Car',
            'engine_capacity' => 1500,
            'roadtax_expiry' => now()->addDays(30),
            'ownership_type' => 'personal',
            'status' => 'Active',
            'approval_status' => 'Approved'
        ]);

        $document = UploadedFile::fake()->image('map.jpg');
        
        $response = $this->actingAs($staff)->post('/claims/store', [
            'claim_type' => 'Mileage',
            'title' => 'Client Visit',
            'vehicle_id' => $vehicle->vehicle_id,
            'mileage_km' => 100,
            'start_location' => 'Office',
            'destination_location' => 'Client Site',
            'transaction_date' => now()->format('Y-m-d'),
            'mileage_document' => $document,
            'business_purpose' => 'Sales Pitch'
        ]);

        $response->assertStatus(302);
        
        $claim = Claim::first();
        $this->assertNotNull($claim);
        $this->assertEquals(60.00, $claim->amount); // 100km * 0.60 default for Car
        $this->assertTrue($claim->amount > 0);
        $this->assertEquals('Mileage', $claim->claim_type);
    }
}
