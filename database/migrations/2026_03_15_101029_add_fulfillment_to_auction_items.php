<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auction_items', function (Blueprint $table) {
            $table->string('fulfillment_method')->nullable()->default('collection')->after('order_id'); // 'collection' | 'delivery'
            $table->decimal('delivery_cost', 10, 2)->default(0)->after('fulfillment_method');
            $table->text('delivery_address')->nullable()->after('delivery_cost');
            $table->string('fulfillment_status')->nullable()->default('pending')->after('delivery_address');
            // 'pending' | 'ready_for_collection' | 'out_for_delivery' | 'collected' | 'delivered'
            $table->timestamp('fulfilled_at')->nullable()->after('fulfillment_status');
        });
    }

    public function down(): void
    {
        Schema::table('auction_items', function (Blueprint $table) {
            $table->dropColumn(['fulfillment_method', 'delivery_cost', 'delivery_address', 'fulfillment_status', 'fulfilled_at']);
        });
    }
};
