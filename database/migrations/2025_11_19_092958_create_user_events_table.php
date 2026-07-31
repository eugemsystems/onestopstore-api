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
        Schema::create('user_events', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('event_type', 100); // click, scroll, add_to_cart, remove_from_cart, search, etc
            $table->string('event_category', 100)->nullable(); // engagement, commerce, navigation
            $table->string('event_name', 200);
            $table->json('event_data')->nullable(); // flexible data storage
            $table->string('page_url', 500)->nullable();
            $table->string('element_id')->nullable();
            $table->string('element_class')->nullable();
            $table->text('element_text')->nullable();
            $table->integer('value')->nullable(); // for tracking numeric values
            $table->timestamps();

            $table->index(['event_type', 'created_at']);
            $table->index(['created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_events');
    }
};
