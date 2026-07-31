<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Http\Requests\CreateAttributeRequest;
use App\Http\Requests\UpdateAttributeRequest;
use App\Repositories\Eloquents\AttributeRepository;
use Illuminate\Support\Facades\Cache;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Attributes", description="Product attributes")
 */
class AttributeController extends Controller
{
    public $repository;

    public function __construct(AttributeRepository $repository)
    {
        $this->authorizeResource(Attribute::class, 'attribute', [
            'except' => [ 'index', 'show' ],
        ]);
        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/attribute",
     *   tags={"Attributes"},
     *   summary="List attributes",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        try {
            $cacheKey = 'attributes_index_' . md5(json_encode($request->all()));

            return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($request) {
                $attribute = $this->filter($this->repository->with(['attribute_values']), $request);
                return $attribute->latest('created_at')->paginate($request->paginate ?? 20);
            });

        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function create()
    {
        // not used in API
    }

    /**
     * @OA\Post(
     *   path="/api/attribute",
     *   tags={"Attributes"},
     *   summary="Create attribute",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store(CreateAttributeRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *   path="/api/attribute/{attribute}",
     *   tags={"Attributes"},
     *   summary="Get attribute",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="attribute", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(Attribute $attribute)
    {
        return $this->repository->show($attribute->id);
    }

    public function edit($id)
    {
        // not used in API
    }

    /**
     * @OA\Put(
     *   path="/api/attribute/{attribute}",
     *   tags={"Attributes"},
     *   summary="Update attribute",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="attribute", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(UpdateAttributeRequest $request, Attribute $attribute)
    {
        return $this->repository->update($request->all(), $attribute->getId($request));
    }

    /**
     * @OA\Delete(
     *   path="/api/attribute/{attribute}",
     *   tags={"Attributes"},
     *   summary="Delete attribute",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="attribute", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(Request $request, Attribute $attribute)
    {
        return $this->repository->destroy($attribute->getId($request));
    }

    /**
     * @OA\Put(
     *   path="/api/attribute/{id}/{status}",
     *   tags={"Attributes"},
     *   summary="Update attribute status",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="status", in="path", required=true, @OA\Schema(type="integer", enum={0,1})),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function status($id, $status)
    {
        return $this->repository->status($id, $status);
    }

    /**
     * @OA\Post(
     *   path="/api/attribute/deleteAll",
     *   tags={"Attributes"},
     *   summary="Bulk delete attributes",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"ids"}, @OA\Property(property="ids", type="array", @OA\Items(type="integer"))))),
     *   @OA\Response(response=200, description="Deleted")
     * )
     */
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    public function getAttributesExportUrl(Request $request)
    {
        return $this->repository->getAttributesExportUrl($request);
    }

    /**
     * @OA\Post(
     *   path="/api/attribute/csv/import",
     *   tags={"Attributes"},
     *   summary="Import attributes (CSV)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Import started")
     * )
     */
    public function import()
    {
        return $this->repository->import();
    }

    /**
     * @OA\Post(
     *   path="/api/attribute/csv/export",
     *   tags={"Attributes"},
     *   summary="Export attributes (CSV)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Export URL")
     * )
     */
    public function export()
    {
        return $this->repository->export();
    }

    public function filter($attribute, $request)
    {
        if ($request->field && $request->sort) {
           $attribute = $attribute->orderBy($request->field, $request->sort);
        }

        if (isset($request->status)) {
            $attribute = $attribute->whereStatus($request->status);
        }

        if ($request->store_slug) {
            $store_slug = $request->store_slug;
            $attribute = $attribute->whereHas('products', function (Builder $products) use ($store_slug) {
                $products->whereHas('store', function (Builder $store) use ($store_slug) {
                    $store->where('slug', $store_slug);
                });
            });
        }

        return $attribute;
    }
}
