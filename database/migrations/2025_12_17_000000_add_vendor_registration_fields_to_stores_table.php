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
        Schema::table('stores', function (Blueprint $table) {
            // VAT & Identification
            $table->enum('is_vat_registered', ['yes', 'no'])->nullable()->after('vendor_id');
            $table->string('vat_number')->nullable()->after('is_vat_registered');
            $table->enum('identification_type', ['id', 'passport'])->nullable()->after('vat_number');
            $table->string('id_number')->nullable()->after('identification_type');

            // Business Identifiers
            $table->string('legal_name')->nullable()->after('id_number');
            $table->string('trading_name')->nullable()->after('legal_name');

            // Business Information
            $table->string('monthly_revenue')->nullable()->after('trading_name');
            $table->enum('has_physical_stores', ['yes', 'no'])->nullable()->after('monthly_revenue');
            $table->integer('number_of_stores')->nullable()->after('has_physical_stores');
            $table->enum('is_supplier_to_retailers', ['yes', 'no'])->nullable()->after('number_of_stores');
            $table->enum('has_marketplace_accounts', ['yes', 'no'])->nullable()->after('is_supplier_to_retailers');

            // Product Range & Brands
            $table->integer('number_of_products')->nullable()->after('has_marketplace_accounts');
            $table->string('primary_category')->nullable()->after('number_of_products');
            $table->string('stock_holding')->nullable()->after('primary_category');
            $table->string('product_source')->nullable()->after('stock_holding');
            $table->string('product_branding')->nullable()->after('product_source');
            $table->text('owned_brands')->nullable()->after('product_branding');
            $table->text('reseller_brands')->nullable()->after('owned_brands');

            // Online Presence
            $table->string('website')->nullable()->after('pinterest');
            $table->string('social_media_page')->nullable()->after('website');
            $table->bigInteger('product_catalog_id')->unsigned()->nullable()->after('social_media_page');
            $table->text('business_summary')->nullable()->after('product_catalog_id');
            $table->text('product_uniqueness')->nullable()->after('business_summary');
            $table->text('intended_products')->nullable()->after('product_uniqueness');
            $table->string('certifications')->nullable()->after('intended_products');
            $table->string('referral_source')->nullable()->after('certifications');

            // Add foreign key for product catalog
            $table->foreign('product_catalog_id')->references('id')->on('attachments')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stores', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['product_catalog_id']);

            // Drop columns
            $table->dropColumn([
                'is_vat_registered',
                'vat_number',
                'identification_type',
                'id_number',
                'legal_name',
                'trading_name',
                'monthly_revenue',
                'has_physical_stores',
                'number_of_stores',
                'is_supplier_to_retailers',
                'has_marketplace_accounts',
                'number_of_products',
                'primary_category',
                'stock_holding',
                'product_source',
                'product_branding',
                'owned_brands',
                'reseller_brands',
                'website',
                'social_media_page',
                'product_catalog_id',
                'business_summary',
                'product_uniqueness',
                'intended_products',
                'certifications',
                'referral_source',
            ]);
        });
    }
};

