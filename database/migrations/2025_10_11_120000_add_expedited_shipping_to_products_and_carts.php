<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Per-product dual shipping options
            $table->boolean('has_expedited_shipping')->default(false)->after('is_free_shipping');
            $table->integer('standard_shipping_days')->nullable()->after('has_expedited_shipping');
            $table->integer('expedited_shipping_days')->nullable()->after('standard_shipping_days');
            $table->decimal('standard_shipping_price', 12, 2)->nullable()->after('expedited_shipping_days');
            $table->decimal('expedited_shipping_price', 12, 2)->nullable()->after('standard_shipping_price');
        });

        Schema::table('carts', function (Blueprint $table) {
            // Store per-item shipping selection (optional, used by checkout to compute totals)
            $table->string('item_shipping_method')->nullable()->after('quantity'); // 'standard' | 'expedited'
            $table->decimal('item_shipping_cost', 12, 2)->nullable()->after('item_shipping_method');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('item_shipping_method');
            $table->dropColumn('item_shipping_cost');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('has_expedited_shipping');
            $table->dropColumn('standard_shipping_days');
            $table->dropColumn('expedited_shipping_days');
            $table->dropColumn('standard_shipping_price');
            $table->dropColumn('expedited_shipping_price');
        });
    }
};
