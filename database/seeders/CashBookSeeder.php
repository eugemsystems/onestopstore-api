<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\CashBookCategory;

class CashBookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'cashbook.index' => 'View cash book page',
            'cashbook.show' => 'View cash book entries and statistics',
            'cashbook.create' => 'Create cash book entries and import CSV',
            'cashbook.edit' => 'Edit cash book entries',
            'cashbook.delete' => 'Delete cash book entries',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );
        }

        $this->command->info('Cash book permissions created successfully.');

        // Create default categories
        $categories = [
            ['name' => 'Sale', 'slug' => 'sale', 'type' => 'income', 'color' => '#10B981'],
            ['name' => 'Purchase', 'slug' => 'purchase', 'type' => 'expense', 'color' => '#EF4444'],
            ['name' => 'Rent', 'slug' => 'rent', 'type' => 'expense', 'color' => '#F59E0B'],
            ['name' => 'Salary', 'slug' => 'salary', 'type' => 'expense', 'color' => '#8B5CF6'],
            ['name' => 'Utilities', 'slug' => 'utilities', 'type' => 'expense', 'color' => '#3B82F6'],
            ['name' => 'Transport', 'slug' => 'transport', 'type' => 'expense', 'color' => '#EC4899'],
            ['name' => 'Marketing', 'slug' => 'marketing', 'type' => 'expense', 'color' => '#14B8A6'],
            ['name' => 'Inventory', 'slug' => 'inventory', 'type' => 'expense', 'color' => '#F97316'],
            ['name' => 'Miscellaneous', 'slug' => 'miscellaneous', 'type' => 'both', 'color' => '#6B7280'],
        ];

        foreach ($categories as $category) {
            CashBookCategory::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        $this->command->info('Default cash book categories created successfully.');
    }
}
