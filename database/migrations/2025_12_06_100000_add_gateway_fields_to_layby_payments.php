<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('layby_payments', function (Blueprint $table) {
            $table->string('currency_symbol', 10)->nullable()->after('currency');
            $table->decimal('exchange_rate', 10, 4)->default(1)->after('currency_symbol');
            $table->string('gateway_reference')->nullable()->after('transaction_id'); // Payment gateway reference
        });
    }

    public function down(): void
    {
        Schema::table('layby_payments', function (Blueprint $table) {
            $table->dropColumn(['currency_symbol', 'exchange_rate', 'gateway_reference']);
        });
    }
};

