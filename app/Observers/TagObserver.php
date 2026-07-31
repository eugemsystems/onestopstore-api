<?php

namespace App\Observers;

use App\Models\Tag;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TagObserver
{
    /**
     * Handle the Tag "created" event.
     */
    public function created(Tag $tag): void
    {
        $this->clearTagCache($tag);
    }

    /**
     * Handle the Tag "updated" event.
     */
    public function updated(Tag $tag): void
    {
        $this->clearTagCache($tag);
    }

    /**
     * Handle the Tag "deleted" event.
     */
    public function deleted(Tag $tag): void
    {
        $this->clearTagCache($tag);
    }

    /**
     * Handle the Tag "restored" event.
     */
    public function restored(Tag $tag): void
    {
        $this->clearTagCache($tag);
    }

    /**
     * Handle the Tag "force deleted" event.
     */
    public function forceDeleted(Tag $tag): void
    {
        $this->clearTagCache($tag);
    }

    /**
     * Clear tag-related caches
     */
    protected function clearTagCache(Tag $tag): void
    {
        DB::afterCommit(function () use ($tag) {
            try {
                // Clear individual tag cache
                Cache::forget("tag_{$tag->id}");
                Cache::forget("tag_slug_{$tag->slug}");

                // Clear active tags cache (used in product edit)
                Cache::forget('active_tags');

                // Clear product lists that might include this tag
                Cache::forget('products_list');
                Cache::forget('featured_products');
                Cache::forget('trending_products');

                // Bump products cache version to invalidate tag-filtered product lists
                $currentVersion = Cache::get('products_cache_version', 1);
                Cache::put('products_cache_version', $currentVersion + 1, now()->addDays(365));


            } catch (\Exception $e) {
                Log::warning("Failed to clear cache for tag {$tag->id}: " . $e->getMessage());
            }
        });
    }
}

