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
        Schema::table('order_products', function (Blueprint $table) {
            if (!Schema::hasColumn('order_products', 'qr_code')) {
                $table->text('qr_code')->nullable()->after('item_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            if (Schema::hasColumn('order_products', 'qr_code')) {
                $table->dropColumn('qr_code');
            }
        });
    }
};
