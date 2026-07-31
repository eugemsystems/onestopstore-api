<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AdminProductStatisticsController extends Controller
{
    public function index()
    {
        $stats = Cache::remember('admin.product.statistics', now()->addMinutes(10), function () {

            // ── Summary counts ─────────────────────────────────────────────
            $summary = DB::table('products')->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN status = 1 THEN 1 END) as active,
                COUNT(CASE WHEN status = 0 THEN 1 END) as inactive,
                COUNT(CASE WHEN is_approved = 1 THEN 1 END) as approved,
                COUNT(CASE WHEN is_approved = 0 THEN 1 END) as pending_approval,
                COUNT(CASE WHEN sale_price IS NOT NULL AND sale_price > 0 THEN 1 END) as on_sale,
                COUNT(CASE WHEN is_featured = 1 THEN 1 END) as featured,
                COUNT(CASE WHEN is_free_shipping = 1 THEN 1 END) as free_shipping,
                COUNT(CASE WHEN is_cod = 1 THEN 1 END) as cod,
                COUNT(CASE WHEN is_gift_card = true THEN 1 END) as gift_cards,
                COUNT(CASE WHEN sa_only = true THEN 1 END) as sa_only,
                COUNT(CASE WHEN zambia_only = true THEN 1 END) as zambia_only,
                COUNT(CASE WHEN zimbabwe_only = true THEN 1 END) as zimbabwe_only,
                COUNT(CASE WHEN is_return = 1 THEN 1 END) as returnable,
                COUNT(CASE WHEN type = 'classified' THEN 1 END) as has_variations,
                COUNT(CASE WHEN type = 'simple' THEN 1 END) as simple
            ")->first();

            // ── By stock status ────────────────────────────────────────────
            $byStockStatus = DB::table('products')
                ->selectRaw("COALESCE(stock_status, 'unknown') as label, COUNT(*) as cnt")
                ->groupBy('label')
                ->orderByDesc('cnt')
                ->get();

            // ── By delivery text ───────────────────────────────────────────
            $byDelivery = DB::table('products')
                ->selectRaw("COALESCE(NULLIF(TRIM(estimated_delivery_text), ''), '(not set)') as label, COUNT(*) as cnt")
                ->groupByRaw("COALESCE(NULLIF(TRIM(estimated_delivery_text), ''), '(not set)')")
                ->orderByDesc('cnt')
                ->get();

            // ── Top 30 categories ──────────────────────────────────────────
            $byCategory = DB::table('product_categories')
                ->join('categories', 'categories.id', '=', 'product_categories.category_id')
                ->selectRaw('categories.name as label, COUNT(*) as cnt')
                ->groupBy('categories.name')
                ->orderByDesc('cnt')
                ->limit(30)
                ->get();

            // ── Top 20 brands ──────────────────────────────────────────────
            $byBrand = DB::table('products')
                ->whereNotNull('brand_id')
                ->join('product_brands', 'product_brands.id', '=', 'products.brand_id')
                ->selectRaw('product_brands.name as label, COUNT(*) as cnt')
                ->groupBy('product_brands.name')
                ->orderByDesc('cnt')
                ->limit(20)
                ->get();

            // ── Price ranges ───────────────────────────────────────────────
            $priceRanges = DB::table('products')->where('status', 1)->selectRaw("
                COUNT(CASE WHEN price < 10 THEN 1 END) as \"Under \$10\",
                COUNT(CASE WHEN price BETWEEN 10 AND 50 THEN 1 END) as \"\$10–\$50\",
                COUNT(CASE WHEN price BETWEEN 51 AND 100 THEN 1 END) as \"\$51–\$100\",
                COUNT(CASE WHEN price BETWEEN 101 AND 500 THEN 1 END) as \"\$101–\$500\",
                COUNT(CASE WHEN price BETWEEN 501 AND 1000 THEN 1 END) as \"\$501–\$1,000\",
                COUNT(CASE WHEN price > 1000 THEN 1 END) as \"Over \$1,000\"
            ")->first();

            $priceRangeData = collect((array) $priceRanges)->map(fn($v, $k) => ['label' => $k, 'cnt' => (int) $v]);

            // ── Products added per month (last 12 months) ──────────────────
            $byMonth = DB::table('products')
                ->selectRaw("TO_CHAR(created_at, 'YYYY-MM') as label, COUNT(*) as cnt")
                ->where('created_at', '>=', now()->subMonths(12))
                ->groupByRaw("TO_CHAR(created_at, 'YYYY-MM')")
                ->orderBy('label')
                ->get();

            // ── Top stores/vendors ─────────────────────────────────────────
            $byStore = DB::table('products')
                ->whereNotNull('store_id')
                ->join('stores', 'stores.id', '=', 'products.store_id')
                ->selectRaw('stores.store_name as label, COUNT(*) as cnt')
                ->groupBy('stores.store_name')
                ->orderByDesc('cnt')
                ->limit(15)
                ->get();

            // ── Regional breakdown ─────────────────────────────────────────
            $regional = collect([
                ['label' => 'SA Only',      'cnt' => (int) $summary->sa_only],
                ['label' => 'Zambia Only',  'cnt' => (int) $summary->zambia_only],
                ['label' => 'Zimbabwe Only','cnt' => (int) $summary->zimbabwe_only],
                ['label' => 'Global',       'cnt' => DB::table('products')
                    ->whereRaw('sa_only = false AND zambia_only = false AND zimbabwe_only = false')->count()],
            ]);

            // ── Products with vs without images ───────────────────────────
            $withThumbnail   = DB::table('products')->whereNotNull('product_thumbnail_id')->count();
            $withoutThumbnail = (int) $summary->total - $withThumbnail;

            // ── Back-order vs same-day breakdown ──────────────────────────
            $backOrderCount   = DB::table('products')->whereRaw("LOWER(estimated_delivery_text) LIKE '%back order%'")->count();
            $sameDayCount     = DB::table('products')->whereRaw("LOWER(estimated_delivery_text) LIKE '%same day%'")->count();
            $tomorrowCount    = DB::table('products')->whereRaw("LOWER(estimated_delivery_text) LIKE '%tomorrow%'")->count();
            $supplierOutCount = DB::table('products')->whereRaw("LOWER(estimated_delivery_text) LIKE '%supplier out%'")->count();

            return compact(
                'summary',
                'byStockStatus',
                'byDelivery',
                'byCategory',
                'byBrand',
                'priceRangeData',
                'byMonth',
                'byStore',
                'regional',
                'withThumbnail',
                'withoutThumbnail',
                'backOrderCount',
                'sameDayCount',
                'tomorrowCount',
                'supplierOutCount'
            );
        });

        return view('admin.products.statistics', $stats);
    }
}
