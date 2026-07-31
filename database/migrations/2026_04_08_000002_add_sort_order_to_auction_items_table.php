<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auction_items', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('id');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('auction_items', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
