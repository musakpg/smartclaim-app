<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileBottomNavTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test staff role receives standard staff mobile navigation.
     */
    public function test_staff_sees_staff_mobile_navigation()
    {
        $staff = User::factory()->create([
            'role' => 'Staff',
        ]);

        $response = $this->actingAs($staff)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('aria-label="Mobile Navigation"', false);
        $response->assertSee(route('dashboard'));
        $response->assertSee(route('claims.create'));
        $response->assertSee(route('claims.history'));
        $response->assertSee(route('advances.index'));
        $response->assertSee(route('profile.index'));
        $response->assertSee('New Claim');
    }

    /**
     * Test manager role receives manager mobile navigation.
     */
    public function test_manager_sees_manager_mobile_navigation()
    {
        $manager = User::factory()->create([
            'role' => 'Manager',
        ]);

        $response = $this->actingAs($manager)->get(route('manager.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('aria-label="Mobile Navigation"', false);
        $response->assertSee(route('manager.dashboard'));
        $response->assertSee(route('manager.verification'));
        $response->assertSee(route('manager.vehicles'));
        $response->assertSee(route('manager.advances'));
        $response->assertSee(route('profile.index'));
        $response->assertSee('Verification');
    }

    /**
     * Test finance role receives finance mobile navigation.
     */
    public function test_finance_sees_finance_mobile_navigation()
    {
        $finance = User::factory()->create([
            'role' => 'Finance',
        ]);

        $response = $this->actingAs($finance)->get(route('finance.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('aria-label="Mobile Navigation"', false);
        $response->assertSee(route('finance.dashboard'));
        $response->assertSee(route('finance.auditing'));
        $response->assertSee(route('finance.disbursement'));
        $response->assertSee(route('finance.cash-advances.index'));
        $response->assertSee(route('profile.index'));
        $response->assertSee('Settlement');
    }
}
