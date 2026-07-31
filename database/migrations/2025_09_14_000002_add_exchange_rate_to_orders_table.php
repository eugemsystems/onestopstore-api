<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Use higher precision for FX rates
            $table->decimal('exchange_rate', 12, 6)->nullable()->default(1)->after('currency_symbol');
        });

        // Backfill existing rows
        DB::table('orders')->whereNull('exchange_rate')->update(['exchange_rate' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('exchange_rate');
        });
    }
};
