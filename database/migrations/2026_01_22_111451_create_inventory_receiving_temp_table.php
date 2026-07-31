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
        Schema::create('inventory_receiving_temp', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('shipment_id')->index();
            $table->string('order_number')->nullable();
            $table->string('product_name');
            $table->integer('quantity')->default(1);
            $table->string('destination')->nullable();
            $table->json('qr_data')->nullable(); // Store full QR data for reference
            $table->timestamp('scanned_at');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('shipment_id')->references('id')->on('inventory_shipments')->onDelete('cascade');
            
            // Unique constraint - prevent duplicate scans
            $table->unique(['user_id', 'shipment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_receiving_temp');
    }
};
