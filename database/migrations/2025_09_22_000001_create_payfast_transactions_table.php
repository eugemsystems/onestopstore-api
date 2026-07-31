<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
//        Schema::create('payfast_transactions', function (Blueprint $table) {
//            $table->id();
//            $table->unsignedBigInteger('order_id')->index();
//            $table->string('gateway_transaction_id')->nullable();
//            $table->string('status')->nullable();
//            $table->decimal('amount', 18, 2)->nullable();
//            $table->string('currency')->nullable();
//            $table->json('raw_response')->nullable();
//            $table->json('other_fields')->nullable();
//            $table->timestamps();
//            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
//        });
    }

    public function down(): void
    {
        //Schema::dropIfExists('payfast_transactions');
    }
};
