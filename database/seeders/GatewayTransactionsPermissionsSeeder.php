<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GatewayTransactionsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'gateway-transactions.index',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign to all admin-level roles
        $adminRoles = ['Admin', 'admin', 'Super Admin', 'superadmin'];
        foreach ($adminRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permissions);
                $this->command->info("Gateway transactions permission assigned to role: {$roleName}");
            }
        }

        $this->command->info('Gateway transaction permissions created successfully!');
    }
}

