<?php

namespace App\Observers;

use App\Models\State;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StateObserver
{
    /**
     * Handle the State "created" event.
     */
    public function created(State $state): void
    {
        $this->clearCache($state);
    }

    /**
     * Handle the State "updated" event.
     */
    public function updated(State $state): void
    {
        $this->clearCache($state);
    }

    /**
     * Handle the State "deleted" event.
     */
    public function deleted(State $state): void
    {
        $this->clearCache($state);
    }

    /**
     * Handle the State "restored" event.
     */
    public function restored(State $state): void
    {
        $this->clearCache($state);
    }

    /**
     * Handle the State "force deleted" event.
     */
    public function forceDeleted(State $state): void
    {
        $this->clearCache($state);
    }

    /**
     * Clear all state-related caches
     */
    protected function clearCache(State $state): void
    {
        DB::afterCommit(function () use ($state) {
            // Clear all states cache
            regenerateCachedStates();

            // Clear country-specific state cache
            if ($state->country_id) {
                Cache::forget("states_country_{$state->country_id}");
            }
        });
    }
}

