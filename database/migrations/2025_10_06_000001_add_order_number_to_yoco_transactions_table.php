<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('yoco_transactions', function (Blueprint $table) {
            $table->string('order_number')->nullable()->index()->after('order_id');
            $table->index(['order_number', 'gateway_transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::table('yoco_transactions', function (Blueprint $table) {
            $table->dropIndex(['order_number']);
            $table->dropIndex(['order_number', 'gateway_transaction_id']);
            $table->dropColumn('order_number');
        });
    }
};
