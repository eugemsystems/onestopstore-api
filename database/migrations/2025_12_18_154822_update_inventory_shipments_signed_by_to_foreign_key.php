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
        Schema::table('inventory_shipments', function (Blueprint $table) {
            // Drop the old enum column if it exists
            $table->dropColumn('signed_by');
        });

        Schema::table('inventory_shipments', function (Blueprint $table) {
            // Add new foreign key column
            $table->foreignId('signed_by')->nullable()->after('f_status')->constrained('users')->onDelete('set null')->comment('Staff user who signed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_shipments', function (Blueprint $table) {
            $table->dropForeign(['signed_by']);
            $table->dropColumn('signed_by');
        });

        Schema::table('inventory_shipments', function (Blueprint $table) {
            $table->enum('signed_by', ['Figo', 'Simba'])->nullable()->after('f_status');
        });
    }
};

