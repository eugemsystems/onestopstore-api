<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PromoTemplate;

class PromoTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templateHtml = file_get_contents(base_path('template.html'));

        PromoTemplate::create([
            'name' => 'Raines Africa Summer Promo',
            'description' => 'A professional promo flyer template with 12 product slots, perfect for Facebook and social media promotions. Features dynamic product loading from SKUs.',
            'html_content' => $templateHtml,
            'status' => 1,
        ]);
    }
}

