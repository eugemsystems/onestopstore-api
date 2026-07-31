<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\CreateCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Repositories\Eloquents\CategoryRepository;
use Illuminate\Support\Facades\Cache;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *   name="Categories",
 *   description="Operations related to categories"
 * )
 */
class CategoryController extends Controller
{

    public $repository;

    public function __construct(CategoryRepository $repository)
    {
        $this->authorizeResource(Category::class, 'category', [
            'except' => [ 'index', 'show' ],
        ]);

        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     *
     * List categories
     * @OA\Get(
     *   path="/api/category",
     *   tags={"Categories"},
     *   summary="List categories",
     *   @OA\Parameter(name="paginate", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="type", in="query", required=false, @OA\Schema(type="string")),
     *   @OA\Parameter(name="ids", in="query", required=false, description="Comma-separated IDs", @OA\Schema(type="string")),
     *   @OA\Parameter(name="field", in="query", required=false, @OA\Schema(type="string")),
     *   @OA\Parameter(name="sort", in="query", required=false, @OA\Schema(type="string", enum={"asc","desc"})),
     *   @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="integer", enum={0,1})),
     *   @OA\Parameter(name="store_slug", in="query", required=false, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Successful")
     * )
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $version = $this->getCategoriesCacheVersion();
        $keyBase = 'categories_page_' . ($request->paginate ?? 25) . '_filters_' . md5(json_encode($request->all()));
        $cacheKey = $keyBase . ':v' . $version;

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($request) {
            $categories = $this->repository
                ->whereNull('parent_id')
                ->select(['id', 'name', 'slug', 'category_image_id', 'category_icon_id', 'status', 'sort_order', 'parent_id', 'type', 'commission_rate'])
                ->with(['subcategories:id,name,slug,parent_id,sort_order']);
            $categories = $this->filter($categories, $request);

            // Default ordering by sort_order; allow override via orderby param (asc/desc)
            $sortDirection = in_array($request->orderby, ['asc', 'desc']) ? $request->orderby : 'asc';
            return $categories->orderBy('sort_order', $sortDirection)->paginate($request->paginate ?? 25)->toArray();
        });
    }

    public function index_optimized(Request $request)
    {
        $version = $this->getCategoriesCacheVersion();
        $keyBase = 'categories_page_' . ($request->paginate ?? 25) . '_filters_' . md5(json_encode($request->all()));
        $cacheKey = $keyBase . ':v' . $version;

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($request) {
            $categories = $this->repository
                ->whereNull('parent_id')
                ->select(['id', 'name', 'slug','category_image_id', 'sort_order'])
                ->with(['subcategories:id,name,slug,parent_id,sort_order']);

            $categories = $this->filter($categories, $request);

            $sortDirection = in_array($request->orderby, ['asc', 'desc']) ? $request->orderby : 'asc';
            return $categories->orderBy('sort_order', $sortDirection)->paginate($request->paginate ?? 25);
        });

    }

    /**
     * @OA\Get(
     *   path="/api/categoryFront",
     *   tags={"Categories"},
     *   summary="Frontend categories tree",
     *   @OA\Response(response=200, description="Successful")
     * )
     */
    public function indexFront(){
        return getCachedCategories();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * Create a category
     * @OA\Post(
     *   path="/api/category",
     *   tags={"Categories"},
     *   summary="Create a category",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         type="object",
     *         required={"name"},
     *         @OA\Property(property="name", type="string", example="Paints"),
     *         @OA\Property(property="slug", type="string", example="paints"),
     *         @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
     *         @OA\Property(property="status", type="integer", enum={0,1}, example=1),
     *         @OA\Property(property="type", type="string", nullable=true, example="default"),
     *         @OA\Property(property="category_image_id", type="integer", nullable=true, example=123)
     *       )
     *     )
     *   ),
     *   @OA\Response(response=201, description="Created"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     *
     * @param  \App\Http\Requests\CreateCategoryRequest  $request
     * @return \Illuminate\Http\Response
     */

    public function store(CreateCategoryRequest $request)
    {
        $res = $this->repository->store($request);
        $this->bumpCategoriesCacheVersion();
        return $res;
    }

    /**
     * Display the specified resource.
     *
     * Get a category by ID
     * @OA\Get(
     *   path="/api/category/{category}",
     *   tags={"Categories"},
     *   summary="Get a single category",
     *   @OA\Parameter(name="category", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Successful"),
     *   @OA\Response(response=404, description="Not found")
     * )
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        return $this->repository->show($category->id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * Update a category
     * @OA\Put(
     *   path="/api/category/{category}",
     *   tags={"Categories"},
     *   summary="Update a category",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="category",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         type="object",
     *         @OA\Property(property="name", type="string", example="Paints & Primers"),
     *         @OA\Property(property="slug", type="string", example="paints-primers"),
     *         @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
     *         @OA\Property(property="status", type="integer", enum={0,1}, example=1),
     *         @OA\Property(property="type", type="string", nullable=true, example="default"),
     *         @OA\Property(property="category_image_id", type="integer", nullable=true, example=456)
     *       )
     *     )
     *   ),
     *   @OA\Response(response=200, description="Updated"),
     *   @OA\Response(response=404, description="Not found"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     *
     * @param  \App\Http\Requests\UpdateCategoryRequest  $request
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $res = $this->repository->update($request->all(), $category->getId($request));
        $this->bumpCategoriesCacheVersion();
        return $res;
    }

    /**
     * Update Status the specified resource from storage.
     *
     * Update category status
     * @OA\Put(
     *   path="/api/category/{id}/{status}",
     *   tags={"Categories"},
     *   summary="Update category status",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="status", in="path", required=true, @OA\Schema(type="integer", enum={0,1})),
     *   @OA\Response(response=200, description="Status updated")
     * )
     *
     * @param  int  $id
     * @param int $status
     * @return \Illuminate\Http\Response
     */
    public function status($id, $status)
    {
        $res = $this->repository->status($id, $status);
        $this->bumpCategoriesCacheVersion();
        return $res;
    }

    /**
     * Remove the specified resource from storage.
     *
     * Delete a category
     * @OA\Delete(
     *   path="/api/category/{category}",
     *   tags={"Categories"},
     *   summary="Delete a category",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="category", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Category $category)
    {
       $res = $this->repository->destroy($category->getId($request));
       $this->bumpCategoriesCacheVersion();
       return $res;
    }

    /**
     * Get a URL for categories export.
     */
    public function getCategoriesExportUrl(Request $request)
    {
        return $this->repository->getCategoriesExportUrl($request);
    }

    /**
     * Import categories by CSV
     * @OA\Post(
     *   path="/api/category/csv/import",
     *   tags={"Categories"},
     *   summary="Import categories by CSV",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Import started")
     * )
     */
    public function import()
    {
        $res = $this->repository->import();
        $this->bumpCategoriesCacheVersion();
        return $res;
    }

    /**
     * Export categories as CSV
     * @OA\Post(
     *   path="/api/category/csv/export",
     *   tags={"Categories"},
     *   summary="Export categories as CSV",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Export URL")
     * )
     */
    public function export()
    {
        return $this->repository->export();
    }

    public function filter($categories, $request)
    {
        if ($request->type) {
            $categories = $this->repository->whereNull('parent_id')
                ->where('type', 'like', $request->type)
                ->with(['subcategories']);
        }

        if ($request->ids) {
            $ids = explode(',',$request->ids);
            $categories = $categories->findWhereIn('id',$ids);
        }

        if ($request->field && $request->sort) {
            $categories = $categories->orderBy($request->field, $request->sort);
        }

        if (isset($request->status)) {
            $categories = $categories->where('status', $request->status);
        }

        if ($request->store_slug) {
            $store_slug = $request->store_slug;
            $categories = $categories->whereHas('products', function (Builder $products) use ($store_slug) {
                $products->whereHas('store', function (Builder $stores) use ($store_slug) {
                    $stores->where('slug', $store_slug);
                });
            });
        }

        if($request->parent){
            $categories = $this->repository->whereNull('parent_id');
        }

        return $categories;
    }

    private function getCategoriesCacheVersion(): int
    {
        return (int) Cache::get('categories_cache_version', 1);
    }

    private function bumpCategoriesCacheVersion(): void
    {
        $ver = (int) Cache::get('categories_cache_version', 1);
        Cache::put('categories_cache_version', $ver + 1, now()->addDays(365));
    }
}
