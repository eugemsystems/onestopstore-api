<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add missing amount and currency columns to pesepay_transactions table
     */
    public function up(): void
    {
        Schema::table('pesepay_transactions', function (Blueprint $table) {
            // Add amount column if missing
            if (!Schema::hasColumn('pesepay_transactions', 'amount')) {
                $table->decimal('amount', 18, 2)->nullable()->after('status');
            }

            // Add currency column if missing
            if (!Schema::hasColumn('pesepay_transactions', 'currency')) {
                $table->string('currency', 10)->nullable()->after('amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pesepay_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('pesepay_transactions', 'amount')) {
                $table->dropColumn('amount');
            }

            if (Schema::hasColumn('pesepay_transactions', 'currency')) {
                $table->dropColumn('currency');
            }
        });
    }
};

