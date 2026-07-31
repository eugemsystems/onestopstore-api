<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auction_deposit_refunds', function (Blueprint $table) {
            // 'bank' = traditional bank transfer, 'wallet' = credit the in-app wallet
            $table->string('refund_method')->default('bank')->after('reason');
            $table->timestamp('wallet_credited_at')->nullable()->after('processed_at');
        });
    }

    public function down(): void
    {
        Schema::table('auction_deposit_refunds', function (Blueprint $table) {
            $table->dropColumn(['refund_method', 'wallet_credited_at']);
        });
    }
};

