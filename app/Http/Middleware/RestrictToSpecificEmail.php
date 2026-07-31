<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictToSpecificEmail
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $allowedEmail): Response
    {
        if (!auth()->check()) {
            return redirect()->route('admin.login')->with('error', 'Please login to access this page.');
        }

        if (auth()->user()->email !== $allowedEmail) {
            abort(403, 'Unauthorized access. This page is restricted.');
        }

        return $next($request);
    }
}
