<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('layby_applications', function (Blueprint $table) {
            // How many ID-document reminder emails have been sent
            $table->unsignedTinyInteger('id_document_reminder_count')->default(0)->after('id_document_number');
            // When the last reminder was sent (null = never)
            $table->timestamp('id_document_last_reminder_at')->nullable()->after('id_document_reminder_count');
        });
    }

    public function down(): void
    {
        Schema::table('layby_applications', function (Blueprint $table) {
            $table->dropColumn(['id_document_reminder_count', 'id_document_last_reminder_at']);
        });
    }
};

