<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_shipments', function (Blueprint $table) {
            $table->timestamp('sticker_downloaded_at')->nullable()->after('notes');
            $table->timestamp('waybill_downloaded_at')->nullable()->after('sticker_downloaded_at');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_shipments', function (Blueprint $table) {
            $table->dropColumn(['sticker_downloaded_at', 'waybill_downloaded_at']);
        });
    }
};
