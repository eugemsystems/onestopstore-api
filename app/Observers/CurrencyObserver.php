<?php

namespace App\Observers;

use App\Models\Currency;
use App\Services\ApiCacheRefresher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CurrencyObserver
{
    /**
     * Handle the Currency "created" event.
     */
    public function created(Currency $currency): void
    {
        $this->updateCache();
    }

    /**
     * Handle the Currency "updated" event.
     */
    public function updated(Currency $currency): void
    {
        $this->updateCache();
    }

    /**
     * Handle the Currency "deleted" event.
     */
    public function deleted(Currency $currency): void
    {
        $this->updateCache();
    }

    /**
     * Handle the Currency "restored" event.
     */
    public function restored(Currency $currency): void
    {
        $this->updateCache();
    }

    /**
     * Handle the Currency "force deleted" event.
     */
    public function forceDeleted(Currency $currency): void
    {
        $this->updateCache();
    }

    protected function updateCache(): void
    {
        DB::afterCommit(function () {
            // Regenerate all currency caches
            regenerateCachedCurrencies();
            regenerateCachedActiveCurrencies();

            // Recache Nginx for currencies endpoints
            ApiCacheRefresher::refreshCurrencies();
        });
    }
}
