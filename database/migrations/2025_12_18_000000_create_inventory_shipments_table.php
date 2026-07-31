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
        Schema::create('inventory_shipments', function (Blueprint $table) {
            $table->id();
            $table->integer('order')->nullable()->comment('Display order/priority');
            $table->string('title');
            $table->integer('quantity');
            $table->string('destination')->nullable();
            $table->string('status')->nullable();
            $table->string('transporter')->nullable();
            $table->date('date')->nullable()->comment('Shipment date');
            $table->date('eta')->nullable()->comment('Estimated time of arrival');
            $table->string('f_status')->nullable()->comment('Final/Freight Status');
            $table->string('signed_by')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null')->comment('Staff or Admin user ID');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for better performance
            $table->index('destination');
            $table->index('status');
            $table->index('date');
            $table->index('eta');
            $table->index('created_at');
        });

        Schema::create('inventory_shipment_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('inventory_shipments')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action')->comment('created, updated, deleted, restored');
            $table->text('changes')->nullable()->comment('JSON of what changed');
            $table->text('old_values')->nullable()->comment('JSON of old values');
            $table->text('new_values')->nullable()->comment('JSON of new values');
            $table->timestamps();

            // Index for querying history
            $table->index(['shipment_id', 'created_at']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_shipment_history');
        Schema::dropIfExists('inventory_shipments');
    }
};

