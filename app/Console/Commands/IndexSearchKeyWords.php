<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class IndexSearchKeyWords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:index-search-key-words';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate the search_keywords column in products table with concatenated values from name, sku, categories, and tags.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to index the products search_keywords column...');

        // Update the search_keywords column with concatenated values
        DB::statement("
            UPDATE products p
            SET search_keywords =
                INITCAP(
                    CONCAT_WS(' ',
                        INITCAP(p.name),
                        INITCAP(p.sku),
                        INITCAP(p.slug),
                        COALESCE((
                            SELECT string_agg(INITCAP(c.name), ' ')
                            FROM categories c
                            JOIN product_categories pc ON pc.category_id = c.id
                            WHERE pc.product_id = p.id
                        ), ''),
                        COALESCE((
                            SELECT string_agg(INITCAP(t.name), ' ')
                            FROM tags t
                            JOIN product_tags pt ON pt.tag_id = t.id
                            WHERE pt.product_id = p.id
                        ), '')
                    )
                )
        ");

        $this->info('Search keywords populated successfully.');

    }
}
