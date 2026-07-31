<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_bans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('auction_item_id')->constrained('auction_items')->cascadeOnDelete();
            $table->timestamp('banned_at')->useCurrent();
            $table->timestamp('lifted_at')->nullable();
            $table->foreignId('lifted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('lift_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'lifted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_bans');
    }
};
