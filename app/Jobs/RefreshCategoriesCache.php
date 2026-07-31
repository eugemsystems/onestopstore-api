<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Category;

class RefreshCategoriesCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Rebuild categories cache
            Cache::rememberForever('categories', function () {
                return Category::all();
            });

            // Rebuild hierarchical tree cache - OPTIMIZED
            // Call command directly instead of Artisan::call for better performance
            $command = new \App\Console\Commands\CacheCategoriesTree();
            $command->handle();
        } catch (\Exception $e) {
            Log::error('RefreshCategoriesCache: Failed to rebuild categories cache', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Don't throw - we don't want this to fail the main operation
        }
    }
}

