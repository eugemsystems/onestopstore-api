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
        // Add indexes to optimize orders query performance
        Schema::table('orders', function (Blueprint $table) {
            // Composite index for filtering and sorting
            $table->index(['parent_id', 'order_status_id', 'created_at'], 'orders_filter_sort_idx');
            // Index for order number search
            $table->index('order_number', 'orders_number_idx');
        });

        Schema::table('order_products', function (Blueprint $table) {
            // Index for product search in orders - order_products has product_id, not name
            $table->index(['order_id', 'product_id'], 'order_products_order_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            // Index for consumer search in orders
            $table->index(['name', 'email', 'deleted_at'], 'users_search_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_filter_sort_idx');
            $table->dropIndex('orders_number_idx');
        });

        Schema::table('order_products', function (Blueprint $table) {
            $table->dropIndex('order_products_order_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_search_idx');
        });
    }
};

