+-<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layby_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('variation_id')->nullable()->constrained('variations')->onDelete('set null');
            $table->json('selected_attribute_ids')->nullable();
            $table->string('variation_display_name')->nullable();

            // Product details at time of application
            $table->string('product_name');
            $table->decimal('product_price', 10, 2);
            $table->string('currency', 10)->default('USD');
            $table->string('currency_symbol', 10)->default('$');
            $table->decimal('exchange_rate', 10, 4)->default(1);

            // Layby terms
            $table->integer('duration_months')->default(3); // 3, 6, 12 months
            $table->decimal('deposit_amount', 10, 2);
            $table->decimal('monthly_amount', 10, 2);
            $table->decimal('total_amount', 10, 2);

            // Application status
            $table->enum('status', ['pending', 'approved', 'rejected', 'active', 'completed', 'cancelled'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');

            // Payment tracking
            $table->decimal('total_paid', 10, 2)->default(0);
            $table->decimal('balance_remaining', 10, 2);
            $table->timestamp('last_payment_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('application_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('layby_applications');
    }
};

