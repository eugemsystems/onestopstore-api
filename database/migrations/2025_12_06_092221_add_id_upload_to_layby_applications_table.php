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
            $table->string('id_document_path')->nullable()->after('variation_display_name');
            $table->string('id_document_type')->nullable()->after('id_document_path');
            $table->string('id_document_number')->nullable()->after('id_document_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('layby_applications', function (Blueprint $table) {
            $table->dropColumn(['id_document_path', 'id_document_type', 'id_document_number']);
        });
    }
};
