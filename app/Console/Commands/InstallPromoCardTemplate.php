<?php

namespace App\Console\Commands;

use App\Models\PromoTemplate;
use Illuminate\Console\Command;

class InstallPromoCardTemplate extends Command
{
    protected $signature = 'promo:install-card-template {--name=Product Card (storefront style) : Template name}';

    protected $description = 'Create/update the storefront-style product card promo template (used by Generate Banners by SKU)';

    public function handle(): int
    {
        $file = database_path('promo-templates/product-card.html');
        if (!is_file($file)) {
            $this->error("Missing {$file}");

            return self::FAILURE;
        }

        $template = PromoTemplate::updateOrCreate(
            ['name' => $this->option('name')],
            [
                'description'  => 'Product card (badge, image, name, rating, prices, delivery) — placeholders are filled per SKU.',
                'html_content' => file_get_contents($file),
                'status'       => 1,
            ]
        );

        $this->info("Template #{$template->id} \"{$template->name}\" saved. Open /admin/promo-templates/{$template->id}/generate-sku");

        return self::SUCCESS;
    }
}
