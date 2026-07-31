<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('layby_applications', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('completed_at')
                  ->constrained('orders')->onDelete('set null');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('layby_applications', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
        });
    }
};

