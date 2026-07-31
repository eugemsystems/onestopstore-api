<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogMobileRequests
{
    private const SLOW_THRESHOLD_SECONDS = 2.0;

    public function handle(Request $request, Closure $next): Response
    {
        $startTime   = microtime(true);
        $startMemory = memory_get_usage(true);

        $response = $next($request);

        $duration = microtime(true) - $startTime;
        $source   = $this->detectSource($request->userAgent() ?? '');
        $isSlow   = $duration >= self::SLOW_THRESHOLD_SECONDS;
        $isMobile = $source === 'mobile';

        if ($isSlow || $isMobile) {
            $path   = $request->path();
            $method = $request->method();
            $status = $response->getStatusCode();
            $memMB  = round((memory_get_usage(true) - $startMemory) / 1048576, 2);
            $peakMB = round(memory_get_peak_usage(true) / 1048576, 2);
            $ua     = substr($request->userAgent() ?? '', 0, 120);
            $ip     = $request->ip();

            $context = [
                'method'   => $method,
                'path'     => $path,
                'query'    => $this->safeQuery($request),
                'status'   => $status,
                'duration' => round($duration, 3),
                'mem_mb'   => $memMB,
                'peak_mb'  => $peakMB,
                'source'   => $source,
                'ip'       => $ip,
                'ua'       => $ua,
            ];

            if ($isSlow) {
                Log::channel('stack')->warning("SLOW_REQUEST [{$source}] {$method} /{$path} {$status} " . round($duration, 2) . "s", $context);
            } else {
                Log::channel('stack')->info("MOBILE_REQUEST {$method} /{$path} {$status} " . round($duration, 2) . "s", $context);
            }
        }

        return $response;
    }

    private function detectSource(string $ua): string
    {
        if ($ua === '') return 'unknown';

        // Mobile/native app signals
        if (preg_match('/okhttp|Dart\/|Flutter|ReactNative|CFNetwork.*Darwin|expo/i', $ua)) {
            return 'mobile';
        }

        // Next.js SSR / node fetchers
        if (preg_match('/Next\.js|node-fetch|undici|axios\/|got\//i', $ua)) {
            return 'ssr';
        }

        // Crawlers
        if (preg_match('/bot|crawler|spider|slurp|bingpreview/i', $ua)) {
            return 'bot';
        }

        if (preg_match('/Mozilla|Chrome|Safari|Firefox|Opera/i', $ua)) {
            return 'browser';
        }

        return 'unknown';
    }

    private function safeQuery(Request $request): string
    {
        $q = $request->query->all();
        unset($q['password'], $q['token'], $q['api_key']);
        $str = http_build_query($q);
        return strlen($str) > 200 ? substr($str, 0, 200) . '...' : $str;
    }
}
