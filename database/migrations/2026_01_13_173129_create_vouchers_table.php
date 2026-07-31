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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // Unique voucher code
            $table->decimal('amount', 10, 2); // Voucher value
            $table->string('currency_code', 3)->default('USD'); // Currency
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null'); // Gift card product
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null'); // Order that generated this voucher
            $table->foreignId('purchased_by')->nullable()->constrained('users')->onDelete('set null'); // User who purchased
            $table->foreignId('redeemed_by')->nullable()->constrained('users')->onDelete('set null'); // User who redeemed
            $table->enum('status', ['active', 'redeemed', 'expired', 'cancelled'])->default('active');
            $table->timestamp('redeemed_at')->nullable(); // When it was redeemed
            $table->timestamp('expires_at')->nullable(); // Expiration date (if any)
            $table->text('notes')->nullable(); // Additional notes
            $table->timestamps();

            $table->index('code');
            $table->index('status');
            $table->index(['purchased_by', 'created_at']);
            $table->index(['redeemed_by', 'redeemed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
