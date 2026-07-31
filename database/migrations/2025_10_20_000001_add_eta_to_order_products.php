<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            if (!Schema::hasColumn('order_products', 'eta')) {
                $table->date('eta')->nullable()->after('item_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            if (Schema::hasColumn('order_products', 'eta')) {
                $table->dropColumn('eta');
            }
        });
    }
};
https://passport.yandex.ru/auth?retpath=https%3A%2F%2Fmetrika.yandex.ru%2Flist%3Futm_source%3Dpromo%26utm_medium%3Dproduct
