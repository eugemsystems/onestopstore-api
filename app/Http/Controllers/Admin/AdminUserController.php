<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminUserController extends BaseAdminController
{
    protected string $permissionPrefix = 'user';

    public function index(Request $request)
    {
        $this->checkPermission('index');

        $query = User::with('roles', 'permissions');

        // Search filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($request->has('role') && $request->role !== '') {
            $query->role($request->role);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);
        $roles = Role::all();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $this->checkPermission('create');

        $roles = Role::all();
        $permissions = Permission::all()->groupBy(function($permission) {
            return explode('.', $permission->name)[0];
        });

        return view('admin.users.create', compact('roles', 'permissions'));
    }

    public function store(Request $request)
    {
        $this->checkPermission('create');

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'array',
            'permissions' => 'array',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Assign roles - only if user has permission
        if ($request->has('roles')) {
            if (!auth()->user()->can('user.assign-roles')) {
                return redirect()->back()
                    ->with('error', 'You do not have permission to assign roles to users.');
            }
            $user->assignRole($request->roles);
        }

        // Assign permissions - only if user has permission
        if ($request->has('permissions')) {
            if (!auth()->user()->can('user.assign-permissions')) {
                return redirect()->back()
                    ->with('error', 'You do not have permission to assign permissions to users.');
            }
            $user->givePermissionTo($request->permissions);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully!');
    }

    public function edit($id)
    {
        $this->checkPermission('edit');

        $user = User::with('roles', 'permissions')->findOrFail($id);
        $roles = Role::all();
        $permissions = Permission::all()->groupBy(function($permission) {
            return explode('.', $permission->name)[0];
        });

        $userRoles = $user->roles->pluck('name')->toArray();
        $userPermissions = $user->permissions->pluck('name')->toArray();

        return view('admin.users.edit', compact('user', 'roles', 'permissions', 'userRoles', 'userPermissions'));
    }

    public function update(Request $request, $id)
    {
        $this->checkPermission('edit');

        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'branch' => 'nullable|string|in:None,Harare,Bulawayo,Mutare,Zambia',
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'array',
            'permissions' => 'array',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'branch' => $request->branch ?? 'None',
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        // Sync roles - only if user has permission
        if ($request->has('roles')) {
            if (!auth()->user()->can('user.assign-roles')) {
                return redirect()->back()
                    ->with('error', 'You do not have permission to assign roles to users.');
            }
            $user->syncRoles($request->roles);
        } else {
            // Only clear roles if user has permission to manage roles
            if (auth()->user()->can('user.assign-roles')) {
                $user->syncRoles([]);
            }
        }

        // Sync permissions - only if user has permission
        if ($request->has('permissions')) {
            if (!auth()->user()->can('user.assign-permissions')) {
                return redirect()->back()
                    ->with('error', 'You do not have permission to assign permissions to users.');
            }
            $user->syncPermissions($request->permissions);
        } else {
            // Only clear permissions if user has permission to manage permissions
            if (auth()->user()->can('user.assign-permissions')) {
                $user->syncPermissions([]);
            }
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully!');
    }

    public function destroy($id)
    {
        $this->checkPermission('destroy');

        $user = User::findOrFail($id);

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return redirect()->back()
                ->with('error', 'You cannot delete your own account!');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully!');
    }

    /**
     * Bulk update branch for multiple users
     */
    public function bulkUpdateBranch(Request $request)
    {
        $this->checkPermission('edit');

        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'branch' => 'required|string|in:None,Harare,Bulawayo,Mutare,Zambia',
        ]);

        try {
            $count = User::whereIn('id', $request->user_ids)
                ->update(['branch' => $request->branch]);

            return response()->json([
                'success' => true,
                'message' => "Successfully updated branch to '{$request->branch}' for {$count} user(s).",
                'updated_count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error("Bulk branch update failed", [
                'error' => $e->getMessage(),
                'user_ids' => $request->user_ids,
                'branch' => $request->branch,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update branches: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user addresses
     */
    public function getAddresses($userId)
    {
        $this->checkPermission('edit');

        try {
            $user = User::with(['address.country', 'address.state'])->findOrFail($userId);

            return response()->json([
                'success' => true,
                'addresses' => $user->address,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load addresses: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new address for user
     */
    public function storeAddress(Request $request, $userId)
    {
        $this->checkPermission('edit');

        $request->validate([
            'title' => 'required|string|max:255',
            'street' => 'required|string|max:500',
            'city' => 'required|string|max:255',
            'state_id' => 'nullable|exists:states,id',
            'country_id' => 'required|exists:countries,id',
            'pincode' => 'required|string|max:20',
            'phone' => 'required|string|max:20',
            'is_default' => 'boolean',
        ]);

        try {
            $user = User::findOrFail($userId);

            // If this is set as default, remove default from other addresses
            if ($request->is_default) {
                $user->address()->update(['is_default' => 0]);
            }

            // Get country code
            $country = \App\Models\Country::find($request->country_id);

            $address = $user->address()->create([
                'title' => $request->title,
                'street' => $request->street,
                'city' => $request->city,
                'state_id' => $request->state_id,
                'country_id' => $request->country_id,
                'country_code' => $country->iso_3166_2 ?? null,
                'pincode' => $request->pincode,
                'phone' => $request->phone,
                'is_default' => $request->is_default ? 1 : 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Address added successfully!',
                'address' => $address->load(['country', 'state']),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create user address', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add address: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing address
     */
    public function updateAddress(Request $request, $userId, $addressId)
    {
        $this->checkPermission('edit');

        $request->validate([
            'title' => 'required|string|max:255',
            'street' => 'required|string|max:500',
            'city' => 'required|string|max:255',
            'state_id' => 'nullable|exists:states,id',
            'country_id' => 'required|exists:countries,id',
            'pincode' => 'required|string|max:20',
            'phone' => 'required|string|max:20',
            'is_default' => 'boolean',
        ]);

        try {
            $user = User::findOrFail($userId);
            $address = $user->address()->findOrFail($addressId);

            // If this is set as default, remove default from other addresses
            if ($request->is_default) {
                $user->address()->where('id', '!=', $addressId)->update(['is_default' => 0]);
            }

            // Get country code
            $country = \App\Models\Country::find($request->country_id);

            $address->update([
                'title' => $request->title,
                'street' => $request->street,
                'city' => $request->city,
                'state_id' => $request->state_id,
                'country_id' => $request->country_id,
                'country_code' => $country->iso_3166_2 ?? null,
                'pincode' => $request->pincode,
                'phone' => $request->phone,
                'is_default' => $request->is_default ? 1 : 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Address updated successfully!',
                'address' => $address->fresh(['country', 'state']),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update user address', [
                'user_id' => $userId,
                'address_id' => $addressId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update address: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an address
     */
    public function deleteAddress($userId, $addressId)
    {
        $this->checkPermission('edit');

        try {
            $user = User::findOrFail($userId);
            $address = $user->address()->findOrFail($addressId);

            $address->delete();

            return response()->json([
                'success' => true,
                'message' => 'Address deleted successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete user address', [
                'user_id' => $userId,
                'address_id' => $addressId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete address: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set an address as default
     */
    public function setDefaultAddress($userId, $addressId)
    {
        $this->checkPermission('edit');

        try {
            $user = User::findOrFail($userId);
            $address = $user->address()->findOrFail($addressId);

            // Remove default from all addresses
            $user->address()->update(['is_default' => 0]);

            // Set this address as default
            $address->update(['is_default' => 1]);

            return response()->json([
                'success' => true,
                'message' => 'Default address updated successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to set default address', [
                'user_id' => $userId,
                'address_id' => $addressId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to set default address: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get countries for dropdown
     */
    public function getCountries()
    {
        try {
            $countries = \App\Models\Country::select('id', 'name', 'iso_3166_2 as code')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'countries' => $countries,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load countries: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get states for a country
     */
    public function getStates($countryId)
    {
        try {
            $states = \App\Models\State::where('country_id', $countryId)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'states' => $states,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load states: ' . $e->getMessage(),
            ], 500);
        }
    }
}

