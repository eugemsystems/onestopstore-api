<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductSkuExportController extends Controller
{
    /**
     * Get all product SKUs grouped by category
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSkusByCategory(Request $request)
    {
        $download = $request->boolean('download', false);

        // Get all categories with their products' SKUs
        $categories = Category::with(['products' => function ($query) {
            $query->select('products.id', 'products.name', 'products.sku', 'products.status')
                  ->where('products.status', 1) // Only active products
                  ->whereNotNull('products.sku')
                  ->where('products.sku', '!=', '');
        }])->get();

        $result = [];

        foreach ($categories as $category) {
            if ($category->products->isNotEmpty()) {
                $categoryData = [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'category_slug' => $category->slug,
                    'product_count' => $category->products->count(),
                    'skus' => $category->products->pluck('sku')->toArray(),
                    'products' => $category->products->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'sku' => $product->sku,
                        ];
                    })->toArray()
                ];

                $result[] = $categoryData;
            }
        }

        // Sort by category name
        usort($result, function ($a, $b) {
            return strcmp($a['category_name'], $b['category_name']);
        });

        $responseData = [
            'success' => true,
            'total_categories' => count($result),
            'total_products' => array_sum(array_column($result, 'product_count')),
            'generated_at' => now()->toDateTimeString(),
            'data' => $result
        ];

        // If download is requested, return as downloadable JSON file
        if ($download) {
            $filename = 'product-skus-by-category-' . now()->format('Y-m-d-His') . '.json';

            return response()->json($responseData, 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ], JSON_PRETTY_PRINT);
        }

        return response()->json($responseData);
    }

    /**
     * Get product SKUs for a specific category
     *
     * @param Request $request
     * @param int $categoryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSkusByCategoryId(Request $request, $categoryId)
    {
        $download = $request->boolean('download', false);

        $category = Category::with(['products' => function ($query) {
            $query->select('products.id', 'products.name', 'products.sku', 'products.status')
                  ->where('products.status', 1)
                  ->whereNotNull('products.sku')
                  ->where('products.sku', '!=', '');
        }])->find($categoryId);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        $responseData = [
            'success' => true,
            'category_id' => $category->id,
            'category_name' => $category->name,
            'category_slug' => $category->slug,
            'product_count' => $category->products->count(),
            'generated_at' => now()->toDateTimeString(),
            'skus' => $category->products->pluck('sku')->toArray(),
            'products' => $category->products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                ];
            })->toArray()
        ];

        if ($download) {
            $filename = 'product-skus-' . $category->slug . '-' . now()->format('Y-m-d-His') . '.json';

            return response()->json($responseData, 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ], JSON_PRETTY_PRINT);
        }

        return response()->json($responseData);
    }

    /**
     * Get SKU summary - just counts per category
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSkuSummary()
    {
        $summary = Category::select('categories.id', 'categories.name', 'categories.slug')
            ->withCount(['products as products_with_sku' => function ($query) {
                $query->where('products.status', 1)
                      ->whereNotNull('products.sku')
                      ->where('products.sku', '!=', '');
            }])
            ->having('products_with_sku', '>', 0)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'total_categories' => $summary->count(),
            'total_products_with_sku' => $summary->sum('products_with_sku'),
            'generated_at' => now()->toDateTimeString(),
            'categories' => $summary->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'products_with_sku_count' => $category->products_with_sku,
                ];
            })
        ]);
    }

    /**
     * Export all SKUs as a simple flat list
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function exportAllSkus(Request $request)
    {
        $download = $request->boolean('download', false);

        $products = Product::select('id', 'name', 'sku', 'category_id')
            ->where('status', 1)
            ->whereNotNull('sku')
            ->where('sku', '!=', '')
            ->with('category:id,name')
            ->orderBy('sku')
            ->get();

        $responseData = [
            'success' => true,
            'total_products' => $products->count(),
            'generated_at' => now()->toDateTimeString(),
            'skus' => $products->pluck('sku')->toArray(),
            'products' => $products->map(function ($product) {
                return [
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'category' => $product->category ? $product->category->name : null,
                ];
            })->toArray()
        ];

        if ($download) {
            $filename = 'all-product-skus-' . now()->format('Y-m-d-His') . '.json';

            return response()->json($responseData, 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ], JSON_PRETTY_PRINT);
        }

        return response()->json($responseData);
    }

    /**
     * Get product SKUs filtered by category (via query parameter)
     * Optimized to return only SKUs for better performance
     *
     * Usage:
     * - /api/products/skus/filter?category=electronics (by slug)
     * - /api/products/skus/filter?category_id=5 (by ID)
     * - /api/products/skus/filter?category=electronics&format=txt (as text file)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function getSkusFiltered(Request $request)
    {
        $categorySlug = $request->get('category');
        $categoryId = $request->get('category_id');
        $format = $request->get('format', 'json'); // json, txt, csv
        $download = $request->boolean('download', false);

        // Build the base query
        $query = Product::select('products.id', 'products.sku', 'products.name')
            ->where('products.status', 1)
            ->whereNotNull('products.sku')
            ->where('products.sku', '!=', '');

        // Apply category filter if provided
        if ($categorySlug || $categoryId) {
            $query->join('product_categories', 'products.id', '=', 'product_categories.product_id')
                  ->join('categories', 'product_categories.category_id', '=', 'categories.id');

            if ($categorySlug) {
                $query->where('categories.slug', $categorySlug);
            } elseif ($categoryId) {
                $query->where('categories.id', $categoryId);
            }
        }

        // For text format, use streaming for better performance
        if ($format === 'txt') {
            return response()->stream(function () use ($query) {
                // Clean any existing output buffers
                while (ob_get_level()) {
                    ob_end_clean();
                }

                $count = 0;
                $batchBuffer = [];
                $batchSize = 10000;

                foreach ($query->distinct()->cursor() as $product) {
                    $batchBuffer[] = $product->sku;
                    $count++;

                    if ($count % $batchSize === 0) {
                        echo implode("\n", $batchBuffer) . "\n";
                        $batchBuffer = [];

                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();

                        if ($count % 50000 === 0) {
                            gc_collect_cycles();
                        }
                    }
                }

                if (!empty($batchBuffer)) {
                    echo implode("\n", $batchBuffer) . "\n";
                }

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }, 200, [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="product-skus-' . ($categorySlug ?? 'filtered') . '-' . now()->format('Y-m-d-His') . '.txt"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Accel-Buffering' => 'no',
            ]);
        }

        // For JSON/CSV format, get all data at once
        $products = $query->distinct()->get();

        if ($format === 'csv') {
            return response()->stream(function () use ($products) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['SKU', 'Product Name']);

                foreach ($products as $product) {
                    fputcsv($handle, [$product->sku, $product->name]);
                }

                fclose($handle);
            }, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="product-skus-' . ($categorySlug ?? 'filtered') . '-' . now()->format('Y-m-d-His') . '.csv"',
            ]);
        }

        // JSON format (default)
        $category = null;
        if ($categorySlug || $categoryId) {
            $category = Category::when($categorySlug, function ($q) use ($categorySlug) {
                return $q->where('slug', $categorySlug);
            })->when($categoryId, function ($q) use ($categoryId) {
                return $q->where('id', $categoryId);
            })->first();
        }

        $responseData = [
            'success' => true,
            'filter' => [
                'category_slug' => $categorySlug,
                'category_id' => $categoryId,
                'category_name' => $category ? $category->name : null,
            ],
            'total_products' => $products->count(),
            'generated_at' => now()->toDateTimeString(),
            'skus' => $products->pluck('sku')->unique()->values()->toArray(),
        ];

        if ($download) {
            $filename = 'product-skus-' . ($categorySlug ?? $categoryId ?? 'all') . '-' . now()->format('Y-m-d-His') . '.json';

            return response()->json($responseData, 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ], JSON_PRETTY_PRINT);
        }

        return response()->json($responseData);
    }

    /**
     * Get just SKUs list - HEAVILY optimized for millions of products (1.5M+)
     * Uses cursor-based iteration and streaming to prevent memory issues
     * MUCH FASTER than chunk() for large datasets
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getSkusList(Request $request)
    {
        $format = $request->get('format', 'json'); // json, txt, csv

        // Disable execution time limit for large datasets
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M'); // Increase memory limit

        if ($format === 'txt') {
            return response()->stream(function () {
                // Clean any existing output buffers to prevent HTML wrapper
                while (ob_get_level()) {
                    ob_end_clean();
                }

                // Start fresh output
                if (function_exists('apache_setenv')) {
                    apache_setenv('no-gzip', '1');
                }
                ini_set('zlib.output_compression', 0);
                ini_set('implicit_flush', 1);

                $count = 0;
                $batchBuffer = [];
                $batchSize = 10000; // Output 10k SKUs at once for better performance

                // Use cursor() instead of chunk() - MUCH faster for large datasets!
                // Cursor uses a database cursor, no sorting needed = faster
                foreach (Product::select('sku')
                    ->where('status', 1)
                    ->whereNotNull('sku')
                    ->where('sku', '!=', '')
                    ->cursor() as $product) {

                    $batchBuffer[] = $product->sku;
                    $count++;

                    // Output in batches for better performance
                    if ($count % $batchSize === 0) {
                        echo implode("\n", $batchBuffer) . "\n";
                        $batchBuffer = [];

                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();

                        // Free memory periodically
                        if ($count % 50000 === 0) {
                            gc_collect_cycles();
                        }
                    }
                }

                // Output remaining SKUs
                if (!empty($batchBuffer)) {
                    echo implode("\n", $batchBuffer) . "\n";
                }

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }, 200, [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="product-skus-' . now()->format('Y-m-d-His') . '.txt"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Accel-Buffering' => 'no', // Disable nginx buffering
            ]);
        }

        if ($format === 'csv') {
            return response()->stream(function () {
                // Clean any existing output buffers
                while (ob_get_level()) {
                    ob_end_clean();
                }

                // Disable compression
                if (function_exists('apache_setenv')) {
                    apache_setenv('no-gzip', '1');
                }
                ini_set('zlib.output_compression', 0);
                ini_set('implicit_flush', 1);

                // Output as CSV
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['SKU']); // Simple header - just SKU for speed

                $count = 0;
                $batchSize = 10000;

                // Use cursor for maximum speed
                foreach (Product::select('sku')
                    ->where('status', 1)
                    ->whereNotNull('sku')
                    ->where('sku', '!=', '')
                    ->cursor() as $product) {

                    fputcsv($handle, [$product->sku]);
                    $count++;

                    if ($count % $batchSize === 0) {
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();

                        // Free memory periodically
                        if ($count % 50000 === 0) {
                            gc_collect_cycles();
                        }
                    }
                }

                fclose($handle);

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="product-skus-' . now()->format('Y-m-d-His') . '.csv"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Accel-Buffering' => 'no',
            ]);
        }

        // Default: JSON format with streaming
        return response()->stream(function () {
            // Clean any existing output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Disable compression
            if (function_exists('apache_setenv')) {
                apache_setenv('no-gzip', '1');
            }
            ini_set('zlib.output_compression', 0);
            ini_set('implicit_flush', 1);

            echo '{"success":true,"generated_at":"' . now()->toDateTimeString() . '","skus":[';

            $first = true;
            $count = 0;
            $batchSize = 10000;

            // Use cursor for maximum speed
            foreach (Product::select('sku')
                ->where('status', 1)
                ->whereNotNull('sku')
                ->where('sku', '!=', '')
                ->cursor() as $product) {

                if (!$first) {
                    echo ',';
                }
                echo json_encode($product->sku);
                $first = false;
                $count++;

                // Flush every batch to prevent buffering issues
                if ($count % $batchSize === 0) {
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();

                    // Free memory periodically
                    if ($count % 50000 === 0) {
                        gc_collect_cycles();
                    }
                }
            }

            echo '],"total":' . $count . '}';

            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}

