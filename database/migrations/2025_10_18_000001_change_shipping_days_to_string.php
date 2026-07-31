<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Change shipping days columns from integer to string to support ranges like "10-14"
            $table->string('standard_shipping_days')->nullable()->change();
            $table->string('expedited_shipping_days')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('standard_shipping_days')->nullable()->change();
            $table->integer('expedited_shipping_days')->nullable()->change();
        });
    }
};

