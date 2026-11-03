<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_pages', function (Blueprint $table) {
            // Real visitors ("clicks") vs. crawlers/link-preview bots, counted separately.
            $table->unsignedBigInteger('hits_total')->default(0)->after('is_active');
            $table->unsignedBigInteger('bot_hits_total')->default(0)->after('hits_total');
            $table->timestamp('last_hit_at')->nullable()->after('bot_hits_total');
        });

        Schema::create('catalog_page_daily_hits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_page_id')->constrained('catalog_pages')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('hits')->default(0);
            $table->unsignedInteger('bot_hits')->default(0);
            $table->unique(['catalog_page_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_page_daily_hits');
        Schema::table('catalog_pages', function (Blueprint $table) {
            $table->dropColumn(['hits_total', 'bot_hits_total', 'last_hit_at']);
        });
    }
};
