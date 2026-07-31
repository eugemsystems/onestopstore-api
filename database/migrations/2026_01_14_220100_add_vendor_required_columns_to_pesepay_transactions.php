<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add missing columns that the Pesepay vendor package expects
     */
    public function up(): void
    {
        Schema::table('pesepay_transactions', function (Blueprint $table) {
            // Add gateway_transaction_id column (required by vendor package)
            if (!Schema::hasColumn('pesepay_transactions', 'gateway_transaction_id')) {
                $table->string('gateway_transaction_id')->nullable()->after('order_id');
            }

            // Add status column if missing (required by vendor package)
            if (!Schema::hasColumn('pesepay_transactions', 'status')) {
                $table->string('status')->nullable()->after('gateway_transaction_id');
            }

            // Add raw_response column if missing (required by vendor package)
            if (!Schema::hasColumn('pesepay_transactions', 'raw_response')) {
                $table->json('raw_response')->nullable()->after('status');
            }

            // Add other_fields column if missing (required by vendor package)
            if (!Schema::hasColumn('pesepay_transactions', 'other_fields')) {
                $table->json('other_fields')->nullable()->after('raw_response');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pesepay_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'gateway_transaction_id',
                'status',
                'raw_response',
                'other_fields'
            ]);
        });
    }
};

