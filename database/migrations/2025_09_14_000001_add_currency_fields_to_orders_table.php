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
            // Add currency fields with sensible defaults
            $table->string('currency', 10)->nullable()->default('USD')->after('payment_status');
            $table->string('currency_symbol', 5)->nullable()->default('$')->after('currency');
        });

        // Backfill existing rows so no order is left without currency data
        DB::table('orders')->whereNull('currency')->update(['currency' => 'USD']);
        DB::table('orders')->where('currency', '')->update(['currency' => 'USD']);
        DB::table('orders')->whereNull('currency_symbol')->update(['currency_symbol' => '$']);
        DB::table('orders')->where('currency_symbol', '')->update(['currency_symbol' => '$']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['currency_symbol', 'currency']);
        });
    }
};
