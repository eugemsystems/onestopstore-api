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
        Schema::create('cash_book_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->time('entry_time')->nullable();
            $table->text('remark')->nullable();
            $table->string('party')->nullable(); // Customer/Vendor name
            $table->unsignedBigInteger('category_id')->nullable(); // Will add foreign key after categories table exists
            $table->enum('mode', ['cash', 'bank', 'card', 'mobile_money', 'other'])->default('cash');
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('cash_in', 15, 2)->default(0);
            $table->decimal('cash_out', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('reference_number')->nullable(); // Order number, invoice, etc.
            $table->string('reference_type')->nullable(); // 'order', 'expense', 'manual'
            $table->unsignedBigInteger('reference_id')->nullable(); // ID of related order/record
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('entry_date');
            $table->index('category_id');
            $table->index('entered_by');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_book_entries');
    }
};
