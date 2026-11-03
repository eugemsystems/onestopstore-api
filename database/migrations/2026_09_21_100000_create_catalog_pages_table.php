<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_pages', function (Blueprint $table) {
            $table->id();
            // The permanent part of the public URL: /en/catalog/{slug}. Never changes after creation.
            $table->string('slug', 100)->unique();
            $table->string('title', 150)->nullable();
            // Where the current HTML file lives on the "public" disk. Replacing the file
            // overwrites this path, so the URL keeps serving whatever was uploaded last.
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_pages');
    }
};
