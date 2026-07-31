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
        // Delete all temporary orders created for layby payment gateway compatibility
        // These orders have note = 'TEMP_LAYBY_PAYMENT:XX' and should not remain in the database
        DB::table('orders')
            ->where('note', 'LIKE', 'TEMP_LAYBY_PAYMENT:%')
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse action - we don't want to restore deleted temp orders
    }
};
