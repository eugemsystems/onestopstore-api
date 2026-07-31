<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 10); // 'android' or 'ios'
            $table->string('latest_version', 20);
            $table->unsignedInteger('latest_build')->default(0);
            $table->string('minimum_version', 20);
            $table->boolean('force_update')->default(false);
            $table->text('release_notes')->nullable();
            $table->string('store_url', 500)->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->unique('platform');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
