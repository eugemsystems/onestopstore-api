<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the missing `zimbabwe_only` boolean column to both the products and
 * variations tables.  The companion `zambia_only` column was already added in
 * 2026_03_19_110000 / 2026_03_19_120000, but `zimbabwe_only` was never
 * migrated.  Without this column the ReindexProducts command's SELECT list
 * throws an "Unknown column" error on production, which silently aborts the
 * chunkById loop and results in zero products being indexed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'zimbabwe_only')) {
                $table->boolean('zimbabwe_only')->default(false)->after('zambia_only');
            }
        });

        Schema::table('variations', function (Blueprint $table) {
            if (!Schema::hasColumn('variations', 'zimbabwe_only')) {
                $table->boolean('zimbabwe_only')->default(false)->after('zambia_only');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('zimbabwe_only');
        });

        Schema::table('variations', function (Blueprint $table) {
            $table->dropColumn('zimbabwe_only');
        });
    }
};
