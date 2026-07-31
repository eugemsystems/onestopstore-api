<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layby_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layby_application_id')->constrained('layby_applications')->onDelete('cascade');
            $table->string('payment_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('USD');
            $table->string('payment_method')->nullable(); // payfast, yoco, bank_transfer, etc.
            $table->string('transaction_id')->nullable();
            $table->enum('payment_status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->text('payment_note')->nullable();
            $table->json('payment_meta')->nullable(); // Store gateway response
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('captured_by')->nullable()->constrained('users')->onDelete('set null'); // Admin who captured
            $table->timestamps();

            $table->index(['layby_application_id', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('layby_payments');
    }
};

