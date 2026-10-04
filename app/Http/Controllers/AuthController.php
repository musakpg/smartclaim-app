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

            // Check if user account is deactivated or pending activation
            if (isset($user->is_active) && !$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if (!empty($user->activation_token)) {
                    return redirect()->route('login')->withErrors([
                        'loginError' => 'Your account is pending activation. Please check your email to set your password and activate your account.',
                    ]);
                }

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
     * Process staff self-registration without immediate password.
     * Dispatches an account activation email containing a direct password setup link.
     */
    public function processRegister(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|string|email|max:255|unique:users,email',
            'bank_name'        => 'required|string|max:100',
            'custom_bank_name' => 'nullable|string|max:100',
            'bank_account_no'  => 'required|string|max:50',
        ]);

        $bankName = $validated['bank_name'];
        if ($bankName === 'Other' && !empty($request->custom_bank_name)) {
            $bankName = trim($request->custom_bank_name);
        }

        $activationToken = Str::random(64);

        $user = User::create([
            'name'                => trim($validated['name']),
            'email'               => strtolower(trim($validated['email'])),
            'password'            => Hash::make(Str::random(32)), // Temporary random password until activated
            'role'                => 'Staff',
            'is_active'           => false,
            'activation_token'    => $activationToken,
            'bank_name'           => $bankName,
            'bank_account_no'     => trim($validated['bank_account_no']),
            'bank_account_holder' => trim($validated['name']),
        ]);

        $setupUrl = url('/setup-password/' . $activationToken);

        // Always log the activation link for operational traceability and testing
        Log::info("SmartClaim: Account activation link generated for {$user->email}: {$setupUrl}");

        try {
            Mail::to($user->email)->send(new AccountActivationMail($user, $setupUrl));
        } catch (\Throwable $e) {
            Log::error("Failed to deliver account activation email to {$user->email}: " . $e->getMessage());
        }

        return redirect()->route('login')->with('success', "Registration successful! An activation link has been emailed to {$user->email}. Please check your inbox and click the direct link to set your password.");
    }

    /**
     * Display the setup password view when user clicks their activation link.
     */
    public function showSetupPassword($token)
    {
        $user = User::where('activation_token', $token)->first();

        if (!$user) {
            return redirect()->route('login')->withErrors([
                'loginError' => 'This account activation link is invalid, expired, or has already been used.',
            ]);
        }

        return view('auth.setup-password', compact('user', 'token'));
    }

    /**
     * Process password setup from the activation link, activating the account.
     */
    public function processSetupPassword(Request $request, $token)
    {
        $user = User::where('activation_token', $token)->first();

        if (!$user) {
            return redirect()->route('login')->withErrors([
                'loginError' => 'This account activation link is invalid, expired, or has already been used.',
            ]);
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->password = Hash::make($request->password);
        $user->is_active = true;
        $user->activation_token = null;
        $user->save();

        Log::info("SmartClaim: User {$user->email} successfully activated their account and set password.");

        return redirect()->route('login')->with('success', 'Your password has been configured and your account is now active! You may now log in.');
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

            try {
                Mail::to($user->email)->send(new ResetPasswordMail($user, $resetUrl));
            } catch (\Throwable $e) {
                Log::error("Failed to deliver password reset email to {$email}: " . $e->getMessage());
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
            $user->is_active = true; // Automatically activate if was previously deactivated
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