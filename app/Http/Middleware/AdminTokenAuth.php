<?php

namespace App\Http\Middleware;

use Closure;
use App\Enums\RoleEnum;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AdminTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        // If already authenticated via web session, check role
        if (auth()->check()) {
            // Cache user permissions for the session
            $user = auth()->user();
            if ($user && $user->id) {
                getCachedUserPermissions($user->id);
            }
            return $this->checkUserRole($request, $next);
        }

        // Bearer token or cookie
        $token = $request->bearerToken() ?: $request->cookie('admin_token');

        // Treat only /api/* as API. All other routes are pages and should redirect.
        $isApiRequest = $request->is('api/*');

        if (!$token) {
            if ($isApiRequest) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('admin.login');
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken || !$accessToken->tokenable) {
            if ($isApiRequest) {
                return response()->json(['message' => 'Invalid token.'], 401);
            }
            cookie()->queue(cookie()->forget('admin_token'));
            return redirect()->route('admin.login')->with('error', 'Session expired. Please login again.');
        }

        // Authenticate request as token owner
        auth()->setUser($accessToken->tokenable);

        // Check user role after authentication
        return $this->checkUserRole($request, $next);
    }

    /**
     * Check if the authenticated user has permission to access admin panel
     */
    protected function checkUserRole(Request $request, Closure $next)
    {
        $user = auth()->user();
        $isApiRequest = $request->is('api/*');

        // Block consumers from accessing admin panel
        if ($user && $user->hasRole(RoleEnum::CONSUMER)) {
            if ($isApiRequest) {
                return response()->json([
                    'message' => 'Access denied. Consumers cannot access the admin panel.',
                    'success' => false
                ], 403);
            }

            // Log them out and redirect
            auth()->logout();
            cookie()->queue(cookie()->forget('admin_token'));
            return redirect()->route('admin.login')
                ->with('error', 'Access denied. Consumers cannot access the admin panel.');
        }

        return $next($request);
    }
}
