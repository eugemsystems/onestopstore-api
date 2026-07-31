<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuctionAnalyticsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::firstOrCreate([
            'name'       => 'auction-analytics.view',
            'guard_name' => 'web',
        ]);

        // Automatically assign to admin role if it exists
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($adminRole && !$adminRole->hasPermissionTo($permission)) {
            $adminRole->givePermissionTo($permission);
        }

        $this->command->info('Auction analytics permission created and assigned to admin.');
    }
}
