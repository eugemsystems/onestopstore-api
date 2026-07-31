<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('search_keywords')->nullable();
        });

        DB::statement("CREATE INDEX products_search_keywords_idx ON products USING gin(to_tsvector('english', search_keywords))");

//        DB::statement("
//            UPDATE products p
//            SET search_keywords =
//                INITCAP(
//                    CONCAT_WS(' ',
//                        INITCAP(p.name),
//                        INITCAP(p.sku),
//                        COALESCE((
//                            SELECT string_agg(INITCAP(c.name), ' ')
//                            FROM categories c
//                            JOIN product_categories pc ON pc.category_id = c.id
//                            WHERE pc.product_id = p.id
//                        ), ''),
//                        COALESCE((
//                            SELECT string_agg(INITCAP(t.name), ' ')
//                            FROM tags t
//                            JOIN product_tags pt ON pt.tag_id = t.id
//                            WHERE pt.product_id = p.id
//                        ), '')
//                    )
//                )
//        ");

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_keywords');
        DB::statement("DROP INDEX products_search_keywords_idx");
        //DB::statement("UPDATE products SET search_keywords = NULL");
    }
};
