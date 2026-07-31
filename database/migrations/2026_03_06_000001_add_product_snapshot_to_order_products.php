<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add product snapshot columns to order_products.
 *
 * These columns store a point-in-time copy of the product data at order
 * creation so that order history is fully self-contained and survives
 * product edits, soft-deletes, and hard-deletes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            if (!Schema::hasColumn('order_products', 'product_name')) {
                $table->string('product_name')->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('order_products', 'product_sku')) {
                $table->string('product_sku')->nullable()->after('product_name');
            }
            if (!Schema::hasColumn('order_products', 'product_slug')) {
                $table->string('product_slug')->nullable()->after('product_sku');
            }
            if (!Schema::hasColumn('order_products', 'product_image_url')) {
                $table->string('product_image_url', 1024)->nullable()->after('product_slug');
            }
            if (!Schema::hasColumn('order_products', 'product_price')) {
                $table->decimal('product_price', 10, 2)->nullable()->after('product_image_url');
            }
            if (!Schema::hasColumn('order_products', 'product_sale_price')) {
                $table->decimal('product_sale_price', 10, 2)->nullable()->after('product_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $columns = [
                'product_name',
                'product_sku',
                'product_slug',
                'product_image_url',
                'product_price',
                'product_sale_price',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('order_products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};


