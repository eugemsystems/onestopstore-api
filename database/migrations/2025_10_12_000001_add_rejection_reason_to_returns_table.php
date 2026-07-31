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
        Schema::table('returns', function (Blueprint $table) {
            // Add rejection_reason to store the admin's rejection message
            if (!Schema::hasColumn('returns', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('returns', function (Blueprint $table) {
            if (Schema::hasColumn('returns', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
        });
    }
};
