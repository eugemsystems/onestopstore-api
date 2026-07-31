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
        Schema::table('users', function (Blueprint $table) {
            $table->string('preferred_currency')->default('USD')->after('email');
            $table->string('currency_symbol')->default('$')->after('preferred_currency');
            $table->decimal('currency_exchange_rate', 10, 4)->default(1.0000)->after('currency_symbol');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['preferred_currency', 'currency_symbol', 'currency_exchange_rate']);
        });
    }
};

