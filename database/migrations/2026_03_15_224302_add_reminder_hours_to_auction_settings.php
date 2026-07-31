<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auction_settings', function (Blueprint $table) {
            $table->unsignedInteger('hours_to_pay')->default(48)->after('delivery_price')
                  ->comment('Hours after winning that the winner has to pay');
            $table->unsignedInteger('reminder_1_hours')->default(12)->after('hours_to_pay')
                  ->comment('First reminder: hours after auction win');
            $table->unsignedInteger('reminder_2_hours')->default(24)->after('reminder_1_hours')
                  ->comment('Second reminder: hours after auction win');
        });
    }

    public function down(): void
    {
        Schema::table('auction_settings', function (Blueprint $table) {
            $table->dropColumn(['hours_to_pay', 'reminder_1_hours', 'reminder_2_hours']);
        });
    }
};
