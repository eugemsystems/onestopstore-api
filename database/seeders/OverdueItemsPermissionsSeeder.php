<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class OverdueItemsPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions for overdue items (order products ETA)
        $permissions = [
            ['name' => 'overdue_items.index', 'guard_name' => 'web'],
            ['name' => 'overdue_items.export', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }

        // Assign permissions to Admin role by default
        $adminRole = Role::where('name', RoleEnum::ADMIN)->first();
        if ($adminRole) {
            $adminRole->givePermissionTo([
                'overdue_items.index',
                'overdue_items.export',
            ]);
        }

        $this->command->info('Overdue Items permissions created and assigned successfully!');
    }
}

