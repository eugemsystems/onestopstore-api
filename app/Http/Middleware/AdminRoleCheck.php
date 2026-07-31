<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Enums\RoleEnum;
use Symfony\Component\HttpFoundation\Response;

class AdminRoleCheck
{
    /**
     * Handle an incoming request.
     *
     * Only allow users with admin or vendor roles to access admin panel.
     * Consumer role is blocked from accessing admin panel.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('admin.login');
        }

        $user = auth()->user();

        // Block consumers from accessing admin panel
        if ($user->hasRole(RoleEnum::CONSUMER)) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Access denied. Consumers cannot access the admin panel.',
                    'success' => false
                ], 403);
            }

            // Log them out and redirect
            auth()->logout();
            return redirect()->route('admin.login')
                ->with('error', 'Access denied. Consumers cannot access the admin panel.');
        }

        // Allow admin and vendor roles to proceed
        return $next($request);
    }
}

