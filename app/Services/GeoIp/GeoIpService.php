<?php

namespace App\Services\GeoIp;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoIpService
{
    private array $providers;
    private int $timeout;
    private int $cacheTtl;
    private bool $fallbackOnError;

    public function __construct()
    {
        $this->providers = config('analytics.geoip.providers', []);
        $this->timeout = (int) config('analytics.geoip.timeout', 2);
        $this->cacheTtl = (int) config('analytics.geoip.cache_ttl', 604800);
        $this->fallbackOnError = (bool) config('analytics.geoip.fallback_on_error', true);
    }

    public function lookup(?string $ip): array
    {
        if (!$ip || !config('analytics.geoip.enabled')) {
            return $this->defaultLocation();
        }

        if ($this->isNonRoutableIp($ip)) {
            return $this->defaultLocation();
        }

        // Check cache first (long TTL to minimize API calls)
        $cacheKey = "geoip:v2:" . $ip;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Try each provider in sequence
        $enabledProviders = array_filter($this->providers, fn($p) => $p['enabled'] ?? false);

        foreach ($enabledProviders as $provider) {
            // Check rate limit for this provider
            if ($this->isRateLimited($provider)) {
                // Silently continue to next provider - no need to log this
                continue;
            }

            try {
                $result = $this->fetchFromProvider($provider, $ip);

                if ($result['country'] !== null || $result['city'] !== null) {
                    // Cache successful result
                    Cache::put($cacheKey, $result, $this->cacheTtl);

                    // Increment usage counter for this provider
                    $this->incrementProviderUsage($provider);

                    return $result;
                }
            } catch (\Exception $e) {
                // Only log non-rate-limit errors as warnings
                // Rate limit (429) errors are expected and should be silent
//                if (!str_contains($e->getMessage(), '429')) {
//                    Log::warning("[GeoIP] Provider {$provider['name']} failed: {$e->getMessage()}");
//                }
                // Silent continue for 429 errors
                continue;
            }
        }

        // All providers failed - cache default to avoid hammering APIs
        $default = $this->defaultLocation();
        Cache::put($cacheKey, $default, 3600); // cache failures for 1 hour

        return $default;
    }

    private function fetchFromProvider(array $provider, string $ip): array
    {
        $endpoint = rtrim($provider['endpoint'], '/');
        $token = $provider['token'] ?? null;
        $format = $provider['format'] ?? 'ipapi';

        $url = $this->buildUrl($endpoint, $ip, $format, $token);

        $request = Http::timeout($this->timeout)
            ->acceptJson()
            ->withHeaders([
                'User-Agent' => 'RainesAfricaAnalytics/1.0'
            ]);

        // Add auth if token exists
        if ($token && $format !== 'ipstack') {
            $request = $request->withToken($token);
        }

        $response = $request->get($url);

        if ($response->failed()) {
            throw new \Exception("HTTP {$response->status()}");
        }

        return $this->parseResponse($response->json(), $format);
    }

    private function buildUrl(string $endpoint, string $ip, string $format, ?string $token): string
    {
        return match($format) {
            'ipapi' => "$endpoint/$ip/json",
            'ip-api' => "$endpoint/json/$ip",
            'ipgeolocation' => "$endpoint/ipgeo?apiKey=$token&ip=$ip",
            'ipstack' => "$endpoint/$ip?access_key=$token",
            default => "$endpoint/$ip/json",
        };
    }

    private function parseResponse(array $data, string $format): array
    {
        return match($format) {
            'ipapi' => [
                'country' => $data['country_name'] ?? $data['country'] ?? null,
                'city' => $data['city'] ?? null,
            ],
            'ip-api' => [
                'country' => $data['country'] ?? null,
                'city' => $data['city'] ?? null,
            ],
            'ipgeolocation' => [
                'country' => $data['country_name'] ?? null,
                'city' => $data['city'] ?? null,
            ],
            'ipstack' => [
                'country' => $data['country_name'] ?? null,
                'city' => $data['city'] ?? null,
            ],
            default => [
                'country' => $data['country_name'] ?? $data['country'] ?? null,
                'city' => $data['city'] ?? null,
            ],
        };
    }

    private function isRateLimited(array $provider): bool
    {
        $dailyLimit = $provider['daily_limit'] ?? 1000;
        $counterKey = "geoip:rate:{$provider['name']}:" . now()->format('Y-m-d');

        $count = (int) Cache::get($counterKey, 0);
        return $count >= $dailyLimit;
    }

    private function incrementProviderUsage(array $provider): void
    {
        $counterKey = "geoip:rate:{$provider['name']}:" . now()->format('Y-m-d');
        $currentCount = (int) Cache::get($counterKey, 0);

        // Store counter with TTL until end of day + 1 hour
        $ttl = now()->endOfDay()->addHour()->diffInSeconds(now());
        Cache::put($counterKey, $currentCount + 1, $ttl);
    }

    private function isNonRoutableIp(string $ip): bool
    {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    private function defaultLocation(): array
    {
        return ['country' => 'Unknown', 'city' => 'Unknown'];
    }
}
