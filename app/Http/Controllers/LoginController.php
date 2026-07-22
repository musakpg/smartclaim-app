<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Display the custom login view.
     */
    public function showLoginForm()
    {
        // Points directly to your existing login blade file
        return view('auth.login'); 
    }

    /**
     * Handle an inbound authentication request and strictly validate credentials.
     */
    public function login(Request $request)
    {
        // 1. Strict form validation requirements
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // 2. SECURE ATTEMPT HANDSHAKE: Checks email AND strictly validates password hash matching
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            
            // Regenerate session tokens to prevent session fixation exploits
            $request->session()->regenerate();

            // Redirect user to dashboard smoothly
            return redirect()->intended(route('dashboard'))
                ->with('success', 'Welcome back to SmartClaim, ' . Auth::user()->name . '!');
        }

        // 3. REJECTION GUARD: If attempt fails, bounce back with a security error alert
        return redirect()->back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors([
                'email' => 'SECURITY ALERT: The provided credentials (email or password) do not match our database records.',
            ]);
    }

    /**
     * Log the user out of the application safely.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}