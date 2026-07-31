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
        Schema::create('invoices_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->unique(); // INV-2026-001, QUO-2026-001, etc.
            $table->enum('document_type', ['invoice', 'quotation', 'receipt', 'proforma', 'delivery_note']);
            $table->string('currency_code', 3); // USD, ZWL, ZMW

            // Customer Details
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('customer_address')->nullable();
            $table->unsignedBigInteger('user_id')->nullable(); // If linked to existing user

            // Amounts
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->enum('discount_type', ['percentage', 'amount'])->default('amount');
            $table->decimal('discount_value', 15, 2)->default(0); // Store original discount input
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('vat_percentage', 5, 2)->default(15); // Default 15% VAT
            $table->boolean('include_vat')->default(true);
            $table->decimal('total_amount', 15, 2)->default(0);

            // Additional Info
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->date('valid_until')->nullable(); // For quotations

            // Status
            $table->enum('status', ['draft', 'sent', 'paid', 'cancelled', 'expired'])->default('draft');

            // Metadata
            $table->unsignedBigInteger('created_by'); // Admin user who created it
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            $table->index('document_type');
            $table->index('currency_code');
            $table->index('status');
            $table->index('issue_date');
        });

        Schema::create('invoice_quotation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_quotation_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('variation_id')->nullable();

            // Product Details (snapshot at time of creation)
            $table->string('product_name');
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();

            // Pricing
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);

            $table->timestamps();

            $table->foreign('invoice_quotation_id')->references('id')->on('invoices_quotations')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('variation_id')->references('id')->on('variations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_quotation_items');
        Schema::dropIfExists('invoices_quotations');
    }
};

