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
        Schema::table('invoices_quotations', function (Blueprint $table) {
            // Add shipping fee (rule-based) field
            $table->decimal('shipping_total', 10, 2)->default(0)->after('include_vat');

            // Add delivery/shipping fields to match order system
            $table->string('delivery_method')->nullable()->after('shipping_total');
            $table->text('delivery_description')->nullable()->after('delivery_method');
            $table->decimal('delivery_price', 10, 2)->default(0)->after('delivery_description');
            $table->string('delivery_interval')->nullable()->after('delivery_price');
            $table->string('collection_point')->nullable()->after('delivery_interval');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices_quotations', function (Blueprint $table) {
            $table->dropColumn(['shipping_total', 'delivery_method', 'delivery_description', 'delivery_price', 'delivery_interval', 'collection_point']);
        });
    }
};

