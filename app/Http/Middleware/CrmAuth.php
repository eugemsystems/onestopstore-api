<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * CRM inbound authentication.
 * Accepts either a valid Sanctum Bearer token OR an X-CRM-Secret header
 * matching CRM_INBOUND_SECRET from .env. The secret path makes the
 * integration resilient to Sanctum token rotation.
 */
class CrmAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Try shared secret first (CRM → API machine-to-machine)
        $secret = config('services.crm.inbound_secret');
        if ($secret && hash_equals($secret, (string) $request->header('X-CRM-Secret', ''))) {
            return $next($request);
        }

        // Fall back to Sanctum bearer token
        if (auth()->guard('sanctum')->check()) {
            auth()->shouldUse('sanctum');
            return $next($request);
        }

        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
}
