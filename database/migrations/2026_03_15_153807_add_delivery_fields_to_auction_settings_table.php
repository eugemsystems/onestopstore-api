<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auction_settings', function (Blueprint $table) {
            $table->boolean('delivery_enabled')->default(false)->after('bid_fee_amount');
            $table->decimal('delivery_price', 10, 2)->default(0)->after('delivery_enabled');
            $table->unsignedInteger('delivery_radius_km')->default(15)->after('delivery_price');
        });
    }

    public function down(): void
    {
        Schema::table('auction_settings', function (Blueprint $table) {
            $table->dropColumn(['delivery_enabled', 'delivery_price', 'delivery_radius_km']);
        });
    }
};
