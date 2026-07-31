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
        Schema::table('cash_book_entries', function (Blueprint $table) {
            $table->enum('branch', ['Harare', 'Bulawayo', 'Mutare', 'Zambia'])->default('Harare')->after('id');
            $table->index('branch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_book_entries', function (Blueprint $table) {
            $table->dropIndex(['branch']);
            $table->dropColumn('branch');
        });
    }
};
