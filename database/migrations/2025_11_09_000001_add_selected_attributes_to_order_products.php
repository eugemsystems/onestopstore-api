<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add column to store selected attribute value IDs when customer selects
     * multiple attributes from different variations (e.g., Color + Size).
     */
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            if (!Schema::hasColumn('order_products', 'selected_attribute_ids')) {
                // JSON column to store array of selected attribute_value_ids
                // Example: [72656, 72232] for "Blue, Brown" + "L"
                $table->json('selected_attribute_ids')->nullable()->after('variation_id');
            }

            if (!Schema::hasColumn('order_products', 'variation_display_name')) {
                // Store the combined variation name for display
                // Example: "Blue, Brown - L"
                $table->string('variation_display_name')->nullable()->after('selected_attribute_ids');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            if (Schema::hasColumn('order_products', 'variation_display_name')) {
                $table->dropColumn('variation_display_name');
            }

            if (Schema::hasColumn('order_products', 'selected_attribute_ids')) {
                $table->dropColumn('selected_attribute_ids');
            }
        });
    }
};

