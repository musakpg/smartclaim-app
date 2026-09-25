<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use App\Models\User;
use App\Models\Claim;

class ReceiptClaimLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_claim_lifecycle()
    {
        $staff = User::factory()->create(['role' => 'Staff']);
        $manager = User::factory()->create(['role' => 'Manager']);
        $finance = User::factory()->create(['role' => 'Finance']);

        // 1. Simulate submission
        $receipt = UploadedFile::fake()->image('receipt.jpg');
        $response = $this->actingAs($staff)->postJson('/claims/store', [
            'claim_type' => 'Receipt',
            'title' => 'Office Supplies',
            'amount' => 150.50,
            'merchant_name' => 'DIY Store',
            'category' => 'Office Supplies',
            'receipt' => UploadedFile::fake()->image('receipt.jpg'),
            'payment_method' => 'Cash',
            'transaction_date' => now()->format('Y-m-d'),
            'business_purpose' => 'Buying office supplies'
        ]);

        $response->assertStatus(302); // Usually redirects on success
        
        $claim = Claim::first();
        $this->assertNotNull($claim);
        $this->assertEquals(150.50, $claim->amount);
        $this->assertTrue($claim->amount > 0);
        $this->assertEquals('DIY Store', $claim->merchant_name);
        $this->assertEquals('Office Supplies', $claim->predicted_category);

        // 2. Claimant cannot approve their own claim
        $response = $this->actingAs($staff)->postJson("/manager/claims/{$claim->claim_id}/status", [
            'status' => 'Approved',
            'rejection_reason' => ''
        ]);
        $response->assertStatus(403);

        // 3. Manager approval transitions state to 'Approved'
        $response = $this->actingAs($manager)->post("/manager/claims/{$claim->claim_id}/status", [
            'status' => 'Approved',
        ]);
        
        $this->assertEquals('Approved', $claim->fresh()->status);

        // 4. Finance settlement transitions state to 'Reimbursed' with valid payment proof attachment
        $proof = UploadedFile::fake()->image('proof.jpg');
        $this->withoutExceptionHandling();
        $response = $this->actingAs($finance)->post("/finance/disbursement/{$claim->claim_id}/settle", [
            'payment_reference' => 'REF123456',
            'payment_proof' => $proof
        ]);
        
        $this->assertEquals('Reimbursed', $claim->fresh()->status);
        
        $this->assertEquals('Reimbursed', $claim->fresh()->status);
        $this->assertNotNull($claim->fresh()->payment_proof_path);
    }
}
