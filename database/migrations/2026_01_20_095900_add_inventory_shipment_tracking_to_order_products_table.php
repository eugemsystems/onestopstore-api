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
        Schema::table('order_products', function (Blueprint $table) {
            $table->boolean('added_to_inventory')->default(false)->after('item_status');
            $table->unsignedBigInteger('inventory_shipment_id')->nullable()->after('added_to_inventory');
            $table->timestamp('added_to_inventory_at')->nullable()->after('inventory_shipment_id');

            // Foreign key constraint
            $table->foreign('inventory_shipment_id')
                  ->references('id')
                  ->on('inventory_shipments')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropForeign(['inventory_shipment_id']);
            $table->dropColumn(['added_to_inventory', 'inventory_shipment_id', 'added_to_inventory_at']);
        });
    }
};
