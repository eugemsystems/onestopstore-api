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
        // Drop the existing unique constraint
        DB::statement('ALTER TABLE invoices_quotations DROP CONSTRAINT IF EXISTS invoices_quotations_document_number_unique');

        // Add a partial unique index that excludes autosave documents
        // This allows multiple autosave documents with different numbers but ensures non-autosave documents are unique
        DB::statement('CREATE UNIQUE INDEX invoices_quotations_document_number_unique ON invoices_quotations (document_number) WHERE status != \'autosave\'');

        // Clean up any duplicate autosave documents
        DB::statement("
            DELETE FROM invoices_quotations
            WHERE id IN (
                SELECT id FROM (
                    SELECT id,
                           ROW_NUMBER() OVER (PARTITION BY created_by, document_type ORDER BY updated_at DESC) as rn
                    FROM invoices_quotations
                    WHERE status = 'autosave'
                ) t
                WHERE t.rn > 1
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the partial unique index
        DB::statement('DROP INDEX IF EXISTS invoices_quotations_document_number_unique');

        // Restore the original unique constraint
        Schema::table('invoices_quotations', function (Blueprint $table) {
            $table->unique('document_number');
        });
    }
};

