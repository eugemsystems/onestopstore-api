<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('require_email_verification')->default(true);
            $table->boolean('bid_fee_enabled')->default(false);
            $table->decimal('bid_fee_amount', 10, 2)->default(0.00);
            $table->timestamps();
        });

        // Seed default row so AuctionSetting::current() always returns something
        DB::table('auction_settings')->insert([
            'require_email_verification' => true,
            'bid_fee_enabled'            => false,
            'bid_fee_amount'             => 0.00,
            'created_at'                 => now(),
            'updated_at'                 => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_settings');
    }
};
