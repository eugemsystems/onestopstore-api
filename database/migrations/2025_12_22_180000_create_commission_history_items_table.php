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
        Schema::create('commission_history_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_history_id')->constrained('commission_histories')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            $table->decimal('product_price', 10, 2);
            $table->integer('quantity')->default(1);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('commission_rate', 5, 2)->comment('Commission percentage applied (e.g., 15.00 for 15%)');
            $table->string('commission_source')->default('category')->comment('category, default, or manual');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('category_name')->nullable();
            $table->decimal('admin_commission', 10, 2);
            $table->decimal('vendor_commission', 10, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['commission_history_id', 'product_id']);
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_history_items');
    }
};

