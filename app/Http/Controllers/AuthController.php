<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

            if ($user->role === 'Manager' || $user->email === 'manager@aeroart.com') {
                return redirect()->route('manager.dashboard');
            } elseif ($user->role === 'Finance' || $user->role === 'fin' || $user->email === 'finance@aeroart.com') {
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
            $request->session()->regenerate();

            $user = Auth::user();

            // 👑 TIER 1 EXECUTIVE GATEWAY: Redirects authenticated users matching the Manager role pattern
            if ($user->role === 'Manager' || $user->email === 'manager@aeroart.com') {
                return redirect()->route('manager.dashboard')
                    ->with('success', 'Executive management session initialized. Welcome back, Manager!');
            }

            // 🔍 TIER 2 AUDITOR GATEWAY: Matches shortened or standard role mappings for Finance accounts
            if ($user->role === 'Finance' || $user->role === 'fin' || $user->email === 'finance@aeroart.com') {
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