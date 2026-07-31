<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes to optimize products query performance
        Schema::table('products', function (Blueprint $table) {
            // Composite index for filtering and sorting
            $table->index(['status', 'stock_status', 'created_at'], 'products_filter_sort_idx');
            // Index for SKU and name search
            $table->index(['sku', 'name'], 'products_search_idx');
        });

        Schema::table('product_categories', function (Blueprint $table) {
            // Index for category filtering - improve the JOIN in product feed
            $table->index(['category_id', 'product_id'], 'product_categories_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_filter_sort_idx');
            $table->dropIndex('products_search_idx');
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropIndex('product_categories_lookup_idx');
        });
    }
};

