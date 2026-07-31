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
        if (!Schema::connection('analytics')->hasTable('page_views')) {
            Schema::connection('analytics')->create('page_views', function (Blueprint $table) {
                $table->id();
                $table->string('session_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('url', 500);
                $table->string('path', 500);
                $table->string('page_title')->nullable();
                $table->string('referrer', 500)->nullable();
                $table->string('utm_source')->nullable();
                $table->string('utm_medium')->nullable();
                $table->string('utm_campaign')->nullable();
                $table->string('utm_term')->nullable();
                $table->string('utm_content')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->string('device_type', 50)->nullable(); // mobile, tablet, desktop
                $table->string('browser', 100)->nullable();
                $table->string('os', 100)->nullable();
                $table->string('country', 100)->nullable();
                $table->string('city', 100)->nullable();
                $table->integer('duration')->nullable(); // time spent on page in seconds
                $table->timestamps();

                $table->index(['created_at']);
                $table->index(['user_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('analytics')->dropIfExists('page_views');
    }
};
