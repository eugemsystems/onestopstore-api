<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Enable the pg_trgm extension if it's not already enabled.
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm;');

        // 2) Create a GIN index on the lowercased search_keywords column using trigram ops.
        //    This will speed up ILIKE queries and similarity() searches.
        DB::statement("
            CREATE INDEX IF NOT EXISTS products_search_keywords_trgm_idx
            ON products
            USING gin (lower(search_keywords) gin_trgm_ops)
        ");
    }

    public function down(): void
    {
        // Drop the trigram index
        DB::statement('DROP INDEX IF EXISTS products_search_keywords_trgm_idx');

        // Optionally, if you want to fully revert:
        // DB::statement('DROP EXTENSION IF EXISTS pg_trgm;');
        //
        // However, removing the extension might break other tables/code
        // that rely on pg_trgm, so it's often left in place.
    }
};
