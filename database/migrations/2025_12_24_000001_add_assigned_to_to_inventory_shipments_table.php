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
            $table->string('srs', 100)->nullable()->after('received_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_shipments', function (Blueprint $table) {
            $table->dropColumn('srs');
        });
    }
};

