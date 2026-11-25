<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_quotation_items', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_quotation_items', 'delivery_option_id')) {
                $table->unsignedBigInteger('delivery_option_id')->nullable()->after('variation_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoice_quotation_items', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_quotation_items', 'delivery_option_id')) {
                $table->dropColumn('delivery_option_id');
            }
        });
    }
};
