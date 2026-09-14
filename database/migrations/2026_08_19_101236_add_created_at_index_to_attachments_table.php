<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // attachments is ~64M rows / 20GB — CREATE INDEX CONCURRENTLY avoids
    // locking writes for the duration, but can't run inside a transaction.
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Partial index matching Eloquent's default soft-delete scope, since the
        // admin media list always filters deleted_at IS NULL and sorts by created_at.
        DB::statement(
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS attachments_deleted_at_created_at_index '
            . 'ON attachments (created_at DESC) WHERE deleted_at IS NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS attachments_deleted_at_created_at_index');
    }
};
