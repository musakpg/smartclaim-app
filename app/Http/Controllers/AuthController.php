<?php

namespace App\Http\Controllers;

use App\Mail\AccountActivationMail;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Render the secure login form authentication view interface.
     */
    public function showLogin()
    {
        // If the user session is already authenticated, redirect them automatically based on role matrix
        if (Auth::check()) {
            $user = Auth::user();
            $normalizedRole = strtolower(trim($user->role ?? ''));

            if ($normalizedRole === 'manager') {
                return redirect()->route('manager.dashboard');
            } elseif ($normalizedRole === 'finance' || $normalizedRole === 'fin') {
                return redirect()->route('finance.dashboard');
            }
            
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Process authentication credentials and handle institutional routing handshakes.
     * Hardened with dynamic role parsing for Staff vs Finance Auditor vs Executive Manager routing.
     */
    public function processLogin(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email|string',
            'password' => 'required|string',
        ]);

        // Official Laravel authentication mechanism integrated with Bcrypt hashing
        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Check if user account is deactivated
            if (isset($user->is_active) && !$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('account_inactive', true);
            }

            $request->session()->regenerate();

            $normalizedRole = strtolower(trim($user->role ?? ''));

            // 👑 TIER 1 EXECUTIVE GATEWAY: Redirects authenticated users matching the Manager role pattern
            if ($normalizedRole === 'manager') {
                return redirect()->route('manager.dashboard')
                    ->with('success', 'Executive management session initialized. Welcome back, Manager!');
            }

            // 🔍 TIER 2 AUDITOR GATEWAY: Matches shortened or standard role mappings for Finance accounts
            if ($normalizedRole === 'finance' || $normalizedRole === 'fin') {
                return redirect()->route('finance.dashboard')
                    ->with('success', 'Finance portal active. Welcome back, Auditor Officer!');
            }

            // Default fallback routing handler for standard Aero Art staff accounts
            return redirect()->route('dashboard')
                ->with('success', 'Authentication handshake verified. Welcome back, ' . $user->name . '!');
        }

        // Authentication failure routine boundary
        return redirect()->back()
            ->withInput($request->only('email'))
            ->withErrors([
                'loginError' => 'SECURITY REJECTION: Invalid email or password combination details found.',
            ]);
    }

    /**
     * Render the staff registration form interface.
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    /**
     * Process staff self-registration without immediate insertion into active users table.
     * Stores in pending_registrations queue with a strict 5-minute expiration limit.
     * Role is strictly reserved for normal Staff; Executive/Finance roles are pre-provisioned by administration.
     */
    public function processRegister(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|string|email|max:255',
            'bank_name'        => 'required|string|max:100',
            'custom_bank_name' => 'nullable|string|max:100',
            'bank_account_no'  => 'required|string|max:50',
        ]);

        $email = strtolower(trim($validated['email']));

        // Check if an activated user already exists in users table
        if (User::where('email', $email)->exists()) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['email' => 'This email address is already registered as an employee account.']);
        }

        $bankName = $validated['bank_name'];
        if ($bankName === 'Other' && !empty($request->custom_bank_name)) {
            $bankName = trim($request->custom_bank_name);
        }

        // Clean up any stale/expired pending registrations older than 5 minutes
        DB::table('pending_registrations')->where('created_at', '<', Carbon::now()->subMinutes(5))->delete();

        // Remove any previous pending attempts for this email
        DB::table('pending_registrations')->where('email', $email)->delete();

        $activationToken = Str::random(64);

        // Save into pending_registrations only (NOT users table)
        DB::table('pending_registrations')->insert([
            'name'                => trim($validated['name']),
            'email'               => $email,
            'bank_name'           => $bankName,
            'bank_account_no'     => trim($validated['bank_account_no']),
            'bank_account_holder' => trim($validated['name']),
            'activation_token'    => $activationToken,
            'created_at'          => Carbon::now(),
        ]);

        $setupUrl = url('/setup-password/' . $activationToken);

        Log::info("SmartClaim: Account activation link generated for {$email} (Expires in 5 mins): {$setupUrl}");

        $pendingUser = (object)[
            'name'            => trim($validated['name']),
            'email'           => $email,
            'bank_name'       => $bankName,
            'bank_account_no' => trim($validated['bank_account_no']),
        ];

        $mailable = new AccountActivationMail($pendingUser, $setupUrl);
        $delivery = \App\Services\EmailDeliveryService::sendMailable($email, $mailable);

        if ($delivery['success']) {
            return redirect()->route('login')->with('success', "Registration initiated! An activation email has been sent to {$email} (valid for 5 minutes). Please check your inbox to set your password.");
        }

        // If restricted by free cloud tier or email provider (e.g. unverified domain):
        // Provide the direct link safely so user can activate immediately within the 5-minute window!
        return redirect()->route('login')
            ->with('success', "Registration initiated! Your profile is ready for activation (valid for 5 minutes).")
            ->with('activation_url', $setupUrl)
            ->with('activation_notice', $delivery['is_restricted']
                ? 'External cloud email dispatch is restricted without a verified domain. You can activate your account directly below:'
                : 'Click the link below to set your password and complete activation:');
    }

    /**
     * Display the setup password view when user clicks their activation link.
     * Enforces the 5-minute validity limit.
     */
    public function showSetupPassword($token)
    {
        $pending = DB::table('pending_registrations')->where('activation_token', $token)->first();

        if (!$pending) {
            return redirect()->route('register')->withErrors([
                'loginError' => 'This activation link is invalid or has already been used. Please submit your registration again.',
            ]);
        }

        // Check if registration token has expired (5 minutes)
        if (Carbon::parse($pending->created_at)->addMinutes(5)->isPast()) {
            DB::table('pending_registrations')->where('activation_token', $token)->delete();
            return redirect()->route('register')->withErrors([
                'loginError' => 'Your activation link has expired (5-minute validity limit). Please register again.',
            ]);
        }

        $user = (object)[
            'name'  => $pending->name,
            'email' => $pending->email,
        ];

        return view('auth.setup-password', compact('user', 'token'));
    }

    /**
     * Process password setup from the activation link.
     * Inserts the verified record into the official users table and activates the account.
     */
    public function processSetupPassword(Request $request, $token)
    {
        $pending = DB::table('pending_registrations')->where('activation_token', $token)->first();

        if (!$pending || Carbon::parse($pending->created_at)->addMinutes(5)->isPast()) {
            if ($pending) {
                DB::table('pending_registrations')->where('activation_token', $token)->delete();
            }
            return redirect()->route('register')->withErrors([
                'loginError' => 'Your activation link is invalid or has expired (5-minute limit). Please register again.',
            ]);
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        // NOW insert the user into the real users table with chosen password and role=Staff
        $user = User::create([
            'name'                => $pending->name,
            'email'               => $pending->email,
            'password'            => Hash::make($request->password),
            'role'                => 'Staff',
            'is_active'           => true,
            'bank_name'           => $pending->bank_name,
            'bank_account_no'     => $pending->bank_account_no,
            'bank_account_holder' => $pending->bank_account_holder ?: $pending->name,
        ]);

        // Delete from pending registrations queue
        DB::table('pending_registrations')->where('activation_token', $token)->delete();

        Log::info("SmartClaim: User {$user->email} successfully activated their account and created password.");

        return redirect()->route('login')->with('success', 'Your account has been successfully activated and password configured! You may now log in.');
    }

    /**
     * Render the forgot password request view.
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /**
     * Process forgot password request and send reset link email.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|string',
        ]);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        if ($user) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token'      => $token,
                    'created_at' => Carbon::now(),
                ]
            );

            $resetUrl = url('/reset-password/' . $token . '?email=' . urlencode($email));

            Log::info("SmartClaim: Password reset link generated for {$email}: {$resetUrl}");

            $mailable = new ResetPasswordMail($user, $resetUrl);
            $delivery = \App\Services\EmailDeliveryService::sendMailable($user->email, $mailable);

            if (!$delivery['success'] && $delivery['is_restricted']) {
                return redirect()->back()
                    ->with('status', 'Password reset generated.')
                    ->with('reset_url', $resetUrl);
            }
        }

        return redirect()->back()->with('status', 'If your email is registered in our system, a password reset link has been dispatched to your inbox.');
    }

    /**
     * Render the reset password form view from email link.
     */
    public function showResetPassword(Request $request, $token)
    {
        $email = $request->query('email');

        $record = DB::table('password_reset_tokens')->where('token', $token)->first();

        if (!$record) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'This password reset link is invalid or has already been used. Please request a new one.',
            ]);
        }

        // Check if token has expired (valid for 60 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $record->email)->delete();
            return redirect()->route('password.request')->withErrors([
                'email' => 'This password reset link has expired. Please request a new one.',
            ]);
        }

        $email = $record->email;

        return view('auth.reset-password', compact('token', 'email'));
    }

    /**
     * Process password reset form submission.
     */
    public function processResetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required|string',
            'email'    => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $email = strtolower(trim($request->email));
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('token', $request->token)
            ->first();

        if (!$record || Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'The password reset token is invalid or has expired. Please request a new link.',
            ]);
        }

        $user = User::where('email', $email)->first();
        if ($user) {
            $user->password = Hash::make($request->password);
            $user->is_active = true;
            $user->save();

            DB::table('password_reset_tokens')->where('email', $email)->delete();

            Log::info("SmartClaim: User {$email} successfully reset their password.");

            return redirect()->route('login')->with('success', 'Your password has been reset successfully! You can now log in with your new password.');
        }

        return redirect()->route('login')->withErrors([
            'loginError' => 'Unable to locate user account for password reset.',
        ]);
    }

    /**
     * Terminate the active authenticated session context safely (Sign Out).
     */
    public function logout(Request $request)
    {
        // Clear corporate user state from authentication guard memory context
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Session terminated successfully. Securely logged out.');
    }
}