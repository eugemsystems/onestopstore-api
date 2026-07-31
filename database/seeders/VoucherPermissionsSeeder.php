<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class VoucherPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => 'vouchers.index', 'guard_name' => 'web'],
            ['name' => 'vouchers.show', 'guard_name' => 'web'],
            ['name' => 'vouchers.create', 'guard_name' => 'web'],
            ['name' => 'vouchers.update', 'guard_name' => 'web'],
            ['name' => 'vouchers.delete', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }

        $this->command->info('Voucher permissions created successfully!');
    }
}

