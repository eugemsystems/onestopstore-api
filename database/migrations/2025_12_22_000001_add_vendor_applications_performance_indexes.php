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
        // Add indexes to optimize vendor applications query
        Schema::table('products', function (Blueprint $table) {
            // Composite index for store_id + status checks
            $table->index(['store_id', 'is_approved', 'status', 'deleted_at'], 'products_store_approval_idx');
        });

        Schema::table('stores', function (Blueprint $table) {
            // Index for filtering and sorting
            $table->index(['is_approved', 'status', 'is_banned', 'created_at'], 'stores_filter_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_store_approval_idx');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropIndex('stores_filter_idx');
        });
    }
};

