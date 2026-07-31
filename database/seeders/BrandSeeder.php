<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;
use Illuminate\Support\Facades\DB;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            ['name' => 'Samsung', 'slug' => 'samsung', 'description' => 'Leading electronics and technology brand', 'status' => 1],
            ['name' => 'Apple', 'slug' => 'apple', 'description' => 'Premium technology and consumer electronics', 'status' => 1],
            ['name' => 'Sony', 'slug' => 'sony', 'description' => 'Electronics, gaming, and entertainment', 'status' => 1],
            ['name' => 'LG', 'slug' => 'lg', 'description' => 'Home appliances and electronics', 'status' => 1],
            ['name' => 'Nike', 'slug' => 'nike', 'description' => 'Sports apparel and footwear', 'status' => 1],
            ['name' => 'Adidas', 'slug' => 'adidas', 'description' => 'Athletic wear and sports equipment', 'status' => 1],
            ['name' => 'HP', 'slug' => 'hp', 'description' => 'Computers and printing solutions', 'status' => 1],
            ['name' => 'Dell', 'slug' => 'dell', 'description' => 'Computer hardware and technology', 'status' => 1],
            ['name' => 'Canon', 'slug' => 'canon', 'description' => 'Cameras and imaging products', 'status' => 1],
            ['name' => 'Nikon', 'slug' => 'nikon', 'description' => 'Photography and optics', 'status' => 1],
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(
                ['slug' => $brand['slug']],
                $brand
            );
        }

        $this->command->info('Brands seeded successfully!');
    }
}

