<?php

namespace App\Http\Controllers;

use App\Helpers\Helpers;
use App\Http\Traits\CheckoutTrait;
use App\Http\Requests\CalculateCheckoutRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Checkout", description="Checkout & payments")
 */
class CheckoutController extends Controller
{
    use CheckoutTrait;

    /**
     * @OA\Post(
     *   path="/api/checkout",
     *   tags={"Checkout"},
     *   summary="Verify checkout",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         type="object",
     *         required={"products","payment_method","billing_address_id","shipping_address_id"},
     *         @OA\Property(
     *           property="products",
     *           type="array",
     *           @OA\Items(
     *             type="object",
     *             required={"product_id","quantity"},
     *             @OA\Property(property="product_id", type="integer", example=1425296),
     *             @OA\Property(property="variation_id", type="integer", nullable=true, example=null),
     *             @OA\Property(property="quantity", type="integer", minimum=1, example=1)
     *           )
     *         ),
     *         @OA\Property(property="coupon", type="string", nullable=true, example="SAVE10"),
     *         @OA\Property(property="payment_method", type="string", enum={"cod","bank_transfer","paypal","pese","payfast","pdo_zambia","yoco"}, example="cod"),
     *         @OA\Property(property="billing_address_id", type="integer", example=1),
     *         @OA\Property(property="shipping_address_id", type="integer", example=1),
     *         @OA\Property(property="wallet_balance", type="boolean", example=false),
     *         @OA\Property(property="points_amount", type="boolean", example=false),
     *         @OA\Property(property="delivery_price", type="number", format="float", example=0)
     *       ),
     *       example={
     *         "products": {{"product_id":1425296, "variation_id":null, "quantity":1}},
     *         "payment_method": "cod",
     *         "billing_address_id": 1,
     *         "shipping_address_id": 1,
     *         "coupon": null,
     *         "wallet_balance": false,
     *         "points_amount": false,
     *         "delivery_price": 0
     *       }
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function verifyCheckout(CalculateCheckoutRequest $request)
    {
        $result = $this->calculate($request);

        Helpers::logMobilePayload($request->path(), $request->userAgent(), $result);

        return $result;
    }
}
