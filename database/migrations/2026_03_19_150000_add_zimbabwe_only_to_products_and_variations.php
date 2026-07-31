<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('zimbabwe_only')->default(false)->after('zambia_only');
        });

        Schema::table('variations', function (Blueprint $table) {
            $table->boolean('zimbabwe_only')->default(false)->after('zambia_only');
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
