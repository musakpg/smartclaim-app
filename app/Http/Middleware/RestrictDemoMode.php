<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RestrictDemoMode
{
    /**
     * Paths/Routes excluded from demo mode mutation blocking.
     */
    protected array $except = [
        'login',
        'login/process',
        'logout',
        'claims/async-scan',
        'finance/disbursement/scan-slip',
        'api/notifications/mark-read',
        'push-subscriptions',
    ];

    /**
     * Handle an incoming request.
     * Intercept state-mutating requests if authenticated user is in Demo Mode.
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && (bool) (Auth::user()->is_demo ?? false)) {
            $method = strtoupper($request->method());

            if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                // Check if the current route/path is excluded
                if ($this->isExcluded($request)) {
                    return $next($request);
                }

                $message = 'Demo Mode: Action is restricted to Read-Only to preserve showcase audit data.';

                if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 403);
                }

                return redirect()->back()
                    ->with('warning', $message)
                    ->with('error', $message)
                    ->with('info', $message);
            }
        }

        return $next($request);
    }

    /**
     * Check if request matches any excluded paths.
     */
    protected function isExcluded(Request $request): bool
    {
        foreach ($this->except as $path) {
            if ($request->is($path) || $request->is(trim($path, '/') . '/*')) {
                return true;
            }
        }

        return false;
    }
}
