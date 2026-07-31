<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\ApiCacheRefresher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CategoryObserver
{
    /**
     * Handle the Category "created" event.
     */
    public function created(Category $category): void
    {
        $this->updateModel();
    }

    /**
     * Handle the Category "updated" event.
     */
    public function updated(Category $category): void
    {
        $this->updateModel();
    }

    /**
     * Handle the Category "deleted" event.
     */
    public function deleted(Category $category): void
    {
        $this->updateModel();
    }

    /**
     * Handle the Category "restored" event.
     */
    public function restored(Category $category): void
    {
        $this->updateModel();
    }

    /**
     * Handle the Category "force deleted" event.
     */
    public function forceDeleted(Category $category): void
    {
        $this->updateModel();
    }

    protected function updateModel(): void
    {
        DB::afterCommit(function () {
            // Use cache lock to prevent multiple simultaneous cache rebuilds
            $lock = Cache::lock('category_cache_rebuild', 10);

            if ($lock->get()) {
                try {
                    // Clear standard categories cache
                    Cache::forget('categories');
                    Cache::rememberForever('categories', function () {
                        return Category::all();
                    });

                    // Clear hierarchical tree cache (will rebuild on next request)
                    Cache::forget('categories_hierarchical_tree');

                    // Clear categories cache used in product edit page
                    Cache::forget('categories_with_subcategories_active');

                    // Try to rebuild in background, but don't block if it fails
                    try {
                        \Artisan::call('categories:cache-tree');
                    } catch (\Exception $e) {
                        \Log::warning('Categories tree rebuild skipped (will rebuild on next request): ' . $e->getMessage());
                    }

                    // Recache Nginx for category-related endpoints
                    try {
                        ApiCacheRefresher::refreshCategories();
                    } catch (\Exception $e) {
                        \Log::warning('ApiCacheRefresher failed: ' . $e->getMessage());
                    }
                } finally {
                    $lock->release();
                }
            }
        });
    }
}
