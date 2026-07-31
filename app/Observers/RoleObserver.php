<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleObserver
{
    /**
     * Handle the Role "created" event.
     */
    public function created(Role $role): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Role "updated" event.
     */
    public function updated(Role $role): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Role "deleted" event.
     */
    public function deleted(Role $role): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Role "restored" event.
     */
    public function restored(Role $role): void
    {
        $this->clearCache();
    }

    /**
     * Handle the Role "force deleted" event.
     */
    public function forceDeleted(Role $role): void
    {
        $this->clearCache();
    }

    /**
     * Clear all role-related caches
     */
    protected function clearCache(): void
    {
        DB::afterCommit(function () {
            // Clear role caches
            Cache::forget('all_roles');
            Cache::forget('role_permissions_map');

            // Clear all user permissions cache since roles changed
            clearAllUserPermissionsCache();

            // Clear Spatie permission cache
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        });
    }
}

