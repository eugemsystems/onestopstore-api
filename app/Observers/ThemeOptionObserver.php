<?php

namespace App\Observers;

use App\Models\ThemeOption;
use App\Services\ApiCacheRefresher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ThemeOptionObserver
{
    /**
     * Handle the ThemeOption "created" event.
     */
    public function created(ThemeOption $themeOption): void
    {
        $this->updateModel();
    }

    /**
     * Handle the ThemeOption "updated" event.
     */
    public function updated(ThemeOption $themeOption): void
    {
        $this->updateModel();
    }

    /**
     * Handle the ThemeOption "deleted" event.
     */
    public function deleted(ThemeOption $themeOption): void
    {
        $this->updateModel();
    }

    /**
     * Handle the ThemeOption "restored" event.
     */
    public function restored(ThemeOption $themeOption): void
    {
        $this->updateModel();
    }

    /**
     * Handle the ThemeOption "force deleted" event.
     */
    public function forceDeleted(ThemeOption $themeOption): void
    {
        $this->updateModel();
    }

    protected function updateModel(): void
    {
        DB::afterCommit(function () {
            reGenerateThemeOptions();
            // Recache Nginx for theme options endpoints
            ApiCacheRefresher::refreshThemeOptions();
        });
    }
}
