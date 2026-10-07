<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    /**
     * Display corporate user accounts management index.
     */
    public function userManagementIndex()
    {
        $users = User::orderBy('name', 'asc')->get();
        return view('manager.user_management', compact('users'));
    }

    /**
     * Toggle active/inactive status of corporate user.
     */
    public function toggleUserStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->user_id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot deactivate your own account.');
        }

        $oldStatus = $user->is_active;
        $user->is_active = !$user->is_active;
        $user->save();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $user->is_active ? 'Activated User' : 'Deactivated User',
            'model_type' => 'User',
            'model_id' => $user->user_id,
            'old_values' => json_encode(['is_active' => (bool) $oldStatus]),
            'new_values' => json_encode(['is_active' => (bool) $user->is_active]),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'event_category' => 'USER_MANAGEMENT'
        ]);

        return redirect()->back()->with('success', 'User status updated successfully.');
    }
}
