<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateWishlistRequest;
use App\Repositories\Eloquents\WishlistRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Wishlists", description="Wishlists")
 */
class WishlistController extends Controller
{
    public $repository;

    public function __construct(WishlistRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/wishlist",
     *   tags={"Wishlists"},
     *   summary="List wishlists",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        return $this->repository->index($request);
    }

    /**
     * @OA\Post(
     *   path="/api/wishlist",
     *   tags={"Wishlists"},
     *   summary="Create wishlist",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store(CreateWishlistRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Delete(
     *   path="/api/wishlist/{id}",
     *   tags={"Wishlists"},
     *   summary="Delete wishlist",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Deleted")
     * )
     */
    public function destroy(Request $request, $id)
    {
        return $this->repository->destroy($id ?? $request->id);
    }
}
