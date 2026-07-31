<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The existing order_products index is (order_id, product_id).
        // Queries doing WHERE product_id = X (from whereHas('products', ...)) cannot
        // use a composite index whose leading column is order_id. A separate single-
        // column index on product_id lets those lookups use the index directly.
        Schema::table('order_products', function (Blueprint $table) {
            $table->index('product_id', 'order_products_product_id_idx');
        });

        // reviews.product_id — used by withCount('reviews') and rating subqueries.
        Schema::table('reviews', function (Blueprint $table) {
            $table->index('product_id', 'reviews_product_id_idx');
        });

        // Partial index for the ExcludeTempLaybyScope NOT LIKE global scope.
        // PostgreSQL can use a partial index when the query WHERE clause matches
        // the partial index predicate, turning sequential scans into index scans.
        DB::statement("
            CREATE INDEX IF NOT EXISTS orders_non_system_note_idx
            ON orders (id)
            WHERE note IS NULL
               OR (
                    note NOT LIKE 'TEMP_LAYBY_PAYMENT:%'
                AND note NOT LIKE 'AUC_DEPOSIT:%'
                AND note NOT LIKE 'AUC_WIN:%'
               )
        ");
    }

    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropIndex('order_products_product_id_idx');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_product_id_idx');
        });

        DB::statement('DROP INDEX IF EXISTS orders_non_system_note_idx');
    }
};
