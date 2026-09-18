<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_feed_runs', function (Blueprint $table) {
            // Set only for the sharded Global feeds — how many files this run's
            // "records_written"/"file_size_bytes" totals are spread across.
            // Null for the single-file Zimbabwe/Zambia feeds.
            $table->unsignedInteger('shard_count')->nullable()->after('file_size_bytes');
        });
    }

    public function down(): void
    {
        Schema::table('daily_feed_runs', function (Blueprint $table) {
            $table->dropColumn('shard_count');
        });
    }
};
