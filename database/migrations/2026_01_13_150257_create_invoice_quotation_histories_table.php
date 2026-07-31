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
        Schema::create('invoice_quotation_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_quotation_id')->constrained('invoices_quotations')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action'); // created, updated, status_changed, converted_to_invoice, converted_to_order, etc.
            $table->string('field_name')->nullable(); // field that was changed
            $table->text('old_value')->nullable(); // previous value
            $table->text('new_value')->nullable(); // new value
            $table->text('description'); // human-readable description of the change
            $table->json('metadata')->nullable(); // additional data (e.g., related order_id, invoice_id)
            $table->timestamps();

            $table->index(['invoice_quotation_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_quotation_histories');
    }
};

