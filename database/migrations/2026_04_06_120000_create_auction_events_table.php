<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'analytics';

    public function up(): void
    {
        if (Schema::connection('analytics')->hasTable('auction_events')) {
            return;
        }

        Schema::connection('analytics')->create('auction_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('auction_item_id')->index();
            $table->string('event', 50)->index(); // page_view, bid_focus, bid_submit, bid_success, bid_error, image_click, product_link_click, countdown_expired, tab_switch
            $table->json('meta')->nullable();     // bid amount, error msg, image index, tab name, etc.
            $table->string('session_id', 64)->nullable()->index(); // anonymous browser session
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            // No FK to main DB — cross-DB foreign keys not supported
        });
    }

    public function down(): void
    {
        Schema::connection('analytics')->dropIfExists('auction_events');
    }
};
