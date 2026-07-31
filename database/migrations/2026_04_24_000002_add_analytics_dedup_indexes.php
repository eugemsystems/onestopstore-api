<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The search deduplication check does:
        //   WHERE session_id = X AND normalized_query = X AND created_at > X
        // The existing separate indexes on session_id and (normalized_query, created_at)
        // force PostgreSQL to pick one and filter the other in memory.
        // A single composite index covers all three columns in one scan.
        Schema::table('search_queries', function (Blueprint $table) {
            $table->index(
                ['session_id', 'normalized_query', 'created_at'],
                'search_queries_dedup_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('search_queries', function (Blueprint $table) {
            $table->dropIndex('search_queries_dedup_idx');
        });
    }
};
