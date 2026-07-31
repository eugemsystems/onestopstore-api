<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auction_items', function (Blueprint $table) {
            $table->string('branch')
                  ->nullable()
                  ->default('')
                  ->after('title')
                  ->comment('Branch where the auction is held: Harare, Mutare, Bulawayo, Zambia');
        });
    }

    public function down(): void
    {
        Schema::table('auction_items', function (Blueprint $table) {
            $table->dropColumn('branch');
        });
    }
};
