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
            $table->string('original_currency_code', 10)->nullable()->after('tax_id');
            $table->decimal('original_price', 10, 2)->nullable()->after('original_currency_code');
            $table->decimal('original_sale_price', 10, 2)->nullable()->after('original_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['original_currency_code', 'original_price', 'original_sale_price']);
        });
    }
};

