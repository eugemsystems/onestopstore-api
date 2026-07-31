<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_apology_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('cooldown_days')->default(4);
            $table->boolean('auto_send_enabled')->default(false);
            $table->string('auto_send_time', 5)->default('09:00'); // HH:MM
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_apology_settings');
    }
};
