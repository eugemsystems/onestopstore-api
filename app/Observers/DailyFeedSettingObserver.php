<?php

namespace App\Observers;

use App\Models\DailyFeedSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DailyFeedSettingObserver
{
    /**
     * Handle the DailyFeedSetting "created" event.
     */
    public function created(DailyFeedSetting $dailyFeedSetting): void
    {
        $this->updateCache();
    }

    /**
     * Handle the DailyFeedSetting "updated" event.
     */
    public function updated(DailyFeedSetting $dailyFeedSetting): void
    {
        $this->updateCache();
    }

    /**
     * Handle the DailyFeedSetting "deleted" event.
     */
    public function deleted(DailyFeedSetting $dailyFeedSetting): void
    {
        $this->updateCache();
    }

    /**
     * Handle the DailyFeedSetting "restored" event.
     */
    public function restored(DailyFeedSetting $dailyFeedSetting): void
    {
        $this->updateCache();
    }

    /**
     * Handle the DailyFeedSetting "force deleted" event.
     */
    public function forceDeleted(DailyFeedSetting $dailyFeedSetting): void
    {
        $this->updateCache();
    }

    /**
     * Clear and refresh daily feed settings cache
     */
    protected function updateCache(): void
    {
        DB::afterCommit(function () {
            Cache::forget('daily_feed_settings');
            // Re-cache immediately
            Cache::rememberForever('daily_feed_settings', function () {
                return DailyFeedSetting::pluck('value', 'key')->toArray();
            });
        });
    }
}
