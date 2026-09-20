<?php

namespace App\Services;

use App\Models\InAppNotification;
use App\Models\User;
use App\Notifications\WebPushNotification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send in-app notification to a specific user and dispatch Web Push to their physical device.
     */
    public static function send(int $userId, string $title, string $message, string $type = 'info', ?string $url = null): void
    {
        // 1. Simpan dalam pangkalan data untuk paparan loceng dalam web
        try {
            InAppNotification::create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'target_url' => $url,
                'is_read' => false,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to dispatch in-app notification: " . $e->getMessage());
        }

        // 2. Pancarkan Web Push Notification terus ke skrin telefon / laptop staf
        try {
            $user = User::where('user_id', $userId)->orWhere('id', $userId)->first();
            if ($user) {
                $user->notify(new WebPushNotification($title, $message, $url));
            }
        } catch (\Throwable $e) {
            Log::warning("WebPush Dispatch warning: " . $e->getMessage());
        }
    }

    /**
     * Broadcast notification to all managers (e.g. for high-risk fraud alerts).
     */
    public static function notifyManagers(string $title, string $message, string $type = 'warning', ?string $url = null): void
    {
        try {
            $managers = User::whereIn('role', ['Manager', 'Admin'])->get();
            foreach ($managers as $mgr) {
                self::send($mgr->user_id ?? $mgr->id, $title, $message, $type, $url);
            }
        } catch (\Throwable $e) {
            Log::error("Failed to broadcast manager notification: " . $e->getMessage());
        }
    }
}