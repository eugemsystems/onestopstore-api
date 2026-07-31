<?php

namespace App\Observers;

use App\Models\Attribute;
use Illuminate\Support\Facades\Cache;

class AttributeObserver
{
    /**
     * Handle the Attribute "created" event.
     */
    public function created(Attribute $attribute): void
    {
        $this->clearAttributeCache();
    }

    /**
     * Handle the Attribute "updated" event.
     */
    public function updated(Attribute $attribute): void
    {
        $this->clearAttributeCache();
    }

    /**
     * Handle the Attribute "deleted" event.
     */
    public function deleted(Attribute $attribute): void
    {
        $this->clearAttributeCache();
    }

    /**
     * Handle the Attribute "restored" event.
     */
    public function restored(Attribute $attribute): void
    {
        $this->clearAttributeCache();
    }

    /**
     * Handle the Attribute "force deleted" event.
     */
    public function forceDeleted(Attribute $attribute): void
    {
        $this->clearAttributeCache();
    }

    /**
     * Instead of recaching millions of records, just clear the cache.
     * The cache will be rebuilt on-demand when needed (lazy loading).
     */
    private function clearAttributeCache()
    {
        // Clear specific attribute-related cache keys instead of recaching everything
        Cache::forget('attributes_all');
        Cache::forget('attributes_with_values');

        // Clear cache tags if using tagged cache
        try {
            Cache::tags(['attributes'])->flush();
        } catch (\Exception $e) {
            // Tags not supported by file/database cache drivers, ignore
        }

        // Note: We don't call reCacheAttributes() here because:
        // 1. It's too slow with 2M+ records
        // 2. Most attributes won't be accessed immediately
        // 3. Cache will be rebuilt on-demand when attributes are queried
    }
}
