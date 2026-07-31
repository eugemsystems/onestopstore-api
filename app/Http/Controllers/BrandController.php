<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Brands", description="Brand management and filtering")
 */
class BrandController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/brand",
     *   tags={"Brands"},
     *   summary="Get all brands",
     *   description="Get list of all brands with pagination and caching (cached for 10 minutes on backend)",
     *   @OA\Parameter(
     *     name="status",
     *     in="query",
     *     description="Filter by status (1=active, 0=inactive)",
     *     required=false,
     *     @OA\Schema(type="integer", enum={0, 1}, default=1, example=1)
     *   ),
     *   @OA\Parameter(
     *     name="paginate",
     *     in="query",
     *     description="Number of brands per page (default: 50, use 'all' to get all brands)",
     *     required=false,
     *     @OA\Schema(type="string", example="50")
     *   ),
     *   @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="Page number (default: 1)",
     *     required=false,
     *     @OA\Schema(type="integer", example=1)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Successful response with brands list",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=1),
     *           @OA\Property(property="name", type="string", example="Samsung"),
     *           @OA\Property(property="slug", type="string", example="samsung"),
     *           @OA\Property(property="status", type="integer", example=1),
     *           @OA\Property(
     *             property="brand_image",
     *             type="object",
     *             nullable=true,
     *             @OA\Property(property="id", type="integer", example=123),
     *             @OA\Property(property="image_url", type="string", example="https://media.raines.africa/brands/samsung.png")
     *           )
     *         )
     *       ),
     *       @OA\Property(property="message", type="string", example="Brands fetched successfully"),
     *       @OA\Property(property="current_page", type="integer", example=1),
     *       @OA\Property(property="last_page", type="integer", example=5),
     *       @OA\Property(property="per_page", type="integer", example=50),
     *       @OA\Property(property="total", type="integer", example=245),
     *       @OA\Property(property="cached", type="boolean", example=true)
     *     )
     *   ),
     *   @OA\Response(response=500, description="Server Error")
     * )
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 1);
        $paginate = $request->get('paginate', 50); // Default 50 brands per page
        $page = $request->get('page', 1);

        // If paginate is 'all', return all brands without pagination (backward compatibility)
        if ($paginate === 'all') {
            $cacheKey = "brands_list_status_{$status}_all";

            // Cache all brands for 10 minutes (600 seconds)
            $brands = Cache::remember($cacheKey, 600, function () use ($status) {
                return Brand::with('brand_image')
                    ->where('status', $status)
                    ->orderBy('name', 'asc')
                    ->get();
            });

            return response()->json([
                'success' => true,
                'data' => $brands,
                'message' => 'Brands fetched successfully',
                'cached' => Cache::has($cacheKey),
                'total' => $brands->count(),
            ]);
        }

        // Paginated response
        $perPage = min((int)$paginate, 100); // Max 100 per page
        $cacheKey = "brands_list_status_{$status}_page_{$page}_per_{$perPage}";

        // Cache paginated brands for 10 minutes
        $brandsData = Cache::remember($cacheKey, 600, function () use ($status, $perPage, $page) {
            $paginator = Brand::with('brand_image')
                ->where('status', $status)
                ->orderBy('name', 'asc')
                ->paginate($perPage, ['*'], 'page', $page);

            return [
                'data' => $paginator->items(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $brandsData['data'],
            'message' => 'Brands fetched successfully',
            'current_page' => $brandsData['current_page'],
            'last_page' => $brandsData['last_page'],
            'per_page' => $brandsData['per_page'],
            'total' => $brandsData['total'],
            'from' => $brandsData['from'],
            'to' => $brandsData['to'],
            'cached' => Cache::has($cacheKey),
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/brand/{id}",
     *   tags={"Brands"},
     *   summary="Get single brand",
     *   description="Get detailed information about a specific brand (cached for 10 minutes)",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Brand ID",
     *     required=true,
     *     @OA\Schema(type="integer", example=1)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Brand details",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="name", type="string", example="Samsung"),
     *         @OA\Property(property="slug", type="string", example="samsung"),
     *         @OA\Property(property="description", type="string", example="Leading electronics brand"),
     *         @OA\Property(property="status", type="integer", example=1)
     *       ),
     *       @OA\Property(property="message", type="string", example="Brand fetched successfully"),
     *       @OA\Property(property="cached", type="boolean", example=true)
     *     )
     *   ),
     *   @OA\Response(response=404, description="Brand not found"),
     *   @OA\Response(response=500, description="Server Error")
     * )
     */
    public function show($id)
    {
        $cacheKey = "brand_{$id}";

        // Cache single brand for 10 minutes
        $brand = Cache::remember($cacheKey, 600, function () use ($id) {
            return Brand::with('brand_image')->findOrFail($id);
        });

        return response()->json([
            'success' => true,
            'data' => $brand,
            'message' => 'Brand fetched successfully',
            'cached' => Cache::has($cacheKey),
        ]);
    }

    /**
     * Clear brand cache (useful when brands are updated)
     */
    public function clearCache()
    {
        Cache::forget('brands_list_status_1');
        Cache::forget('brands_list_status_0');


        return response()->json([
            'success' => true,
            'message' => 'Brand cache cleared successfully',
        ]);
    }
}

