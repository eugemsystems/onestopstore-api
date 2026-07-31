<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\BotDetectionService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class FilterBotTraffic
{
    protected BotDetectionService $botDetection;

    public function __construct(BotDetectionService $botDetection)
    {
        $this->botDetection = $botDetection;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if bot filtering is enabled
        if (!config('analytics.bot_detection.enabled', true)) {
            return $next($request);
        }

        $userAgent = $request->userAgent();
        $ip = $request->ip();

        // ---------------------------------------------------------------
        // FAST PATH: Hardcoded datacenter prefix check (Tencent, Alibaba)
        // Catches these before even calling BotDetectionService.
        // ---------------------------------------------------------------
        if ($this->isKnownDatacenterPrefix($ip)) {
            return response()->json(['success' => true], 200);
        }

        // ---------------------------------------------------------------
        // FULL SERVICE CHECK via BotDetectionService
        // ---------------------------------------------------------------
        $isBot = $this->botDetection->isBot($userAgent, $ip);

        if ($isBot) {
            return response()->json(['success' => true], 200);
        }

        return $next($request);
    }

    /**
     * Fast string-prefix check for known datacenter IP blocks.
     * Covers IPs that are obviously from Tencent Cloud, Alibaba Cloud, etc.
     * Uses simple prefix matching — no CIDR math, guaranteed to work.
     */
    protected function isKnownDatacenterPrefix(string $ip): bool
    {
        // Tencent Cloud: 43.128-183.x.x  (AS132203, AS45090)
        $parts = explode('.', $ip);
        if (count($parts) >= 2 && (int)$parts[0] === 43) {
            $second = (int)$parts[1];
            if ($second >= 128 && $second <= 183) {
                return true;
            }
            // Also 43.152-159 (another Tencent block)
        }

        // Tencent Cloud: 101.32-63.x.x
        if (count($parts) >= 2 && (int)$parts[0] === 101) {
            $second = (int)$parts[1];
            if ($second >= 32 && $second <= 63) {
                return true;
            }
        }

        // Alibaba Cloud: 47.52-245.x.x (main Alibaba range)
        if (count($parts) >= 2 && (int)$parts[0] === 47) {
            $second = (int)$parts[1];
            if ($second >= 52 && $second <= 245) {
                return true;
            }
        }

        // Alibaba HK: 8.210-219.x.x
        if (count($parts) >= 2 && (int)$parts[0] === 8) {
            $second = (int)$parts[1];
            if ($second >= 210 && $second <= 219) {
                return true;
            }
        }

        return false;
    }
}
