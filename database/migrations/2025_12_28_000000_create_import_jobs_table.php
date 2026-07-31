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
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id', 100)->index(); // Group multiple file imports together
            $table->string('filename'); // The JSON file being processed
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable(); // Error details if failed

            // Statistics
            $table->integer('total_items')->default(0);
            $table->integer('processed_items')->default(0);
            $table->integer('updated_items')->default(0);
            $table->integer('skipped_items')->default(0);
            $table->integer('failed_items')->default(0);

            // Skip reasons breakdown (JSON)
            $table->json('skip_reasons')->nullable();

            // Import settings used
            $table->decimal('percentage', 8, 4)->nullable();
            $table->decimal('todays_rate', 12, 6)->nullable();

            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable(); // Calculated: completed_at - started_at

            // User who initiated the import
            $table->unsignedBigInteger('user_id')->nullable();

            $table->timestamps();

            // Indexes for querying
            $table->index(['batch_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_jobs');
    }
};

