<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicate rows first — keep only the most recent one per user/auction
        DB::statement("
            DELETE FROM auction_auto_bids
            WHERE id NOT IN (
                SELECT MAX(id)
                FROM auction_auto_bids
                GROUP BY auction_item_id, user_id
            )
        ");

        Schema::table('auction_auto_bids', function (Blueprint $table) {
            // Drop the old plain index if it exists
            $table->dropIndex(['auction_item_id', 'user_id', 'is_active']);

            // Add a proper unique constraint so updateOrCreate works reliably
            $table->unique(['auction_item_id', 'user_id'], 'auction_auto_bids_item_user_unique');

            // Re-add just a plain index for is_active filtering
            $table->index(['auction_item_id', 'is_active'], 'auction_auto_bids_active_idx');
        });
    }

    public function down(): void
    {
        Schema::table('auction_auto_bids', function (Blueprint $table) {
            $table->dropUnique('auction_auto_bids_item_user_unique');
            $table->dropIndex('auction_auto_bids_active_idx');
            $table->index(['auction_item_id', 'user_id', 'is_active']);
        });
    }
};
