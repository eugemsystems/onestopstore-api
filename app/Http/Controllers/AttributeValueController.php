<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttributeValue;
use App\Http\Requests\CreateAttributeValueRequest;
use App\Http\Requests\UpdateAttributeValueRequest;
use App\Repositories\Eloquents\AttributeValueRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Attribute Values", description="Values for attributes")
 */
class AttributeValueController extends Controller
{

    public $repository;

    public function __construct(AttributeValueRepository $repository)
    {
        $this->authorizeResource(AttributeValue::class,'attributeValue', [
            'except' => [ 'index', 'show' ],
        ]);

        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/attribute-value",
     *   tags={"Attribute Values"},
     *   summary="List attribute values",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        $attribute_values = $this->repository->latest('created_at');
        return $attribute_values->paginate($request->paginate ?? $attribute_values->count());
    }

    public function create()
    {
        // not used in API
    }

    /**
     * @OA\Post(
     *   path="/api/attribute-value",
     *   tags={"Attribute Values"},
     *   summary="Create attribute value",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store(CreateAttributeValueRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *   path="/api/attribute-value/{attribute_value}",
     *   tags={"Attribute Values"},
     *   summary="Get attribute value",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="attribute_value", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(AttributeValue $attributeValue)
    {
        return $this->repository->show($attributeValue->id);
    }

    public function edit($id)
    {
        // not used in API
    }

    /**
     * @OA\Put(
     *   path="/api/attribute-value/{attribute_value}",
     *   tags={"Attribute Values"},
     *   summary="Update attribute value",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="attribute_value", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(UpdateAttributeValueRequest $request, AttributeValue $attributeValue)
    {
        return $this->repository->update($request->all(), $attributeValue->getId($request));
    }

    /**
     * @OA\Delete(
     *   path="/api/attribute-value/{attribute_value}",
     *   tags={"Attribute Values"},
     *   summary="Delete attribute value",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="attribute_value", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(Request $request, AttributeValue $attributeValue)
    {
        return $this->repository->destroy($attributeValue->getId($request));
    }
}
