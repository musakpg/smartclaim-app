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

    public function test_logout_invalidates_session_and_redirects_to_login()
    {
        $staff = User::factory()->create(['role' => 'Staff']);

        $response = $this->actingAs($staff)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $response->assertSessionHas('success', 'Session terminated successfully. Securely logged out.');
    }

    public function test_token_mismatch_exception_redirects_to_login_with_warning()
    {
        $request = \Illuminate\Http\Request::create('/login/process', 'POST');
        $session = app('session.store');
        $request->setLaravelSession($session);

        $handler = app(\App\Exceptions\Handler::class);
        $response = $handler->render($request, new \Illuminate\Session\TokenMismatchException('CSRF token mismatch.'));

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('login'), $response->headers->get('Location'));
        $this->assertEquals('Your session expired. Please log in again.', $response->getSession()->get('warning'));
    }

    public function test_json_token_mismatch_exception_returns_419()
    {
        $request = \Illuminate\Http\Request::create('/api/action', 'POST');
        $request->headers->set('Accept', 'application/json');

        $handler = app(\App\Exceptions\Handler::class);
        $response = $handler->render($request, new \Illuminate\Session\TokenMismatchException('CSRF token mismatch.'));

        $this->assertEquals(419, $response->getStatusCode());
        $this->assertEquals('Your session expired. Please log in again.', json_decode($response->getContent(), true)['message']);
    }
}
