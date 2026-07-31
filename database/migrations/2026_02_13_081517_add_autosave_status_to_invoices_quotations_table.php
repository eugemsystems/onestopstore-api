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
        // Change status enum to include 'autosave'
        DB::statement("ALTER TABLE invoices_quotations DROP CONSTRAINT IF EXISTS invoices_quotations_status_check");
        DB::statement("ALTER TABLE invoices_quotations ALTER COLUMN status TYPE VARCHAR(50)");
        DB::statement("ALTER TABLE invoices_quotations ADD CONSTRAINT invoices_quotations_status_check CHECK (status IN ('autosave', 'draft', 'sent', 'paid', 'cancelled', 'expired'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE invoices_quotations DROP CONSTRAINT IF EXISTS invoices_quotations_status_check");
        DB::statement("ALTER TABLE invoices_quotations ADD CONSTRAINT invoices_quotations_status_check CHECK (status IN ('draft', 'sent', 'paid', 'cancelled', 'expired'))");
    }
};


