<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auction_items', function (Blueprint $table) {
            $table->timestamp('payment_deadline')->nullable()->after('fulfilled_at');
            $table->timestamp('reminder_1_sent_at')->nullable()->after('payment_deadline');
            $table->timestamp('reminder_2_sent_at')->nullable()->after('reminder_1_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('auction_items', function (Blueprint $table) {
            $table->dropColumn(['payment_deadline', 'reminder_1_sent_at', 'reminder_2_sent_at']);
        });
    }
};
