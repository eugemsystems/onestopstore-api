<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_feed_definitions', function (Blueprint $table) {
            $table->decimal('min_price', 10, 2)->default(0)->after('currency');
            $table->unsignedInteger('shard_count')->default(1)->after('min_price');
        });
    }

    public function down(): void
    {
        Schema::table('custom_feed_definitions', function (Blueprint $table) {
            $table->dropColumn(['min_price', 'shard_count']);
        });
    }
};
