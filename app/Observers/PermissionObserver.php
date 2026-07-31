<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class PermissionObserver
{
    /**
     * Handle the Permission "created" event.
     */
    public function created(Permission $permission): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Permission "updated" event.
     */
    public function updated(Permission $permission): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Permission "deleted" event.
     */
    public function deleted(Permission $permission): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Permission "restored" event.
     */
    public function restored(Permission $permission): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Permission "force deleted" event.
     */
    public function forceDeleted(Permission $permission): void
    {
        $this->clearCache();
    }

    /**
     * Clear all permission-related caches
     */
    protected function clearCache(): void
    {
        DB::afterCommit(function () {
            // Clear permission caches
            Cache::forget('all_permissions');
            Cache::forget('role_permissions_map');

            // Clear all user permissions cache since permissions changed
            clearAllUserPermissionsCache();

            // Clear Spatie permission cache
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        });
    }
}

