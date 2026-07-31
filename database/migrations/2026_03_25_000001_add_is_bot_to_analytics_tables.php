<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The database connection that should be used by the migration.
     *
     * @var string
     */
    protected $connection = 'analytics';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add is_bot flag to user_sessions
        if (Schema::connection('analytics')->hasTable('user_sessions') &&
            !Schema::connection('analytics')->hasColumn('user_sessions', 'is_bot')) {
            Schema::connection('analytics')->table('user_sessions', function (Blueprint $table) {
                $table->boolean('is_bot')->default(false)->after('duration')->index();
            });
        }

        // Add is_bot flag to page_views
        if (Schema::connection('analytics')->hasTable('page_views') &&
            !Schema::connection('analytics')->hasColumn('page_views', 'is_bot')) {
            Schema::connection('analytics')->table('page_views', function (Blueprint $table) {
                $table->boolean('is_bot')->default(false)->after('duration')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::connection('analytics')->hasTable('user_sessions')) {
            Schema::connection('analytics')->table('user_sessions', function (Blueprint $table) {
                $table->dropColumn('is_bot');
            });
        }

        if (Schema::connection('analytics')->hasTable('page_views')) {
            Schema::connection('analytics')->table('page_views', function (Blueprint $table) {
                $table->dropColumn('is_bot');
            });
        }
    }
};
