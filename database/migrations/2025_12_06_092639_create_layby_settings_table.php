<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('layby_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('layby_settings')->insert([
            [
                'key' => 'sale_products_deposit_percentage',
                'value' => '30',
                'description' => 'Deposit percentage for products on sale',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'sale_products_duration_months',
                'value' => '3',
                'description' => 'Payment duration in months for products on sale',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'regular_products_deposit_percentage',
                'value' => '30',
                'description' => 'Deposit percentage for regular products',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'regular_products_duration_months',
                'value' => '6',
                'description' => 'Payment duration in months for regular products (comma-separated options)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'require_id_upload',
                'value' => '1',
                'description' => 'Require ID document upload for layby applications (1=yes, 0=no)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('layby_settings');
    }
};
