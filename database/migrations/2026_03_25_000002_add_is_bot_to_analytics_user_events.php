<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'analytics';

    public function up(): void
    {
        if (Schema::connection('analytics')->hasTable('user_events') &&
            !Schema::connection('analytics')->hasColumn('user_events', 'is_bot')) {
            Schema::connection('analytics')->table('user_events', function (Blueprint $table) {
                $table->boolean('is_bot')->default(false)->after('value')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection('analytics')->hasTable('user_events')) {
            Schema::connection('analytics')->table('user_events', function (Blueprint $table) {
                $table->dropColumn('is_bot');
            });
        }
    }
};
