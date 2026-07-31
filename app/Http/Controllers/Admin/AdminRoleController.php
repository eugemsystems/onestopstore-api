<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminRoleController extends BaseAdminController
{
    protected string $permissionPrefix = 'role';

    public function index()
    {
        $this->checkPermission('index');

        $roles = Role::withCount('users', 'permissions')->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $this->checkPermission('create');

        $permissions = Permission::all()->groupBy(function($permission) {
            return explode('.', $permission->name)[0];
        });

        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $this->checkPermission('create');

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
        ]);

        $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);

        if ($request->has('permissions')) {
            $role->givePermissionTo($request->permissions);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role created successfully!');
    }

    public function edit($id)
    {
        $this->checkPermission('edit');

        $role = Role::with('permissions')->findOrFail($id);
        $permissions = Permission::all()->groupBy(function($permission) {
            return explode('.', $permission->name)[0];
        });
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, $id)
    {
        $this->checkPermission('edit');

        $role = Role::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'permissions' => 'array',
        ]);

        $role->update(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        } else {
            $role->syncPermissions([]);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role updated successfully!');
    }

    public function destroy($id)
    {
        $this->checkPermission('destroy');

        $role = Role::findOrFail($id);

        // Prevent deletion of admin role
        if ($role->name === 'admin') {
            return redirect()->back()
                ->with('error', 'Cannot delete admin role!');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted successfully!');
    }
}

