<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add payment_details column
        Schema::table('withdraw_requests', function (Blueprint $table) {
            $table->json('payment_details')->nullable()->after('payment_type');
        });

        // Drop the old check constraint
        DB::statement('ALTER TABLE withdraw_requests DROP CONSTRAINT IF EXISTS withdraw_requests_payment_type_check');

        // Convert payment_type to VARCHAR to avoid enum issues
        DB::statement('ALTER TABLE withdraw_requests ALTER COLUMN payment_type TYPE VARCHAR(50)');

        // Add new check constraint with expanded values
        DB::statement("ALTER TABLE withdraw_requests ADD CONSTRAINT withdraw_requests_payment_type_check CHECK (payment_type IN ('paypal', 'bank', 'Bank', 'Mobile Money', 'Wallet'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('withdraw_requests', function (Blueprint $table) {
            $table->dropColumn('payment_details');
        });

        // Drop the new check constraint
        DB::statement('ALTER TABLE withdraw_requests DROP CONSTRAINT IF EXISTS withdraw_requests_payment_type_check');

        // Restore original check constraint
        DB::statement("ALTER TABLE withdraw_requests ADD CONSTRAINT withdraw_requests_payment_type_check CHECK (payment_type IN ('paypal', 'bank'))");
    }
};

