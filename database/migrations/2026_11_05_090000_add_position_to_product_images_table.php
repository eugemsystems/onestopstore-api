<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Display order of a product's gallery images (set by drag & drop in the admin product edit page).
     * Nullable: rows without a position keep the old order (by pivot id) after the positioned ones.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('product_images', 'position')) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->unsignedInteger('position')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_images', 'position')) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->dropColumn('position');
            });
        }
    }
};
