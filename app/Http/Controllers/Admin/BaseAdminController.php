<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class BaseAdminController extends Controller
{
    use AuthorizesRequests;

    /**
     * Permission prefix for this controller
     * Override in child controllers
     */
    protected string $permissionPrefix = '';

    /**
     * Check if user has permission
     */
    protected function checkPermission(string $action): void
    {
        if ($this->permissionPrefix) {
            $permission = "{$this->permissionPrefix}.{$action}";

            if (!auth()->user()->can($permission)) {
                abort(403, "You don't have permission to {$action} {$this->permissionPrefix}");
            }
        }
    }

    /**
     * Check multiple permissions (user needs at least one)
     */
    protected function checkAnyPermission(array $permissions): void
    {
        foreach ($permissions as $permission) {
            if (auth()->user()->can($permission)) {
                return;
            }
        }

        abort(403, "You don't have the required permissions");
    }

    /**
     * Check if user has all permissions
     */
    protected function checkAllPermissions(array $permissions): void
    {
        foreach ($permissions as $permission) {
            if (!auth()->user()->can($permission)) {
                abort(403, "You don't have all required permissions");
            }
        }
    }
}

