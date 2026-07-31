<?php

namespace App\Observers;

use App\Models\Tax;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TaxObserver
{
    /**
     * Handle the Tax "created" event.
     */
    public function created(Tax $tax): void
    {
        $this->clearTaxCache($tax);
    }

    /**
     * Handle the Tax "updated" event.
     */
    public function updated(Tax $tax): void
    {
        $this->clearTaxCache($tax);
    }

    /**
     * Handle the Tax "deleted" event.
     */
    public function deleted(Tax $tax): void
    {
        $this->clearTaxCache($tax);
    }

    /**
     * Handle the Tax "restored" event.
     */
    public function restored(Tax $tax): void
    {
        $this->clearTaxCache($tax);
    }

    /**
     * Handle the Tax "force deleted" event.
     */
    public function forceDeleted(Tax $tax): void
    {
        $this->clearTaxCache($tax);
    }

    /**
     * Clear tax-related caches
     */
    protected function clearTaxCache(Tax $tax): void
    {
        DB::afterCommit(function () use ($tax) {
            try {
                // Clear individual tax cache
                Cache::forget("tax_{$tax->id}");

                // Clear active taxes cache (used in product edit)
                Cache::forget('active_taxes');



            } catch (\Exception $e) {
                Log::warning("Failed to clear cache for tax {$tax->id}: " . $e->getMessage());
            }
        });
    }
}

