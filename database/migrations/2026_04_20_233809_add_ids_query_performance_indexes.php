<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Composite index for the most common frontend query:
        // WHERE status = 1 AND is_approved = 1 (with optional whereIn id)
        Schema::table('products', function (Blueprint $table) {
            if (!$this->indexExists('products', 'products_status_approved_idx')) {
                $table->index(['status', 'is_approved'], 'products_status_approved_idx');
            }
        });

        // Speed up eager-loading product_thumbnail via the media table.
        // Laravel's with(['product_thumbnail']) generates:
        //   SELECT * FROM media WHERE model_type = ? AND model_id IN (...) AND collection_name = ?
        Schema::table('media', function (Blueprint $table) {
            if (!$this->indexExists('media', 'media_model_collection_idx')) {
                $table->index(['model_type', 'model_id', 'collection_name'], 'media_model_collection_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndexIfExists('products_status_approved_idx');
        });

        Schema::table('media', function (Blueprint $table) {
            $table->dropIndexIfExists('media_model_collection_idx');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(DB::select(
            "SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?",
            [$table, $index]
        ))->isNotEmpty();
    }
};
