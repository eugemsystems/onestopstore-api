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
        // Add indexes to user_sessions table
        if (Schema::hasTable('user_sessions')) {
            Schema::table('user_sessions', function (Blueprint $table) {
                try {
                    // Check if indexes don't exist before creating
                    if (!$this->indexExists('user_sessions', 'user_sessions_session_id_index')) {
                        $table->index('session_id');
                    }
                } catch (\Exception $e) {
                    // Index already exists, skip
                }

                try {
                    if (!$this->indexExists('user_sessions', 'user_sessions_last_activity_at_index')) {
                        $table->index('last_activity_at');
                    }
                } catch (\Exception $e) {}

                try {
                    if (!$this->indexExists('user_sessions', 'user_sessions_created_at_index')) {
                        $table->index('created_at');
                    }
                } catch (\Exception $e) {}

                try {
                    if (!$this->indexExists('user_sessions', 'user_sessions_user_id_index')) {
                        $table->index('user_id');
                    }
                } catch (\Exception $e) {}
            });
        }

        // Add indexes to page_views table
        if (Schema::hasTable('page_views')) {
            Schema::table('page_views', function (Blueprint $table) {
                try {
                    if (!$this->indexExists('page_views', 'page_views_session_id_index')) {
                        $table->index('session_id');
                    }
                } catch (\Exception $e) {}

                try {
                    if (!$this->indexExists('page_views', 'page_views_created_at_index')) {
                        $table->index('created_at');
                    }
                } catch (\Exception $e) {}

                try {
                    if (!$this->indexExists('page_views', 'page_views_path_index')) {
                        $table->index('path');
                    }
                } catch (\Exception $e) {}
            });
        }

        // Add indexes to user_events table
        if (Schema::hasTable('user_events')) {
            Schema::table('user_events', function (Blueprint $table) {
                try {
                    if (!$this->indexExists('user_events', 'user_events_session_id_index')) {
                        $table->index('session_id');
                    }
                } catch (\Exception $e) {}

                try {
                    if (!$this->indexExists('user_events', 'user_events_created_at_index')) {
                        $table->index('created_at');
                    }
                } catch (\Exception $e) {}

                try {
                    if (!$this->indexExists('user_events', 'user_events_event_name_index')) {
                        $table->index('event_name');
                    }
                } catch (\Exception $e) {}

                try {
                    if (!$this->indexExists('user_events', 'user_events_event_type_index')) {
                        $table->index('event_type');
                    }
                } catch (\Exception $e) {}
            });
        }

        // Add indexes to cart_abandonments table
        if (Schema::hasTable('cart_abandonments')) {
            Schema::table('cart_abandonments', function (Blueprint $table) {
                try {
                    if (!$this->indexExists('cart_abandonments', 'cart_abandonments_session_id_index')) {
                        $table->index('session_id');
                    }
                } catch (\Exception $e) {}

                try {
                    if (!$this->indexExists('cart_abandonments', 'cart_abandonments_created_at_index')) {
                        $table->index('created_at');
                    }
                } catch (\Exception $e) {}

                try {
                    if (!$this->indexExists('cart_abandonments', 'cart_abandonments_recovered_index')) {
                        $table->index('recovered');
                    }
                } catch (\Exception $e) {}

                try {
                    if (!$this->indexExists('cart_abandonments', 'cart_abandonments_abandonment_stage_index')) {
                        $table->index('abandonment_stage');
                    }
                } catch (\Exception $e) {}
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('user_sessions')) {
            Schema::table('user_sessions', function (Blueprint $table) {
                try { $table->dropIndex(['session_id']); } catch (\Exception $e) {}
                try { $table->dropIndex(['last_activity_at']); } catch (\Exception $e) {}
                try { $table->dropIndex(['created_at']); } catch (\Exception $e) {}
                try { $table->dropIndex(['user_id']); } catch (\Exception $e) {}
            });
        }

        if (Schema::hasTable('page_views')) {
            Schema::table('page_views', function (Blueprint $table) {
                try { $table->dropIndex(['session_id']); } catch (\Exception $e) {}
                try { $table->dropIndex(['created_at']); } catch (\Exception $e) {}
                try { $table->dropIndex(['path']); } catch (\Exception $e) {}
            });
        }

        if (Schema::hasTable('user_events')) {
            Schema::table('user_events', function (Blueprint $table) {
                try { $table->dropIndex(['session_id']); } catch (\Exception $e) {}
                try { $table->dropIndex(['created_at']); } catch (\Exception $e) {}
                try { $table->dropIndex(['event_name']); } catch (\Exception $e) {}
                try { $table->dropIndex(['event_type']); } catch (\Exception $e) {}
            });
        }

        if (Schema::hasTable('cart_abandonments')) {
            Schema::table('cart_abandonments', function (Blueprint $table) {
                try { $table->dropIndex(['session_id']); } catch (\Exception $e) {}
                try { $table->dropIndex(['created_at']); } catch (\Exception $e) {}
                try { $table->dropIndex(['recovered']); } catch (\Exception $e) {}
                try { $table->dropIndex(['abandonment_stage']); } catch (\Exception $e) {}
            });
        }
    }

    /**
     * Check if an index exists using raw SQL
     */
    private function indexExists(string $table, string $index): bool
    {
        try {
            // For PostgreSQL
            $result = DB::select(
                "SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ?",
                [$table, $index]
            );
            return count($result) > 0;
        } catch (\Exception $e) {
            // If query fails, assume index doesn't exist
            return false;
        }
    }
};

