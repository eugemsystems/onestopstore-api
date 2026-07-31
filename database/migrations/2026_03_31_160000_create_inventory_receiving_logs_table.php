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
        Schema::create('inventory_receiving_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('branch')->default('None')->index();
            $table->unsignedBigInteger('shipment_id')->index();
            $table->string('order_number')->nullable();
            $table->string('product_name');
            $table->integer('quantity')->default(1);
            $table->string('destination')->nullable();
            $table->timestamp('scanned_at');
            $table->timestamp('saved_at');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Index for common filter queries
            $table->index(['branch', 'saved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_receiving_logs');
    }
};
