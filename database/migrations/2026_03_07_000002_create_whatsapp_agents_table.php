<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('whatsapp_number');
            $table->foreignId('job_title_id')->nullable()->constrained('whatsapp_job_titles')->nullOnDelete();
            $table->string('branch')->default('None');
            $table->text('profile_picture_url')->nullable();
            $table->boolean('chat_enabled')->default(false);
            $table->time('available_from')->nullable();
            $table->time('available_to')->nullable();
            $table->json('available_days')->nullable(); // e.g. ["Mon","Tue","Wed","Thu","Fri"]
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_agents');
    }
};
