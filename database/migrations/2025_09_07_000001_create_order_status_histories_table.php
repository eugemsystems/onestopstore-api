<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('old_status_id')->nullable();
            $table->unsignedBigInteger('new_status_id');
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();

            $table->foreign('order_id')
                ->references('id')->on('orders')
                ->onDelete('cascade');

            // References the `order_status` table (as used by existing migrations)
            $table->foreign('old_status_id')
                ->references('id')->on('order_status')
                ->onDelete('set null');

            $table->foreign('new_status_id')
                ->references('id')->on('order_status')
                ->onDelete('restrict');

            $table->foreign('updated_by_id')
                ->references('id')->on('users')
                ->onDelete('set null');

            $table->index(['order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};
