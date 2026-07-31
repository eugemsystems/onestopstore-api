<?php

namespace App\Observers;

use App\Models\Brand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BrandObserver
{
    /**
     * Handle the Brand "created" event.
     */
    public function created(Brand $brand): void
    {
        $this->clearBrandCache();
    }

    /**
     * Handle the Brand "updated" event.
     */
    public function updated(Brand $brand): void
    {
        $this->clearBrandCache();

        // Also clear specific brand cache
        Cache::forget("brand_{$brand->id}");
    }

    /**
     * Handle the Brand "deleted" event.
     */
    public function deleted(Brand $brand): void
    {
        $this->clearBrandCache();
        Cache::forget("brand_{$brand->id}");
    }

    /**
     * Handle the Brand "restored" event.
     */
    public function restored(Brand $brand): void
    {
        $this->clearBrandCache();
    }

    /**
     * Clear all brand list caches (including paginated caches)
     */
    private function clearBrandCache(): void
    {
        try {
            // Clear old non-paginated caches
            Cache::forget('brands_list_status_1');
            Cache::forget('brands_list_status_0');

            // Clear 'all' brands cache
            Cache::forget('brands_list_status_1_all');
            Cache::forget('brands_list_status_0_all');

            // Clear paginated caches - clear common pagination scenarios
            // Status 1 (active) - pages 1-20, per_page 50, 100
            for ($page = 1; $page <= 20; $page++) {
                Cache::forget("brands_list_status_1_page_{$page}_per_50");
                Cache::forget("brands_list_status_1_page_{$page}_per_100");
                Cache::forget("brands_list_status_0_page_{$page}_per_50");
                Cache::forget("brands_list_status_0_page_{$page}_per_100");
            }

        } catch (\Exception $e) {
            Log::error('Failed to clear brand cache: ' . $e->getMessage());
        }
    }
}

