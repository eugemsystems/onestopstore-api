<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIX: Order products permanently deleted when products/variations are deleted
 *
 * ROOT CAUSE: The order_products table has CASCADE DELETE on product_id and variation_id.
 * When a product or variation is deleted, the database automatically HARD DELETES
 * all order_products records, bypassing Laravel's soft delete mechanism.
 *
 * This migration changes CASCADE to SET NULL to preserve order history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            // Drop existing foreign keys with CASCADE DELETE
            $table->dropForeign(['product_id']);
            $table->dropForeign(['variation_id']);
        });

        Schema::table('order_products', function (Blueprint $table) {
            // Make product_id nullable so SET NULL works
            $table->unsignedBigInteger('product_id')->nullable()->change();

            // Re-add foreign keys with SET NULL instead of CASCADE
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('set null');

            $table->foreign('variation_id')
                ->references('id')
                ->on('variations')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['variation_id']);
        });

        Schema::table('order_products', function (Blueprint $table) {
            // Restore CASCADE (not recommended)
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->foreign('variation_id')
                ->references('id')
                ->on('variations')
                ->onDelete('cascade');
        });
    }
};
