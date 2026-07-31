<?php

namespace App\Http\Controllers;

use App\GraphQL\Exceptions\ExceptionHandler;
use App\Helpers\Helpers;
use App\Models\Cart;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\SyncCartRequest;
use App\Http\Requests\CreateUpdateCartRequest;
use App\Repositories\Eloquents\CartRepository;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Carts", description="Shopping cart")
 */
class CartController extends Controller
{
    public $repository;

    public function __construct(CartRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/cart",
     *   tags={"Carts"},
     *   summary="Get cart",
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
     *   path="/api/cart",
     *   tags={"Carts"},
     *   summary="Add to cart",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         type="object",
     *         required={"product_id","quantity"},
     *         @OA\Property(property="product_id", type="integer", example=1425296),
     *         @OA\Property(property="variation_id", type="integer", nullable=true, example=null),
     *         @OA\Property(property="quantity", type="integer", minimum=1, example=1)
     *       ),
     *       example={"product_id":1425296, "variation_id":null, "quantity":1}
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function store(CreateUpdateCartRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Put(
     *   path="/api/cart",
     *   tags={"Carts"},
     *   summary="Update cart (bulk)",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         type="object",
     *         required={"product_id","quantity"},
     *         @OA\Property(property="product_id", type="integer", example=1425296),
     *         @OA\Property(property="variation_id", type="integer", nullable=true, example=null),
     *         @OA\Property(property="quantity", type="integer", minimum=1, example=2)
     *       ),
     *       example={"product_id":1425296, "variation_id":null, "quantity":2}
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function update(CreateUpdateCartRequest $request)
    {
        return $this->repository->update($request->all());
    }

    /**
     * @OA\Delete(
     *   path="/api/cart/{cart}",
     *   tags={"Carts"},
     *   summary="Remove cart item",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="cart", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Removed")
     * )
     */
    public function destroy(Request $request, Cart $cart)
    {
        return $this->repository->destroy($cart->getId($request));
    }

    /**
     * @OA\Post(
     *   path="/api/clear/cart",
     *   tags={"Carts"},
     *   summary="Clear cart",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Cleared")
     * )
     */
    public function clearCart(Request $request)
    {
        return $this->repository->clearCart($request);
    }

    /**
     * @OA\Put(
     *   path="/api/replace/cart",
     *   tags={"Carts"},
     *   summary="Replace cart",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         type="object",
     *         required={"id","product_id","quantity"},
     *         @OA\Property(property="id", type="integer", example=123),
     *         @OA\Property(property="product_id", type="integer", example=1425296),
     *         @OA\Property(property="variation_id", type="integer", nullable=true, example=null),
     *         @OA\Property(property="quantity", type="integer", minimum=1, example=3)
     *       ),
     *       example={"id":123, "product_id":1425296, "variation_id":null, "quantity":3}
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function replace(Request $request)
    {
        return $this->repository->replace($request);
    }

    /**
     * @OA\Post(
     *   path="/api/sync/cart",
     *   tags={"Carts"},
     *   summary="Sync cart",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           required={"product_id","quantity"},
     *           @OA\Property(property="product_id", type="integer", example=1425296),
     *           @OA\Property(property="variation_id", type="integer", nullable=true, example=null),
     *           @OA\Property(property="quantity", type="integer", minimum=1, example=1)
     *         )
     *       ),
     *       example={{"product_id":1425296, "variation_id":null, "quantity":1}, {"product_id":1427777, "variation_id":55, "quantity":2}}
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function sync(SyncCartRequest $request)
    {
        return $this->repository->syncCart($request);
    }
}
