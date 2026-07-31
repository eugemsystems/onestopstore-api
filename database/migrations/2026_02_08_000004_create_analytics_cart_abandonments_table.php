<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The database connection that should be used by the migration.
     *
     * @var string
     */
    protected $connection = 'analytics';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::connection('analytics')->hasTable('cart_abandonments')) {
            Schema::connection('analytics')->create('cart_abandonments', function (Blueprint $table) {
                $table->id();
                $table->string('session_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('email')->nullable()->index();
                $table->json('cart_items'); // store cart contents
                $table->decimal('cart_value', 10, 2)->nullable();
                $table->string('currency', 3)->default('USD');
                $table->integer('items_count')->default(0);
                $table->string('abandonment_stage', 50); // cart, checkout_start, checkout_shipping, checkout_payment
                $table->text('abandonment_reason')->nullable();
                $table->boolean('recovered')->default(false)->index();
                $table->unsignedBigInteger('order_id')->nullable();
                $table->timestamp('recovered_at')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('device_type', 50)->nullable();
                $table->timestamps();

                $table->index(['created_at']);
                $table->index(['user_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('analytics')->dropIfExists('cart_abandonments');
    }
};
