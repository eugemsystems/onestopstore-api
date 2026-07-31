<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new order statuses: refunded and arrived_at_local_branch
        DB::table('order_status')->insertOrIgnore([
            [
                'name' => 'Refunded',
                'slug' => 'refunded',
                'sequence' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Arrived At Local Branch',
                'slug' => 'arrived-at-local-branch',
                'sequence' => 60,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('order_status')
            ->whereIn('slug', ['refunded', 'arrived-at-local-branch'])
            ->delete();
    }
};
