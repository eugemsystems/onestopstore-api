<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('image_url')->nullable();
            $table->string('model_id')->nullable();
            $table->string('model_type')->nullable();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('collection_name')->nullable();
            $table->string('name')->nullable();
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('disk')->default('public')->nullable();
            $table->string('conversions_disk')->default('public')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->jsonb('manipulations')->nullable();
            $table->jsonb('custom_properties')->nullable();
            $table->jsonb('generated_conversions')->nullable();
            $table->jsonb('responsive_images')->nullable();
            $table->unsignedInteger('order_column')->nullable()->index();
            $table->bigInteger('created_by_id')->unsigned()->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attachments');
    }
};
