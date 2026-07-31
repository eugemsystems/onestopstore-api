<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Brand;

class TestBrandCache extends Command
{
    protected $signature = 'test:brand-cache';
    protected $description = 'Test if brand caching is working';

    public function handle()
    {
        $this->info('=== Testing Brand Cache ===');

        // Clear cache first
        Cache::forget('brands_list_status_1');
        $this->info('Cache cleared');

        // First call - should hit DB
        $this->info('First call (should hit DB)...');
        $startTime1 = microtime(true);

        $brands1 = Cache::remember('brands_list_status_1', 600, function () {
            $this->info('  -> Cache MISS - Querying database');
            return Brand::with('brand_image')->where('status', 1)->orderBy('name', 'asc')->get();
        });

        $time1 = round((microtime(true) - $startTime1) * 1000, 2);
        $this->info("  -> Time: {$time1}ms");
        $this->info("  -> Count: {$brands1->count()} brands");

        // Second call - should hit cache
        $this->info('Second call (should hit cache)...');
        $startTime2 = microtime(true);

        $brands2 = Cache::remember('brands_list_status_1', 600, function () {
            $this->warn('  -> ERROR: Cache MISS (should have been cached!)');
            return Brand::with('brand_image')->where('status', 1)->orderBy('name', 'asc')->get();
        });

        $time2 = round((microtime(true) - $startTime2) * 1000, 2);
        $this->info("  -> Time: {$time2}ms");
        $this->info("  -> Count: {$brands2->count()} brands");

        // Check if cached
        if (Cache::has('brands_list_status_1')) {
            $this->info('✅ Cache exists!');
        } else {
            $this->error('❌ Cache does NOT exist!');
        }

        // Performance comparison
        $this->info('=== Performance ===');
        $this->info("First call:  {$time1}ms (DB query)");
        $this->info("Second call: {$time2}ms (cached)");

        if ($time2 < $time1 / 2) {
            $this->info('✅ Cache is working! Second call is significantly faster.');
        } else {
            $this->warn('⚠️  Cache might not be working properly.');
        }

        // Check cache driver
        $driver = config('cache.default');
        $this->info("Cache Driver: {$driver}");

        return 0;
    }
}

