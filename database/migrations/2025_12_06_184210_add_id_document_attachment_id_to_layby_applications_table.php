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
        Schema::table('layby_applications', function (Blueprint $table) {
            // Add attachment ID to reference laravel-media attachments (like products do)
            $table->unsignedBigInteger('id_document_attachment_id')->nullable()->after('id_document_path');

            // Keep id_document_path for backward compatibility (can be removed later)
            // New applications will use id_document_attachment_id
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('layby_applications', function (Blueprint $table) {
            $table->dropColumn('id_document_attachment_id');
        });
    }
};

