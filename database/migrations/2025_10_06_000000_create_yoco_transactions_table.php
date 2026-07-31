<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yoco_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('gateway_transaction_id')->nullable()->index();
            $table->string('status')->nullable()->index();
            $table->bigInteger('amount_cents')->nullable();
            $table->string('currency', 10)->nullable();
            $table->json('raw_response')->nullable();
            $table->json('other_fields')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->index(['order_id','gateway_transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yoco_transactions');
    }
};
