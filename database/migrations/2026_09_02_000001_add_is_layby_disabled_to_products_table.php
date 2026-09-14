<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Defaults to false so every existing product keeps its current layby
            // eligibility (price/region based) unless an admin explicitly disables it.
            $table->boolean('is_layby_disabled')->default(false)->after('is_permanently_disabled');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_layby_disabled');
        });
    }
};
