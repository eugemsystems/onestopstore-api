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
        Schema::create('marketing_feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('order_number')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();

            // Feedback fields
            $table->enum('ordering_process_rating', ['excellent', 'good', 'fair', 'poor']);
            $table->string('heard_about_source');
            $table->string('heard_about_other')->nullable(); // For "Other" option

            // User information (in case user is not logged in)
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->string('user_phone')->nullable();

            // Additional data
            $table->text('additional_comments')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');

            // Indexes
            $table->index('order_number');
            $table->index('heard_about_source');
            $table->index('ordering_process_rating');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_feedback');
    }
};

