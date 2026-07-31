<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CurrencyPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'currency.index',
            'currency.create',
            'currency.edit',
            'currency.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach (Role::all() as $role) {
            if (stripos($role->name, 'admin') !== false) {
                $role->givePermissionTo($permissions);
                $this->command->info("Currency permissions assigned to: {$role->name}");
            }
        }

        $this->command->info('Currency permissions created successfully!');
    }
}

