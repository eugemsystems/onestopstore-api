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
         // Create system_tickets table
        Schema::create('system_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->string('title');
            $table->text('description');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'testing', 'closed', 'reopened'])->default('open');
            $table->string('category')->nullable();
            $table->json('attachments')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('closed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('priority');
            $table->index('created_by');
            $table->index('assigned_to');
        });

        // Create ticket_activities table
        Schema::create('ticket_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('system_tickets')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('action', ['created', 'updated', 'commented', 'status_changed', 'assigned', 'closed', 'reopened'])->default('updated');
            $table->text('comment')->nullable();
            $table->json('attachments')->nullable();
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->timestamps();

            $table->index('ticket_id');
            $table->index('user_id');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_activities');
        Schema::dropIfExists('system_tickets');
    }
};
