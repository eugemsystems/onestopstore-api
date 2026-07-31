<?php

namespace App\Repositories\Eloquents;

use Exception;
use App\Models\Cart;
use App\Helpers\Helpers;
use Illuminate\Support\Facades\DB;
use App\GraphQL\Exceptions\ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;

class CartRepository extends BaseRepository
{
    public function boot()
    {
        try {

            $this->pushCriteria(app(RequestCriteria::class));

        } catch (ExceptionHandler $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    function model()
    {
       return Cart::class;
    }

    public function index($request)
    {
        $cartItems = $this->model->where('consumer_id', Helpers::getCurrentUserId())->latest('created_at')
            ->paginate($request->paginate ?? $this->model->count());

        $cart = $this->getCartTotal($cartItems);
        return $cart;
    }

    public function getCartTotal($cartItems)
    {
        $sub_total = [];
        $cart['items'] = [];
        $shipping_total = [];
        $hasGiftCards = false;

        foreach ($cartItems as $cartItem) {
            $cart['items'][] = $cartItem;
            $sub_total[] = $cartItem->sub_total;
            if ($cartItem->item_shipping_cost !== null) { $shipping_total[] = (float)$cartItem->item_shipping_cost; }
            $cartItem->product;

            // Check if this item is a gift card
            if ($cartItem->product && $cartItem->product->is_gift_card) {
                $hasGiftCards = true;
            }
        }

        $cart['sub_total'] = Helpers::formatDecimal(array_sum($sub_total));
        $cart['shipping_total'] = Helpers::formatDecimal(array_sum($shipping_total));
        $cart['total'] = Helpers::formatDecimal(array_sum($sub_total) + array_sum($shipping_total));
        $cart['has_gift_cards'] = $hasGiftCards; // Flag to indicate gift cards in cart

        return $cart;
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {
            // Validate gift card cart restrictions before adding
            $this->validateGiftCardCart($request->all());

            $cartItems[] = $this->verifyCartItem($request->all());
            $cart = $this->getCartTotal($cartItems);

            DB::commit();
            return $cart;

        } catch (Exception $e) {

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function update($request, $id = null)
    {

        DB::beginTransaction();
        try {

            $cart = $this->verifyCartItem($request);
            if ($cart) {
                $cartItems = $this->model->where('consumer_id', Helpers::getCurrentUserId())->get();
                $cart = $this->getCartTotal($cartItems);
            }

            DB::commit();
            return $cart;

        } catch (Exception $e) {

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function replace($request)
    {
        DB::beginTransaction();
        try {

            if ($this->isStockAvailable($request, $request->quantity)) {
                $cart = $this->model->findOrFail($request->id);
                $singleProductPrice = Helpers::getSalePrice($request);
                $subTotal = Helpers::getSubTotal($singleProductPrice, $request->quantity);

                $updatePayload = [
                    'product_id' => $request->product_id,
                    'variation_id' => $request->variation_id,
                    'quantity' => $request->quantity,
                    'sub_total' => Helpers::roundNumber($subTotal),
                ];
                if (!empty($request->item_shipping_method)) {
                    $method = strtolower((string)$request->item_shipping_method);
                    $updatePayload['item_shipping_method'] = $method;
                    try {
                        $prod = \App\Models\Product::find($request->product_id);
                        if ($prod && (int)$prod->has_expedited_shipping === 1) {
                            if ($method === 'expedited' && $prod->expedited_shipping_price !== null) {
                                $updatePayload['item_shipping_cost'] = (float) $prod->expedited_shipping_price;
                            } elseif ($method === 'standard' && $prod->standard_shipping_price !== null) {
                                $updatePayload['item_shipping_cost'] = (float) $prod->standard_shipping_price;
                            }
                        }
                    } catch (\Throwable $e) {}
                }
                $cart->update($updatePayload);

                DB::commit();

                $cart = $cart->fresh();
                return $cart;
            }

            throw new Exception("You cannot add more than {$request->quantity} items.", 400);

        } catch (Exception $e) {

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function isStockAvailable($product, $quantity)
    {
        $item = Helpers::getProductStock($product['product_id']);
        if (isset($product['variation_id'])) {
            $item = Helpers::getVariationStock($product['variation_id']);
        }

        if ($quantity <= $item->quantity && $item->quantity > 0) {
            return true;
        }

        return false;
    }

    public function verifyCartItem($request)
    {
        try {
            // Validate gift card restrictions BEFORE processing
            $this->validateGiftCardCart($request);

            $singleProductPrice = Helpers::getSalePrice($request);
            $subTotal = Helpers::getSubTotal($singleProductPrice, $request['quantity']);

            // compute per-item shipping cost if method provided and product supports dual shipping
            $itemShippingMethod = isset($request['item_shipping_method']) ? strtolower((string)$request['item_shipping_method']) : null;
            $itemShippingCost = null;
            try {
                $prod = \App\Models\Product::find($request['product_id']);
                if ($prod && (int)$prod->has_expedited_shipping === 1 && $itemShippingMethod) {
                    if ($itemShippingMethod === 'expedited' && $prod->expedited_shipping_price !== null) {
                        $itemShippingCost = (float) $prod->expedited_shipping_price;
                    } elseif ($itemShippingMethod === 'standard' && $prod->standard_shipping_price !== null) {
                        $itemShippingCost = (float) $prod->standard_shipping_price;
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }

            $cart = $this->getCartData($request);

            if ($cart) {
                $quantity = $cart->quantity + $request['quantity'];
                if (!$this->isStockAvailable($request, $quantity)) {
                    throw new Exception("You cannot add more than {$cart->quantity} items.", 400);
                }

                $updatePayload = [
                    'quantity' => $cart->quantity + $request['quantity'],
                    'sub_total' => Helpers::formatDecimal($cart->sub_total + $subTotal),
                ];
                if ($itemShippingMethod) { $updatePayload['item_shipping_method'] = $itemShippingMethod; }
                if ($itemShippingCost !== null) { $updatePayload['item_shipping_cost'] = $itemShippingCost; }
                $cart->update($updatePayload);

            } else  {
                $cart = $this->model->create([
                    'product_id' => $request['product_id'],
                    'variation_id' => $request['variation_id'],
                    'quantity' => $request['quantity'],
                    'sub_total' => Helpers::formatDecimal($subTotal),
                    'item_shipping_method' => $itemShippingMethod,
                    'item_shipping_cost' => $itemShippingCost,
                ]);
            }

            $cart->product;
            $cart->variation;

            return $cart;

        } catch (Exception $e) {

            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function getCartData($product)
    {
        return $this->model->where([
            ['product_id', $product['product_id']],
            ['variation_id', $product['variation_id']],
            ['consumer_id', Helpers::getCurrentUserId()]
        ])->first();
    }

    public function destroy($id)
    {
        try {

            return $this->model->findOrFail($id)->destroy($id);

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function syncCart($request)
    {
        DB::beginTransaction();
        try {

            foreach($request->all() as $cart) {
                $cartItems[] = $this->verifyCartItem($cart);
                $cart = $this->getCartTotal($cartItems);
            }

            DB::commit();
            return $cart;

        } catch (Exception $e) {

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function clearCart()
    {
        DB::beginTransaction();
        try {
            $cartItems = $this->model->where('consumer_id', Helpers::getCurrentUserId())->get();
            foreach ($cartItems as $cartItem) {
                $cartItem->delete();
            }

            DB::commit();
            return true;

        } catch (Exception $e) {

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Validate gift card cart restrictions
     * - Gift cards cannot be mixed with regular products
     * - If cart has gift cards, reject regular products
     * - If cart has regular products, reject gift cards
     * - Allow adding the same product (quantity increase)
     */
    protected function validateGiftCardCart($request)
    {
        // Get the product being added
        $newProduct = \App\Models\Product::find($request['product_id']);
        if (!$newProduct) {
            throw new ExceptionHandler("Product not found.", 404);
        }

        $isNewProductGiftCard = (bool) $newProduct->is_gift_card;

        // Get existing cart items for this user
        $existingCartItems = $this->model->where('consumer_id', Helpers::getCurrentUserId())->get();

        if ($existingCartItems->isEmpty()) {
            // Empty cart, allow adding
            return true;
        }

        // Check existing cart items
        foreach ($existingCartItems as $cartItem) {
            // Skip validation if it's the same product (quantity increase is allowed)
            if ($cartItem->product_id == $request['product_id'] &&
                ($cartItem->variation_id ?? null) == ($request['variation_id'] ?? null)) {
                continue; // Same product, allow quantity increase
            }

            $existingProduct = \App\Models\Product::find($cartItem->product_id);
            if (!$existingProduct) {
                continue;
            }

            $isExistingGiftCard = (bool) $existingProduct->is_gift_card;

            // If trying to add gift card but cart has regular products (different products)
            if ($isNewProductGiftCard && !$isExistingGiftCard) {
                throw new ExceptionHandler("Gift vouchers cannot be purchased with other products. Please checkout your current items first or clear your cart.", 400);
            }

            // If trying to add regular product but cart has gift cards (different products)
            if (!$isNewProductGiftCard && $isExistingGiftCard) {
                throw new ExceptionHandler("Your cart contains gift vouchers. Please checkout gift vouchers separately or clear your cart to add other products.", 400);
            }
        }

        return true;
    }
}
