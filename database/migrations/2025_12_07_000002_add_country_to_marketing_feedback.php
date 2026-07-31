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
        Schema::table('marketing_feedback', function (Blueprint $table) {
            $table->string('country_code', 2)->nullable()->after('ip_address'); // ISO 2-letter code
            $table->string('country_name')->nullable()->after('country_code');

            // Add index for country analytics
            $table->index('country_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketing_feedback', function (Blueprint $table) {
            $table->dropIndex(['country_code']);
            $table->dropColumn(['country_code', 'country_name']);
        });
    }
};

