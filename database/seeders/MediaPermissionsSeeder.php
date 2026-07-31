<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MediaPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create media permissions
        $permissions = [
            'media.index',
            'media.show',
            'media.create',
            'media.edit',
            'media.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign to Admin role
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
            $this->command->info('Media permissions assigned to Admin role');
        }

        // Optionally assign to Vendor role (limited access)
        $vendorRole = Role::where('name', 'Vendor')->first();
        if ($vendorRole) {
            $vendorRole->givePermissionTo([
                'media.index',
                'media.show',
                'media.create'
            ]);
            $this->command->info('Limited media permissions assigned to Vendor role');
        }

        $this->command->info('Media permissions created successfully!');
    }
}

