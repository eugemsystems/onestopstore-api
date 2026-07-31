<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CacheUserPermissionsOnLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        try {
            // Cache user permissions on login
            $user = $event->user;

            if ($user && $user->id) {
                getCachedUserPermissions($user->id);
            }
        } catch (\Throwable $e) {
            // Log but don't fail login if caching fails
            Log::error("Failed to cache user permissions on login: " . $e->getMessage());
        }
    }
}
