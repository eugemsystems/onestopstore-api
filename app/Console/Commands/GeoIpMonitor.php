<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GeoIp\GeoIpService;
use Illuminate\Support\Facades\Cache;

class GeoIpMonitor extends Command
{
    protected $signature = 'analytics:geoip-monitor';
    protected $description = 'Monitor GeoIP fallback system health and usage';

    public function handle()
    {
        $this->newLine();
        $this->line('=== <fg=cyan>GeoIP System Monitor</> ===');
        $this->newLine();

        // 1. Check today's rate limits
        $this->checkRateLimits();

        // 2. Test lookups
        $this->testLookups();

        // 3. Cache statistics
        $this->cacheStatistics();

        // 4. Configuration
        $this->showConfiguration();

        // 5. Recommendations
        $this->showRecommendations();

        $this->newLine();
        return 0;
    }

    private function checkRateLimits()
    {
        $today = now()->format('Y-m-d');

        $this->info('📊 Rate Limit Usage (Today: ' . $today . ')');
        $this->line(str_repeat('-', 50));

        $providers = config('analytics.geoip.providers', []);

        foreach ($providers as $provider) {
            $name = $provider['name'];
            $key = "geoip:rate:$name:$today";
            $count = Cache::get($key, 0);
            $limit = $provider['daily_limit'] ?? 0;
            $enabled = $provider['enabled'] ?? false;

            $percentage = $limit > 0 ? round(($count / $limit) * 100, 1) : 0;
            $status = $enabled ? '<fg=green>✅</>' : '<fg=red>❌</>';

            $color = $percentage < 50 ? 'green' : ($percentage < 80 ? 'yellow' : 'red');

            $this->line(sprintf(
                "%s %-15s: %5d / %5d (%5.1f%%) <fg=$color>%s</>",
                $status,
                $name,
                $count,
                $limit,
                $percentage,
                str_repeat('█', min(20, (int)($percentage / 5)))
            ));
        }

        $this->newLine();
    }

    private function testLookups()
    {
        $this->info('🌍 Test Lookups');
        $this->line(str_repeat('-', 50));

        $testIps = [
            ['8.8.8.8', 'Google DNS'],
            ['1.1.1.1', 'Cloudflare DNS'],
            ['192.168.1.1', 'Private IP'],
        ];

        $service = app(GeoIpService::class);

        foreach ($testIps as [$ip, $description]) {
            $result = $service->lookup($ip);
            $country = $result['country'] ?? 'null';
            $city = $result['city'] ?? 'null';

            $color = $country === 'Unknown' ? 'yellow' : 'green';

            $this->line(sprintf(
                "%-15s <fg=gray>(%s)</>",
                $ip,
                $description
            ));
            $this->line(sprintf(
                "  → <fg=$color>Country: %s, City: %s</>",
                $country,
                $city
            ));
        }

        $this->newLine();
    }

    private function cacheStatistics()
    {
        $this->info('💾 Cache Statistics');
        $this->line(str_repeat('-', 50));

        try {
            $redis = Cache::getRedis();
            $cacheKeys = $redis->keys('*geoip:v2:*');
            $cacheCount = is_array($cacheKeys) ? count($cacheKeys) : 0;
        } catch (\Exception $e) {
            $cacheCount = 0;
        }

        $this->line("Total cached IPs: <fg=cyan>" . number_format($cacheCount) . "</>");

        $ttl = config('analytics.geoip.cache_ttl');
        $days = round($ttl / 86400, 1);
        $this->line("Cache TTL: <fg=cyan>$ttl seconds ($days days)</>");

        if ($cacheCount > 0 && isset($cacheKeys)) {
            $this->newLine();
            $this->line("Sample cache entries:");
            $sample = array_slice($cacheKeys, 0, 5);
            foreach ($sample as $key) {
                $ip = str_replace('laravel_database_geoip:v2:', '', $key);
                $ip = str_replace('geoip:v2:', '', $ip);
                $data = Cache::get(str_replace('laravel_database_', '', $key));
                if ($data) {
                    $this->line("  <fg=gray>$ip</> → {$data['country']}, {$data['city']}");
                }
            }
        }

        $this->newLine();
    }

    private function showConfiguration()
    {
        $this->info('⚙️  Configuration');
        $this->line(str_repeat('-', 50));

        $enabled = config('analytics.geoip.enabled') ? '<fg=green>Yes</>' : '<fg=red>No</>';
        $this->line("Enabled: $enabled");

        $timeout = config('analytics.geoip.timeout');
        $this->line("Timeout: <fg=cyan>$timeout seconds</>");

        $fallback = config('analytics.geoip.fallback_on_error') ? '<fg=green>Yes</>' : '<fg=red>No</>';
        $this->line("Fallback on error: $fallback");

        $providers = collect(config('analytics.geoip.providers'))
            ->where('enabled', true)
            ->pluck('name')
            ->implode(', ');
        $this->line("Enabled providers: <fg=cyan>$providers</>");

        $this->newLine();
    }

    private function showRecommendations()
    {
        $this->info('💡 Recommendations');
        $this->line(str_repeat('-', 50));

        $totalLimit = collect(config('analytics.geoip.providers'))
            ->where('enabled', true)
            ->sum('daily_limit');

        $this->line("Total daily capacity: <fg=cyan>" . number_format($totalLimit) . " requests</>");

        $today = now()->format('Y-m-d');
        $totalUsed = collect(config('analytics.geoip.providers'))
            ->where('enabled', true)
            ->sum(function($provider) use ($today) {
                return Cache::get("geoip:rate:{$provider['name']}:$today", 0);
            });

        $usagePercent = $totalLimit > 0 ? round(($totalUsed / $totalLimit) * 100, 1) : 0;

        if ($usagePercent > 80) {
            $this->warn("⚠️  High API usage ($usagePercent%) - consider enabling more providers");
        } else {
            $this->line("<fg=green>✅ API usage is healthy ($usagePercent%)</>");
        }

        // Check if additional providers are available
        $disabledProviders = collect(config('analytics.geoip.providers'))
            ->where('enabled', false);

        if ($disabledProviders->count() > 0) {
            $names = $disabledProviders->pluck('name')->implode(', ');
            $this->line("<fg=yellow>💡 Consider enabling: $names</>");
        }
    }
}

