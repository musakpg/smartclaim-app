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

        $this->assertDatabaseHas('users', [
            'name'            => 'Noraini binti Kassim',
            'email'           => 'noraini@aeroart.com',
            'bank_name'       => 'Maybank',
            'bank_account_no' => '164012345678',
            'is_active'       => false,
            'role'            => 'Staff',
        ]);

        $user = User::where('email', 'noraini@aeroart.com')->first();
        $this->assertNotEmpty($user->activation_token);

        Mail::assertSent(AccountActivationMail::class, function ($mail) use ($user) {
            return $mail->user->email === $user->email &&
                   str_contains($mail->setupUrl, $user->activation_token);
        });
    }

    public function test_staff_can_setup_password_and_activate_account()
    {
        $user = User::factory()->create([
            'is_active'        => false,
            'activation_token' => 'test-activation-token-12345',
        ]);

        $viewResponse = $this->get('/setup-password/' . $user->activation_token);
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('ACTIVATE YOUR ACCOUNT');

        $postResponse = $this->post('/setup-password/' . $user->activation_token, [
            'password'              => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ]);

        $postResponse->assertRedirect('/');
        $postResponse->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue((bool)$user->is_active);
        $this->assertNull($user->activation_token);
        $this->assertTrue(Hash::check('StrongPassword123!', $user->password));
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
