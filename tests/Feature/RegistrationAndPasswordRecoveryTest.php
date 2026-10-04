<?php

namespace Tests\Feature;

use App\Mail\AccountActivationMail;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationAndPasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_registration_page()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertSee('STAFF REGISTRATION');
    }

    public function test_staff_can_register_and_receives_activation_email()
    {
        Mail::fake();

        $payload = [
            'name'            => 'Noraini binti Kassim',
            'email'           => 'noraini@aeroart.com',
            'bank_name'       => 'Maybank',
            'bank_account_no' => '164012345678',
        ];

        $response = $this->post('/register', $payload);

        $response->assertRedirect('/');
        $response->assertSessionHas('success');

        // Ensure user is NOT created in users table yet
        $this->assertDatabaseMissing('users', [
            'email' => 'noraini@aeroart.com',
        ]);

        // Ensure record is saved in pending_registrations table
        $this->assertDatabaseHas('pending_registrations', [
            'name'            => 'Noraini binti Kassim',
            'email'           => 'noraini@aeroart.com',
            'bank_name'       => 'Maybank',
            'bank_account_no' => '164012345678',
        ]);

        $pending = DB::table('pending_registrations')->where('email', 'noraini@aeroart.com')->first();
        $this->assertNotEmpty($pending->activation_token);

        Mail::assertSent(AccountActivationMail::class, function ($mail) use ($pending) {
            return $mail->user->email === $pending->email &&
                   str_contains($mail->setupUrl, $pending->activation_token);
        });
    }

    public function test_staff_can_setup_password_and_activate_account()
    {
        $token = 'test-activation-token-12345';
        DB::table('pending_registrations')->insert([
            'name'                => 'Noraini binti Kassim',
            'email'               => 'noraini.setup@aeroart.com',
            'bank_name'           => 'Maybank',
            'bank_account_no'     => '164012345678',
            'bank_account_holder' => 'Noraini binti Kassim',
            'activation_token'    => $token,
            'created_at'          => Carbon::now(),
        ]);

        $viewResponse = $this->get('/setup-password/' . $token);
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('ACTIVATE YOUR ACCOUNT');

        $postResponse = $this->post('/setup-password/' . $token, [
            'password'              => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ]);

        $postResponse->assertRedirect('/');
        $postResponse->assertSessionHas('success');

        // Verify pending registration was cleared
        $this->assertDatabaseMissing('pending_registrations', [
            'activation_token' => $token,
        ]);

        // Verify user is now created in users table
        $this->assertDatabaseHas('users', [
            'email'     => 'noraini.setup@aeroart.com',
            'role'      => 'Staff',
            'is_active' => true,
        ]);

        $user = User::where('email', 'noraini.setup@aeroart.com')->first();
        $this->assertTrue(Hash::check('StrongPassword123!', $user->password));
    }

    public function test_activation_link_expires_after_5_minutes()
    {
        $token = 'expired-token-999';
        DB::table('pending_registrations')->insert([
            'name'                => 'Late User',
            'email'               => 'late@aeroart.com',
            'bank_name'           => 'CIMB Bank',
            'bank_account_no'     => '8001234567',
            'bank_account_holder' => 'Late User',
            'activation_token'    => $token,
            'created_at'          => Carbon::now()->subMinutes(6), // Over 5 minutes ago
        ]);

        $response = $this->get('/setup-password/' . $token);
        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['loginError']);

        // Assert cleaned up
        $this->assertDatabaseMissing('pending_registrations', [
            'activation_token' => $token,
        ]);
    }

    public function test_forgot_password_sends_reset_email_with_token()
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'staff.reset@aeroart.com',
        ]);

        $response = $this->post('/forgot-password', [
            'email' => 'staff.reset@aeroart.com',
        ]);

        $response->assertSessionHas('status');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'staff.reset@aeroart.com',
        ]);

        Mail::assertSent(ResetPasswordMail::class, function ($mail) use ($user) {
            return $mail->user->email === $user->email;
        });
    }

    public function test_user_can_reset_password_with_valid_token()
    {
        $user = User::factory()->create([
            'email' => 'staff.reset2@aeroart.com',
        ]);

        $token = 'valid-reset-token-67890';
        DB::table('password_reset_tokens')->insert([
            'email'      => $user->email,
            'token'      => $token,
            'created_at' => Carbon::now(),
        ]);

        $viewResponse = $this->get('/reset-password/' . $token . '?email=' . urlencode($user->email));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('RESET PASSWORD');

        $postResponse = $this->post('/reset-password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'NewSecurePassword888!',
            'password_confirmation' => 'NewSecurePassword888!',
        ]);

        $postResponse->assertRedirect('/');
        $postResponse->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword888!', $user->password));
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }
}
