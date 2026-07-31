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
        Schema::table('categories', function (Blueprint $table) {
            $table->uuid('category_image_uuid')->nullable()->after('category_image_id')->references('uuid')->on('attachments')->onDelete('cascade');
            $table->uuid('category_icon_uuid')->nullable()->after('category_icon_id')->references('uuid')->on('attachments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('category_image_uuid');
            $table->dropColumn('category_icon_uuid');
        });
    }
};
