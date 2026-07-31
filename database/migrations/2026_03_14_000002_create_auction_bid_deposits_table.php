<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_bid_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('auction_item_id')->constrained('auction_items')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->nullable();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamp('paid_at')->nullable()->comment('null = pending, set when payment confirmed');
            $table->timestamps();

            // A user can only have one deposit record per auction
            $table->unique(['user_id', 'auction_item_id']);
            $table->index(['auction_item_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_bid_deposits');
    }
};
