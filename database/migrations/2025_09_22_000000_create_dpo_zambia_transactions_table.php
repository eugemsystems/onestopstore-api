<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dpo_zambia_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->string('trans_id')->nullable();
            $table->string('transaction_token')->nullable();
            $table->string('result')->nullable();
            $table->string('result_code')->nullable();
            $table->string('result_explanation')->nullable();
            $table->string('transaction_status')->nullable();
            $table->string('ccd_approval')->nullable();
            $table->string('company_ref')->nullable();
            $table->string('transaction_currency')->nullable();
            $table->decimal('payment_amount', 18, 2)->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_country')->nullable();
            $table->boolean('fraud_alert')->nullable();
            $table->string('fraud_explanation')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_approved')->nullable();
            $table->json('raw_response')->nullable();
            $table->json('other_fields')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dpo_zambia_transactions');
    }
};
