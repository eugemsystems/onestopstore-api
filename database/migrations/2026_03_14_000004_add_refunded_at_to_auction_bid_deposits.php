<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auction_bid_deposits', function (Blueprint $table) {
            if (!Schema::hasColumn('auction_bid_deposits', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('paid_at')
                    ->comment('Set when the deposit is refunded to a losing bidder');
            }
        });
    }

    public function down(): void
    {
        Schema::table('auction_bid_deposits', function (Blueprint $table) {
            if (Schema::hasColumn('auction_bid_deposits', 'refunded_at')) {
                $table->dropColumn('refunded_at');
            }
        });
    }
};
