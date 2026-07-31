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
        Schema::create('cash_book_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->after('id');
            $table->string('slug')->unique()->after('name');
            $table->enum('type', ['income', 'expense', 'both'])->default('both')->after('slug');
            $table->string('color')->nullable()->after('type');
            $table->text('description')->nullable()->after('color');
            $table->boolean('is_active')->default(true)->after('description');
            $table->softDeletes()->after('updated_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_book_categories');
    }
};
