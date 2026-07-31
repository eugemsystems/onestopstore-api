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
        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->string('query')->index();
            $table->string('normalized_query')->nullable()->index();
            $table->unsignedInteger('results_count')->default(0);
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('session_id')->nullable()->index();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('filters')->nullable();
            $table->string('sort_by')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['query', 'created_at']);
            $table->index(['normalized_query', 'created_at']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_queries');
    }
};
