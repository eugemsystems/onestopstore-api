<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique()->index();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('os', 100)->nullable();
            $table->string('platform', 100)->nullable(); // web, mobile app, etc
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('landing_page', 500)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('total_page_views')->default(0);
            $table->integer('total_events')->default(0);
            $table->integer('duration')->nullable(); // total session duration in seconds
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
