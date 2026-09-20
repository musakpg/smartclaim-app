<?php

namespace App\Http\Controllers;

use App\Models\InAppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get unread notification counter and latest notifications payload.
     */
    public function fetchLatest()
    {
        $userId = Auth::id() ?? 1;

        $unreadCount = InAppNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        $notifications = InAppNotification::where('user_id', $userId)
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $userId = Auth::id() ?? 1;

        InAppNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }
}