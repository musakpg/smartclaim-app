<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use NotificationChannels\WebPush\PushSubscription;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $this->validate($request, [
            'endpoint' => 'required',
            'keys.auth' => 'required',
            'keys.p256dh' => 'required'
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $endpoint = $request->input('endpoint');
        $key = $request->input('keys.p256dh');
        $token = $request->input('keys.auth');

        // Padam langganan lama dengan endpoint yang sama jika ada
        PushSubscription::where('endpoint', $endpoint)->delete();

        // Cipta langganan baru yang dipautkan ke user_id
        $user->pushSubscriptions()->create([
            'endpoint' => $endpoint,
            'public_key' => $key,
            'auth_token' => $token,
            'content_encoding' => $request->input('contentEncoding', 'aes128gcm'),
        ]);

        return response()->json(['success' => true, 'message' => 'Device successfully subscribed.']);
    }
}