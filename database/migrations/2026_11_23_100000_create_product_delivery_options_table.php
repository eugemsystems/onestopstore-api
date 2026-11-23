<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_delivery_options')) {
            Schema::create('product_delivery_options', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id')->index();
                // Admin-editable name, e.g. "Fastest Delivery", "Best Price"
                $table->string('label');
                // Free text shown to customers, e.g. "2-3 working days"
                $table->string('delivery_text')->nullable();
                // Lower bound in days, used for sorting/ETA only
                $table->unsignedInteger('delivery_days')->nullable();
                $table->decimal('price', 12, 2);
                $table->decimal('sale_price', 12, 2)->nullable();
                $table->integer('quantity')->default(0);
                $table->string('stock_status')->default('in_stock');
                $table->string('sku')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_default')->default(false);
                $table->boolean('status')->default(true);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        Schema::table('carts', function (Blueprint $table) {
            if (!Schema::hasColumn('carts', 'delivery_option_id')) {
                $table->unsignedBigInteger('delivery_option_id')->nullable()->after('variation_id');
            }
        });

        Schema::table('order_products', function (Blueprint $table) {
            if (!Schema::hasColumn('order_products', 'delivery_option_id')) {
                $table->unsignedBigInteger('delivery_option_id')->nullable();
            }
            if (!Schema::hasColumn('order_products', 'delivery_option_label')) {
                $table->string('delivery_option_label')->nullable();
            }
            if (!Schema::hasColumn('order_products', 'delivery_option_text')) {
                $table->string('delivery_option_text')->nullable();
            }
            if (!Schema::hasColumn('order_products', 'delivery_option_price')) {
                $table->decimal('delivery_option_price', 12, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            foreach (['delivery_option_id', 'delivery_option_label', 'delivery_option_text', 'delivery_option_price'] as $c) {
                if (Schema::hasColumn('order_products', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
        Schema::table('carts', function (Blueprint $table) {
            if (Schema::hasColumn('carts', 'delivery_option_id')) {
                $table->dropColumn('delivery_option_id');
            }
        });
        Schema::dropIfExists('product_delivery_options');
    }
};
