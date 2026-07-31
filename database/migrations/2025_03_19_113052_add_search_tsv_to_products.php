<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add the generated column for the tsvector.
        DB::statement("
            ALTER TABLE products
            ADD COLUMN search_tsv tsvector
            GENERATED ALWAYS AS (to_tsvector('english', search_keywords)) STORED
        ");

        // Create a GIN index on the generated column.
        DB::statement("CREATE INDEX products_search_tsv_idx ON products USING gin(search_tsv)");

    }

    public function down(): void
    {
        DB::statement("DROP INDEX products_search_tsv_idx");
        Schema::table('products', function ($table) {
            $table->dropColumn('search_tsv');
        });
    }
};
