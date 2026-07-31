<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * CRITICAL: Remove all FK constraints from order_products that point to products/variations.
 *
 * order_products is HISTORICAL data — it must NEVER be modified when a product
 * or variation is updated, soft-deleted, or hard-deleted. The previous FK with
 * CASCADE DELETE was the root cause of order items vanishing when products were
 * re-imported. Even SET NULL is dangerous because it loses the product_id link.
 *
 * After this migration, order_products is decoupled from products/variations at
 * the database constraint level. The application layer (Eloquent relationships)
 * handles all referential integrity.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop FK on product_id (currently SET NULL — still dangerous)
        $this->dropForeignKeyIfExists('order_products', 'order_products_product_id_foreign');

        // Drop FK on variation_id (currently SET NULL — still dangerous)
        $this->dropForeignKeyIfExists('order_products', 'order_products_variation_id_foreign');
    }

    public function down(): void
    {
        // Restore SET NULL FKs if rolling back (not recommended)
        Schema::table('order_products', function (Blueprint $table) {
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('set null');

            $table->foreign('variation_id')
                ->references('id')
                ->on('variations')
                ->onDelete('set null');
        });
    }

    /**
     * Safely drop a foreign key constraint if it exists.
     */
    private function dropForeignKeyIfExists(string $table, string $constraintName): void
    {
        $exists = DB::select(
            "SELECT 1 FROM information_schema.table_constraints
             WHERE table_name = ? AND constraint_name = ? AND constraint_type = 'FOREIGN KEY'",
            [$table, $constraintName]
        );

        if (!empty($exists)) {
            Schema::table($table, function (Blueprint $t) use ($constraintName) {
                $t->dropForeign($constraintName);
            });
        }
    }
};

