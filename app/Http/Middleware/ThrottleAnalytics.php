<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use App\Services\BotDetectionService;

class ThrottleAnalytics
{
    protected BotDetectionService $botDetection;

    public function __construct(BotDetectionService $botDetection)
    {
        $this->botDetection = $botDetection;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ---------------------------------------------------------------
        // BOT DETECTION — reject bots before any data is stored or queued
        // Silently return success so bots don't retry or adapt.
        // ---------------------------------------------------------------
        if ($this->botDetection->isBot($request->userAgent(), $request->ip())) {
            return response()->json(['success' => true], 200);
        }

        // Rate limit: 60 requests per minute per session/IP
        $key = 'analytics:' . ($request->input('session_id') ?? $request->ip());

        if (RateLimiter::tooManyAttempts($key, 60)) {
            // Silently succeed for analytics - we don't want to break UX
            return response()->json(['success' => true], 200);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
