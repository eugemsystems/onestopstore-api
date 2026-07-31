<?php

namespace App\Observers;

use App\Models\LaybySetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LaybySettingObserver
{
    /**
     * Handle the LaybySetting "created" event.
     */
    public function created(LaybySetting $laybySetting): void
    {
        $this->updateCache();
    }

    /**
     * Handle the LaybySetting "updated" event.
     */
    public function updated(LaybySetting $laybySetting): void
    {
        $this->updateCache();
    }

    /**
     * Handle the LaybySetting "deleted" event.
     */
    public function deleted(LaybySetting $laybySetting): void
    {
        $this->updateCache();
    }

    /**
     * Handle the LaybySetting "restored" event.
     */
    public function restored(LaybySetting $laybySetting): void
    {
        $this->updateCache();
    }

    /**
     * Handle the LaybySetting "force deleted" event.
     */
    public function forceDeleted(LaybySetting $laybySetting): void
    {
        $this->updateCache();
    }

    /**
     * Clear and refresh layby settings cache
     */
    protected function updateCache(): void
    {
        DB::afterCommit(function () {
            Cache::forget('layby_settings');
            // Re-cache immediately
            Cache::rememberForever('layby_settings', function () {
                return LaybySetting::pluck('value', 'key')->toArray();
            });
        });
    }
}

