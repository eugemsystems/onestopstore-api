<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_auto_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_item_id')->constrained('auction_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('max_amount', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // One active auto-bid per user per auction (unique enforced in app layer)
            $table->index(['auction_item_id', 'user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_auto_bids');
    }
};
