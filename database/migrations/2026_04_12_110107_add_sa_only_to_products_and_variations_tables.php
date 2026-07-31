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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'sa_only')) {
                $table->boolean('sa_only')->default(false);
            }
        });

        Schema::table('variations', function (Blueprint $table) {
            if (!Schema::hasColumn('variations', 'sa_only')) {
                $table->boolean('sa_only')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('sa_only');
        });

        Schema::table('variations', function (Blueprint $table) {
            $table->dropColumn('sa_only');
        });
    }
};
