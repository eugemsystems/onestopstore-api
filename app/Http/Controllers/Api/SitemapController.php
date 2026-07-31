<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Get product slugs for sitemap generation (lightweight - only slug and updated_at)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function productSlugs(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 50000);

        // Limit per_page to prevent abuse
        $perPage = min($perPage, 50000);

        // Cache key based on page and per_page
        $cacheKey = "sitemap_slugs_page_{$page}_per_{$perPage}";

        // Cache for 1 hour (sitemap doesn't need to be real-time)
        $data = Cache::remember($cacheKey, 3600, function () use ($page, $perPage) {
            // Only fetch slug and updated_at (super lightweight)
            $products = DB::table('products')
                ->select('slug', 'updated_at')
                ->where('status', 1) // Only active products
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();

            // Get total count (cached separately)
            $total = Cache::remember('sitemap_total_products', 3600, function () {
                return DB::table('products')
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->count();
            });

            return [
                'data' => $products,
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
            ];
        });

        return response()->json($data);
    }

    /**
     * Get total count of active products for sitemap index
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function productCount()
    {
        $count = Cache::remember('sitemap_total_products', 3600, function () {
            return DB::table('products')
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->count();
        });

        return response()->json(['count' => $count]);
    }
}
