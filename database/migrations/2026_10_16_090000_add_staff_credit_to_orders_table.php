<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Manual staff-performance credit for orders nobody claimed (e.g. online orders)
            $table->unsignedBigInteger('staff_credit_id')->nullable()->index();
            $table->unsignedBigInteger('staff_credit_assigned_by_id')->nullable();
            $table->timestamp('staff_credit_assigned_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['staff_credit_id']);
            $table->dropColumn(['staff_credit_id', 'staff_credit_assigned_by_id', 'staff_credit_assigned_at']);
        });
    }
};
