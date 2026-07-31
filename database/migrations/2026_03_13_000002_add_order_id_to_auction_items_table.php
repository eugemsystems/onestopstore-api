<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auction_items', function (Blueprint $table) {
            if (!Schema::hasColumn('auction_items', 'order_id')) {
                $table->unsignedBigInteger('order_id')->nullable()->after('winner_bid')
                      ->comment('Set when auction winner completes payment — links to orders table');
            }
        });
    }

    public function down(): void
    {
        Schema::table('auction_items', function (Blueprint $table) {
            $table->dropColumn('order_id');
        });
    }
};
