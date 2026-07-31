<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\GeoIp\GeoIpService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeoIpServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_returns_unknown_for_null_ip()
    {
        $service = app(GeoIpService::class);
        $result = $service->lookup(null);

        $this->assertEquals('Unknown', $result['country']);
        $this->assertEquals('Unknown', $result['city']);
    }

    public function test_returns_unknown_for_private_ip()
    {
        $service = app(GeoIpService::class);
        $result = $service->lookup('192.168.1.1');

        $this->assertEquals('Unknown', $result['country']);
        $this->assertEquals('Unknown', $result['city']);
    }

    public function test_caches_successful_lookups()
    {
        Http::fake([
            'ipapi.co/*' => Http::response(['country_name' => 'United States', 'city' => 'Mountain View'], 200),
        ]);

        $service = app(GeoIpService::class);

        // First call
        $result1 = $service->lookup('8.8.8.8');
        $this->assertEquals('United States', $result1['country']);

        // Second call should use cache
        Http::fake([
            'ipapi.co/*' => Http::response([], 500),
        ]);

        $result2 = $service->lookup('8.8.8.8');
        $this->assertEquals('United States', $result2['country']); // Still from cache
    }

    public function test_falls_back_to_next_provider_on_failure()
    {
        Http::fake([
            'ipapi.co/*' => Http::response([], 429), // Rate limited
            'ip-api.com/*' => Http::response(['country' => 'United States', 'city' => 'Mountain View'], 200),
        ]);

        $service = app(GeoIpService::class);
        $result = $service->lookup('8.8.8.8');

        $this->assertEquals('United States', $result['country']);
        $this->assertEquals('Mountain View', $result['city']);
    }

    public function test_respects_rate_limits()
    {
        $counterKey = "geoip:rate:ipapi:" . now()->format('Y-m-d');
        Cache::put($counterKey, 1000, 86400); // Set to limit

        Http::fake([
            'ip-api.com/*' => Http::response(['country' => 'United States', 'city' => 'Mountain View'], 200),
        ]);

        $service = app(GeoIpService::class);
        $result = $service->lookup('8.8.8.8');

        // Should skip ipapi.co and use ip-api.com
        $this->assertEquals('United States', $result['country']);
    }

    public function test_returns_unknown_when_all_providers_fail()
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $service = app(GeoIpService::class);
        $result = $service->lookup('8.8.8.8');

        $this->assertEquals('Unknown', $result['country']);
        $this->assertEquals('Unknown', $result['city']);
    }
}

