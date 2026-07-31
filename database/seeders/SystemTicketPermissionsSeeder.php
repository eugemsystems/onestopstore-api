<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SystemTicketPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'system-ticket.index',
            'system-ticket.create',
            'system-ticket.show',
            'system-ticket.edit',
            'system-ticket.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        $adminRole = Role::where('name', 'admin')->first();
        $developerRole = Role::firstOrCreate(['name' => 'developer']);
        $testerRole = Role::firstOrCreate(['name' => 'tester']);

        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
        }

        if ($developerRole) {
            $developerRole->givePermissionTo($permissions);
        }

        if ($testerRole) {
            $testerRole->givePermissionTo($permissions);
        }

        $this->command->info('System ticket permissions created and assigned successfully!');
    }
}
