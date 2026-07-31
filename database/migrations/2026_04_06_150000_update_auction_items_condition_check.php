<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing check constraint (name may vary — try both common names)
        DB::statement('ALTER TABLE auction_items DROP CONSTRAINT IF EXISTS auction_items_condition_check');

        // Re-add with all 8 valid condition values
        DB::statement("
            ALTER TABLE auction_items
            ADD CONSTRAINT auction_items_condition_check
            CHECK (condition IN (
                'damaged',
                'refurbished',
                'as-is',
                'boxed-damaged',
                'no-box',
                'returned',
                'dented',
                'missing-accessories'
            ))
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE auction_items DROP CONSTRAINT IF EXISTS auction_items_condition_check');

        // Restore original 3-value constraint
        DB::statement("
            ALTER TABLE auction_items
            ADD CONSTRAINT auction_items_condition_check
            CHECK (condition IN ('damaged', 'refurbished', 'as-is'))
        ");
    }
};
