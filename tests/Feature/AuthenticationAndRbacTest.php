<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AuthenticationAndRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_staff_blocked_from_manager_and_finance_portals()
    {
        $staff = User::factory()->create(['role' => 'Staff']);

        $response = $this->actingAs($staff)->get('/manager/dashboard');
        $response->assertStatus(403);

        $response = $this->actingAs($staff)->get('/finance/dashboard');
        $response->assertStatus(403);
    }

    public function test_manager_can_access_manager_portal()
    {
        $this->withoutExceptionHandling();
        $manager = User::factory()->create(['role' => 'Manager']);

        $response = $this->actingAs($manager)->get('/manager/dashboard');
        $response->assertStatus(200);
    }

    public function test_finance_can_access_finance_portal()
    {
        $finance = User::factory()->create(['role' => 'Finance']);

        $response = $this->actingAs($finance)->get('/finance/dashboard');
        $response->assertStatus(200);
    }

    public function test_login_page_renders_evaluation_sandbox_footer()
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('SmartClaim v1.0.0-rc · Evaluation Sandbox Environment');
        $response->assertSee('smartclaim.aeroart@gmail.com');
    }
}
