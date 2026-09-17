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
        Schema::create('daily_feed_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Defaults: scheduler runs 4 AM, all 4 feeds enabled, no minimum price floor.
        DB::table('daily_feed_settings')->insert([
            [
                'key' => 'schedule_time',
                'value' => '04:00',
                'description' => 'Time of day (HH:MM, server time) the daily regional feed generation runs.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'global_usd_enabled',
                'value' => '1',
                'description' => 'Generate the Global (USD) feed.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'global_usd_min_price',
                'value' => '0',
                'description' => 'Global (USD) feed: only include products priced at or above this amount (USD).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'global_usd_limit',
                'value' => '0',
                'description' => 'Global (USD) feed: maximum products to include (0 = no limit).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'global_zmw_enabled',
                'value' => '1',
                'description' => 'Generate the Global (ZMW) feed.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'global_zmw_min_price',
                'value' => '0',
                'description' => 'Global (ZMW) feed: only include products priced at or above this amount (ZMW).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'global_zmw_limit',
                'value' => '0',
                'description' => 'Global (ZMW) feed: maximum products to include (0 = no limit).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'zimbabwe_usd_enabled',
                'value' => '1',
                'description' => 'Generate the Zimbabwe (USD) feed.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'zimbabwe_usd_min_price',
                'value' => '0',
                'description' => 'Zimbabwe (USD) feed: only include products priced at or above this amount (USD).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'zimbabwe_usd_limit',
                'value' => '0',
                'description' => 'Zimbabwe (USD) feed: maximum products to include (0 = no limit).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'zambia_zmw_enabled',
                'value' => '1',
                'description' => 'Generate the Zambia (ZMW) feed.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'zambia_zmw_min_price',
                'value' => '0',
                'description' => 'Zambia (ZMW) feed: only include products priced at or above this amount (ZMW).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'zambia_zmw_limit',
                'value' => '0',
                'description' => 'Zambia (ZMW) feed: maximum products to include (0 = no limit).',
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
        Schema::dropIfExists('daily_feed_settings');
    }
};
