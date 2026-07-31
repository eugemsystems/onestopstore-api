<?php

namespace App\Http\Controllers;

use App\Models\Compare;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCompareRequest;
use App\Repositories\Eloquents\CompareRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Compare", description="Product compare list")
 */
class CompareController extends Controller
{
    public $repository;

    public function __construct(CompareRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/compare",
     *   tags={"Compare"},
     *   summary="List compare items",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        return $this->repository->index($request);
    }

    public function create()
    {
        // not used in API
    }

    /**
     * @OA\Post(
     *   path="/api/compare",
     *   tags={"Compare"},
     *   summary="Add to compare",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=201, description="Added")
     * )
     */
    public function store(CreateCompareRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *   path="/api/compare/{compare}",
     *   tags={"Compare"},
     *   summary="Get compare item",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="compare", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(Compare $compare)
    {
        // no detailed view provided
    }

    public function edit(Compare $compare)
    {
        // not used in API
    }

    /**
     * @OA\Put(
     *   path="/api/compare/{compare}",
     *   tags={"Compare"},
     *   summary="Update compare item",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="compare", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function update(Request $request, Compare $compare)
    {
        // implement update if needed
    }

    /**
     * @OA\Delete(
     *   path="/api/compare/{id}",
     *   tags={"Compare"},
     *   summary="Remove compare item",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Removed")
     * )
     */
    public function destroy(Request $request, $id)
    {
        return $this->repository->destroy($id ?? $request->id);
    }
}
