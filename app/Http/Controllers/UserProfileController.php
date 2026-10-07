<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetSuccessMail;
use App\Services\EmailDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class UserProfileController extends Controller
{
    /**
     * Staff profile view.
     */
    public function profileIndex()
    {
        $user = auth()->user();
        if ($user && strtolower($user->role) === 'manager') {
            return redirect()->route('manager.profile');
        }
        if ($user && in_array(strtolower($user->role), ['finance', 'fin'])) {
            return redirect()->route('finance.profile');
        }
        return view('profile.index', compact('user'));
    }

    /**
     * Update banking and profile attributes.
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $user->id,
            'bank_name' => 'sometimes|required|string|max:100',
            'bank_account_no' => 'sometimes|required|string|max:50',
            'bank_account_holder' => 'sometimes|required|string|max:150',
            'phone_number' => 'sometimes|nullable|string|max:20',
        ]);

        $user->update($validated);

        return redirect()->back()->with('success', 'Banking and profile particulars successfully updated.');
    }

    /**
     * Update account password.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = auth()->user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        try {
            EmailDeliveryService::sendMailable($user->email, new PasswordResetSuccessMail($user));
        } catch (\Throwable $e) {
            Log::warning("Failed to send password update confirmation email to {$user->email}: " . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Account password successfully updated.');
    }
}
