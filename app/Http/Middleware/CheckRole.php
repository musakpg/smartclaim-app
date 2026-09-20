<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        // 1. Semak jika pengguna belum login
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // 2. Semak role secara case-insensitive (abaikan huruf besar/kecil)
        if (strtolower(trim($user->role ?? '')) !== strtolower(trim($role))) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}