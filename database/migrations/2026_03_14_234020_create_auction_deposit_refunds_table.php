<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_deposit_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('auction_item_id')->constrained('auction_items')->cascadeOnDelete();
            $table->foreignId('auction_bid_deposit_id')->constrained('auction_bid_deposits')->cascadeOnDelete();
            $table->decimal('deposit_amount', 10, 2)->comment('Full deposit paid');
            $table->decimal('winner_bid_amount', 10, 2)->default(0)->comment('Winning bid (0 if deposit only / non-winner refund)');
            $table->decimal('refund_amount', 10, 2)->comment('Amount to be refunded (deposit - winner_bid, or full deposit if non-winner)');
            $table->enum('reason_type', ['lost_auction', 'deposit_overpaid', 'auction_cancelled', 'other'])->default('other');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['auction_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_deposit_refunds');
    }
};
