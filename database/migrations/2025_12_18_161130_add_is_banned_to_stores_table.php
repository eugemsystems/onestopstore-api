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
        Schema::table('stores', function (Blueprint $table) {
            $table->boolean('is_banned')->default(false)->after('is_approved')->comment('Whether vendor is banned');
            $table->text('ban_reason')->nullable()->after('is_banned')->comment('Reason for banning vendor');
            $table->timestamp('banned_at')->nullable()->after('ban_reason')->comment('When vendor was banned');
            $table->foreignId('banned_by')->nullable()->after('banned_at')->constrained('users')->onDelete('set null')->comment('Admin who banned the vendor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropForeign(['banned_by']);
            $table->dropColumn(['is_banned', 'ban_reason', 'banned_at', 'banned_by']);
        });
    }
};

