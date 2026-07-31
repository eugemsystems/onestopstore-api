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
        Schema::table('tables', function (Blueprint $table) {
            /**
             * PRODUCTS
             */
            Schema::table('products', function (Blueprint $table) {
                // Single-column indexes
                $table->index('name');
                $table->index('slug');
                $table->index('sku');
                // If you often order by or query by price:
                $table->index('price');
                $table->index('created_at');
            });

            /**
             * PRODUCT IMAGES
             */
            Schema::table('product_images', function (Blueprint $table) {
                $table->index('product_id');
                $table->index('attachment_id');
            });

            /**
             * PRODUCT CATEGORIES (Pivot table)
             */
            Schema::table('product_categories', function (Blueprint $table) {
                // composite index helps (product_id, category_id) lookups
                $table->index(['product_id', 'category_id']);
            });

            /**
             * PRODUCT TAGS (Pivot table)
             */
            Schema::table('product_tags', function (Blueprint $table) {
                $table->index(['product_id', 'tag_id']);
            });

            /**
             * PRODUCT ATTRIBUTES (Pivot table)
             */
            Schema::table('product_attributes', function (Blueprint $table) {
                $table->index(['product_id', 'attribute_id']);
            });

            /**
             * VARIATIONS
             */
            Schema::table('variations', function (Blueprint $table) {
                // Already has a unique on sku, but we can index product_id
                $table->index('product_id');
                $table->index('variation_image_id');
                $table->index('stock_status');
                $table->index('status');
            });

            /**
             * VARIATION ATTRIBUTE VALUES
             */
            Schema::table('variation_attribute_values', function (Blueprint $table) {
                $table->index('variation_id');
                $table->index('attribute_value_id');
            });

            /**
             * RELATED PRODUCTS
             */
            Schema::table('related_products', function (Blueprint $table) {
                $table->index('product_id');
                $table->index('related_product_id');
            });

            /**
             * CROSS SELL PRODUCTS
             */
            Schema::table('cross_sell_products', function (Blueprint $table) {
                $table->index('product_id');
                $table->index('cross_sell_product_id');
            });

            /**
             * CATEGORIES
             */
            Schema::table('categories', function (Blueprint $table) {
                // We'll add indexes if you frequently query by slug or parent_id
                $table->index('slug');
                $table->index('parent_id');
                $table->index('created_by_id');
            });

            /**
             * ATTACHMENTS
             */
            Schema::table('attachments', function (Blueprint $table) {
                // If you frequently filter by disk or model_type
                $table->index('disk');
                $table->index('model_type');
                $table->index('model_id');
            });

            /**
             * ATTRIBUTES
             */
            Schema::table('attributes', function (Blueprint $table) {
                $table->index('slug');
                $table->index('created_by_id');
            });

            /**
             * ATTRIBUTE VALUES
             */
            Schema::table('attribute_values', function (Blueprint $table) {
                $table->index('slug');
                $table->index('attribute_id');
                $table->index('created_by_id');
            });

            /**
             * TAGS
             */
            Schema::table('tags', function (Blueprint $table) {
                $table->index('slug');
                $table->index('created_by_id');
            });

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the indexes you added in up()
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropIndex(['sku']);
            $table->dropIndex(['store_id']);
            $table->dropIndex(['created_by_id']);
            $table->dropIndex(['tax_id']);
            $table->dropIndex(['stock_status']);
            $table->dropIndex(['price']);
            $table->dropIndex(['status']);
        });
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropIndex(['attachment_id']);
        });
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'category_id']);
        });
        Schema::table('product_tags', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'tag_id']);
        });
        Schema::table('product_attributes', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'attribute_id']);
        });
        Schema::table('variations', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropIndex(['variation_image_id']);
            $table->dropIndex(['stock_status']);
            $table->dropIndex(['status']);
        });
        Schema::table('variation_attribute_values', function (Blueprint $table) {
            $table->dropIndex(['variation_id']);
            $table->dropIndex(['attribute_value_id']);
        });
        Schema::table('related_products', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropIndex(['related_product_id']);
        });
        Schema::table('cross_sell_products', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropIndex(['cross_sell_product_id']);
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['created_by_id']);
        });
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropIndex(['disk']);
            $table->dropIndex(['model_type']);
            $table->dropIndex(['model_id']);
        });
        Schema::table('attributes', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropIndex(['created_by_id']);
        });
        Schema::table('attribute_values', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropIndex(['attribute_id']);
            $table->dropIndex(['created_by_id']);
        });
        Schema::table('tags', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropIndex(['created_by_id']);
        });
    }
};
