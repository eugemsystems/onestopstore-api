<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('condition', ['damaged', 'refurbished', 'as-is'])->default('damaged');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->json('images')->nullable()->comment('Array of image URLs/paths');
            $table->decimal('starting_price', 10, 2);
            $table->decimal('reserve_price', 10, 2)->nullable();
            $table->decimal('current_bid', 10, 2)->nullable();
            $table->unsignedInteger('bid_count')->default(0);
            $table->decimal('min_bid_increment', 10, 2)->default(1.00);
            $table->enum('status', ['draft', 'active', 'ended', 'cancelled'])->default('draft');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('auto_extend_minutes')->default(5)
                  ->comment('Extend by N minutes when a bid is placed in the last N minutes');
            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('winner_bid', 10, 2)->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_items');
    }
};
