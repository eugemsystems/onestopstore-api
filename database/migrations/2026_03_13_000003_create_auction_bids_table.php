<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('auction_bids')) {
            return;
        }
        Schema::create('auction_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_item_id')->constrained('auction_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('auction_item_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_bids');
    }
};
