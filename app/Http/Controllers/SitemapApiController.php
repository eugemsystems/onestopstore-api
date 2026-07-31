<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Sitemap", description="Sitemap helpers")
 */
class SitemapApiController extends Controller
{
    /* ---------- utils: column helpers ---------- */

    /** pick the first existing column from the list */
    private function pick(string $table, array $candidates, $default = null): ?string
    {
        foreach ($candidates as $c) {
            if (Schema::hasColumn($table, $c)) return $c;
        }
        return $default;
    }

    /** apply common visibility filters (status/is_approved, soft deletes) */
    private function applyVisibility(string $table, $query)
    {
        if (Schema::hasColumn($table, 'status')) {
            $query->where("{$table}.status", 1);
        } elseif (Schema::hasColumn($table, 'is_approved')) {
            $query->where("{$table}.is_approved", 1);
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull("{$table}.deleted_at");
        }

        return $query;
    }

    /* ---------- endpoints ---------- */

    /**
     * @OA\Get(
     *   path="/api/sitemap/products/count",
     *   tags={"Sitemap"},
     *   summary="Sitemap product count",
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function productCount(Request $request): JsonResponse
    {
        $filter = $request->input('filter'); // 'featured', 'sale', or null for all

        $q = DB::table('products');
        $this->applyVisibility('products', $q);

        if ($filter === 'featured') {
            $q->where('is_featured', 1);
        } elseif ($filter === 'sale') {
            $q->where('is_sale_enable', 1);
        }

        $count = $q->count();

        return response()->json(['count' => (int) $count]);
    }

    /**
     * @OA\Get(
     *   path="/api/sitemap/products",
     *   tags={"Sitemap"},
     *   summary="Sitemap product slice",
     *   @OA\Parameter(name="offset", in="query", required=false, @OA\Schema(type="integer", default=0)),
     *   @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer", default=50000)),
     *   @OA\Response(response=200, description="OK")
     * )
     * Returns rows like: [{ slug: "...", updated_at: "..." }]
     */
    public function productSlice(Request $request): JsonResponse
    {
        $offset = max(0, (int) $request->integer('offset', 0));
        $limit  = max(1, min(50000, (int) $request->integer('limit', 50000)));
        $filter = $request->input('filter'); // 'featured', 'sale', or null for all

        // prefer slug → else fall back to id (frontend will build URL accordingly)
        $slugCol     = $this->pick('products', ['slug', 'permalink', 'uuid', 'id'], 'id');
        $updatedCol  = $this->pick('products', ['updated_at', 'modified_at', 'created_at'], 'created_at');

        $q = DB::table('products');
        $this->applyVisibility('products', $q);

        // Optional priority filters for high-value sitemaps
        if ($filter === 'featured') {
            $q->where('is_featured', 1);
        } elseif ($filter === 'sale') {
            $q->where('is_sale_enable', 1);
        }

        // stable ordering for paging — priority products first
        $orderCol = Schema::hasColumn('products', 'id') ? 'id' : $slugCol;
        $q->orderBy($orderCol)->offset($offset)->limit($limit);

        // select with aliases expected by Next.js sitemap code
        $rows = $q->get([
            "{$slugCol} as slug",
            "{$updatedCol} as updated_at",
        ]);

        // Format dates to ISO 8601 with Harare timezone (e.g., 2026-02-09T10:19:04+02:00)
        $rows = $rows->map(function ($row) {
            if (isset($row->updated_at)) {
                $row->updated_at = \Carbon\Carbon::parse($row->updated_at)
                    ->setTimezone('Africa/Harare')
                    ->toIso8601String();
            }
            return $row;
        });

        return response()->json($rows);
    }

    /**
     * @OA\Get(
     *   path="/api/sitemap/blogs",
     *   tags={"Sitemap"},
     *   summary="Sitemap blog posts",
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function blogs(): JsonResponse
    {
        $table = 'blogs';
        $slugCol    = $this->pick($table, ['slug', 'permalink', 'uuid', 'id'], 'id');
        $updatedCol = $this->pick($table, ['updated_at', 'modified_at', 'created_at'], 'created_at');

        $q = DB::table($table);
        $this->applyVisibility($table, $q);

        $orderCol = Schema::hasColumn($table, 'id') ? 'id' : $slugCol;
        $rows = $q->orderBy($orderCol)->get([
            "{$slugCol} as slug",
            "{$updatedCol} as updated_at",
        ]);

        $rows = $rows->map(function ($row) {
            if (isset($row->updated_at)) {
                $row->updated_at = \Carbon\Carbon::parse($row->updated_at)
                    ->setTimezone('Africa/Harare')
                    ->toIso8601String();
            }
            return $row;
        });

        return response()->json($rows);
    }

    /**
     * @OA\Get(
     *   path="/api/sitemap/categories",
     *   tags={"Sitemap"},
     *   summary="Sitemap categories",
     *   @OA\Response(response=200, description="OK")
     * )
     * Returns rows like: [{ path: "/category/slug", updated_at: "..." }]
     */
    public function categories(): JsonResponse
    {
        // If your categories table is different, adapt here
        $table = 'categories';

        // choose slug-like field or fall back to id
        $slugCol    = $this->pick($table, ['slug', 'permalink', 'uuid', 'id'], 'id');
        $updatedCol = $this->pick($table, ['updated_at', 'modified_at', 'created_at'], 'created_at');

        $q = DB::table($table);
        $this->applyVisibility($table, $q);

        $orderCol = Schema::hasColumn($table, 'id') ? 'id' : $slugCol;
        $rows = $q->orderBy($orderCol)->get([
            "{$slugCol} as path",
            "{$updatedCol} as updated_at",
        ]);

        // Format dates to ISO 8601 with Harare timezone (e.g., 2026-02-09T10:19:04+02:00)
        $rows = $rows->map(function ($row) {
            if (isset($row->updated_at)) {
                $row->updated_at = \Carbon\Carbon::parse($row->updated_at)
                    ->setTimezone('Africa/Harare')
                    ->toIso8601String();
            }
            return $row;
        });

        // We return just the slug/id; your Next sitemap code prefixes the domain + "/category/"
        return response()->json($rows);
    }
}
