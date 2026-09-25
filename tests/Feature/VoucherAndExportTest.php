<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Claim;

class VoucherAndExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_voucher_generation()
    {
        $staff = User::factory()->create(['role' => 'Staff']);
        
        $claim = Claim::create([
            'user_id' => $staff->user_id,
            'title' => 'Test PDF Voucher',
            'amount' => 50.00,
            'merchant_name' => 'ABC',
            'status' => 'Approved'
        ]);

        $response = $this->actingAs($staff)->get("/claims/{$claim->claim_id}/voucher-pdf");
        
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_unauthorized_user_cannot_download_other_vouchers()
    {
        $staff1 = User::factory()->create(['role' => 'Staff']);
        $staff2 = User::factory()->create(['role' => 'Staff']);
        
        $claim = Claim::create([
            'user_id' => $staff1->user_id,
            'title' => 'Private Voucher',
            'amount' => 50.00,
            'merchant_name' => 'ABC',
            'status' => 'Approved'
        ]);

        // Staff 2 trying to download Staff 1's voucher
        $response = $this->actingAs($staff2)->get("/claims/{$claim->claim_id}/voucher-pdf");
        
        $response->assertStatus(403);
    }
}
