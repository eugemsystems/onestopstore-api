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
        // Change value column from integer to decimal(10,2) to support prices like 77.13
        DB::statement('ALTER TABLE user_events ALTER COLUMN value TYPE NUMERIC(10,2)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to integer (will lose decimal precision)
        DB::statement('ALTER TABLE user_events ALTER COLUMN value TYPE INTEGER USING value::integer');
    }
};

