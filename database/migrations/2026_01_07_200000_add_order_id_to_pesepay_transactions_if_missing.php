<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if table exists and if order_id column is missing
        if (Schema::hasTable('pesepay_transactions') && !Schema::hasColumn('pesepay_transactions', 'order_id')) {
            Schema::table('pesepay_transactions', function (Blueprint $table) {
                $table->string('order_id')->after('id')->nullable();
                //$table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pesepay_transactions') && Schema::hasColumn('pesepay_transactions', 'order_id')) {
            Schema::table('pesepay_transactions', function (Blueprint $table) {
                $table->dropForeign(['order_id']);
                $table->dropColumn('order_id');
            });
        }
    }
};

