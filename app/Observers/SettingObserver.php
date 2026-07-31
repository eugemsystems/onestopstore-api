<?php

namespace App\Observers;

use App\Models\Setting;
use App\Services\ApiCacheRefresher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingObserver
{
    /**
     * Handle the Setting "created" event.
     */
    public function created(Setting $setting): void
    {
        $this->updateModel();
    }

    /**
     * Handle the Setting "updated" event.
     */
    public function updated(Setting $setting): void
    {
        $this->updateModel();
    }

    /**
     * Handle the Setting "deleted" event.
     */
    public function deleted(Setting $setting): void
    {
        $this->updateModel();
    }

    /**
     * Handle the Setting "restored" event.
     */
    public function restored(Setting $setting): void
    {
        $this->updateModel();
    }

    /**
     * Handle the Setting "force deleted" event.
     */
    public function forceDeleted(Setting $setting): void
    {
        $this->updateModel();
    }

    protected function updateModel(): void
    {
        DB::afterCommit(function () {
            Cache::forget('settings');
            Cache::rememberForever('settings', function () {
                return Setting::all();
            });

            // Recache Nginx for settings endpoints
            ApiCacheRefresher::refreshSettings();
        });
    }
}
