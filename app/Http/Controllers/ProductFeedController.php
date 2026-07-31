<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Cache;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Product Feed", description="Product feed export")
 */
class ProductFeedController extends Controller
{
    /**
     * Show the product feed export form
     */
    public function showForm()
    {
        $categories = Category::whereNull('parent_id')
            ->with('subcategories.subcategories')
            ->orderBy('name')
            ->get();

        $currencies = Currency::where('status', 1)
            ->select('code', 'symbol')
            ->orderBy('code')
            ->get()
            ->toArray();

        return view('admin.product-feed.index', compact('categories', 'currencies'));
    }

    /**
     * @OA\Post(
     *   path="/api/product-feed/export",
     *   tags={"Product Feed"},
     *   summary="Export product feed (RSS/XML)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="category", in="query", required=true, description="Category slug", @OA\Schema(type="string")),
     *   @OA\Parameter(name="currency", in="query", required=true, description="Currency code (e.g. USD, ZAR)", @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="XML stream", content={@OA\MediaType(mediaType="application/rss+xml")})
     * )
     */
    public function export(Request $request)
    {
        // allow this script to run as long as it needs
        set_time_limit(0);

        $categorySlug = $request->query('category');
        $currencyCode = $request->query('currency');

        $category = Cache::remember("category_by_slug_{$categorySlug}", now()->addHours(6), function () use ($categorySlug) {
            return Category::whereSlug($categorySlug)->firstOrFail();
        });
        $currency = Cache::remember("currency_code_{$currencyCode}", now()->addHours(6), function () use ($currencyCode) {
            return Currency::where('code', $currencyCode)
                ->where('status', 1)
                ->firstOrFail();
        });

        // prepare conversion & formatting rules
        $rate     = (float) $currency->exchange_rate;
        $decimals = (int)   $currency->no_of_decimal;
        $decSep   = $currency->decimal_separator   === 'comma' ? ',' : '.';
        $thouSep  = $currency->thousands_separator === 'comma' ? ',' : '.';

        $filename = "product-feed-{$categorySlug}-{$currencyCode}.xml";

        return response()->streamDownload(function() use (
            $category,
            $categorySlug,
            $rate,
            $decimals,
            $decSep,
            $thouSep,
            $currencyCode
        ) {
            // 1) XML head & channel open
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">';
            echo '<channel>';
            echo '<title>' . htmlspecialchars(config('app.name') . ' Product Feed') . '</title>';
            echo '<link>'  . htmlspecialchars(config('app.url'))                . '</link>';
            echo '<description>'
                . htmlspecialchars(config('app.name') . ' Feed for category ' . str_replace('&','and',$category->name))
                . '</description>';

            // 2) Stream products in chunks
            Product::with(['product_thumbnail:id,image_url'])
                ->select(['id','sku','name','description','slug','price','sale_price','stock_status','product_thumbnail_id'])
                ->whereHas('categories', fn($q) => $q->where('slug', $categorySlug))
                ->where('status',1)
                ->where('is_approved',1)
                ->orderBy('id')
                ->chunkById(1000, function($products) use (
                    $rate, $decimals, $decSep, $thouSep, $currencyCode
                ) {
                    // helpers created once per chunk to reduce per-item overhead
                    $g = static function($tag, $val='') {
                        $e = htmlspecialchars($val, ENT_XML1 | ENT_COMPAT, 'UTF-8');
                        echo "<g:{$tag}>{$e}</g:{$tag}>";
                    };
                    $formatPrice = static function($amount) use ($rate, $decimals) {
                        $converted = $amount * $rate;
                        if ($decimals > 0) {
                            $formatted = number_format($converted, $decimals, '.', '');
                            $formatted = rtrim(rtrim($formatted, '0'), '.');
                        } else {
                            $formatted = number_format($converted, 0, '.', '');
                        }
                        return $formatted;
                    };
                    $baseUrl = config('app.frontend_url');
                    $currencySuffix = " {$currencyCode}";

                    foreach ($products as $product) {
                        // open item
                        echo '<item>';

                        $g('id',          $product->sku);
                        $g('title',       $product->name);
                        $desc = $product->description;
                        if ($desc && strpos($desc, '<') !== false) {
                            $desc = strip_tags($desc);
                        }
                        $g('description', $desc);
                        $g('link',        $baseUrl."/product/{$product->slug}");

                        // price
                        $g('price', $formatPrice($product->price) . $currencySuffix);

                        // sale_price
                        if ($product->sale_price) {
                            $g('sale_price', $formatPrice($product->sale_price) . $currencySuffix);
                        } else {
                            $g('sale_price', '');
                        }

                        $g('condition',    'new');
                        $g('availability', $product->stock_status === 'in_stock' ? 'in stock' : 'out of stock');
                        $g('brand',        '');
                        $g('item_group_id',$product->sku);

                        // image
                        $img = $product->product_thumbnail ? $product->product_thumbnail->image_url : '';
                        $g('image_link', $img);

                        // close item
                        echo '</item>';
                    }
                });

            // 3) close channel & rss
            echo '</channel>';
            echo '</rss>';
        }, $filename, [
            'Content-Type'        => 'application/rss+xml; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\""
        ]);
    }
}
