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
                $cart = $this->model->where('consumer_id', Helpers::getCurrentUserId())->find($request->id);
                if (!$cart) {
                    throw new Exception("Cart item not found.", 404);
                }
                $singleProductPrice = Helpers::getSalePrice($request);
                $subTotal = Helpers::getSubTotal($singleProductPrice, $request->quantity);

                $updatePayload = [
                    'product_id' => $request->product_id,
                    'variation_id' => $request->variation_id,
                    'delivery_option_id' => $request->delivery_option_id,
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
        // Products with delivery options are stocked per option
        $activeOptions = \App\Models\ProductDeliveryOption::where('product_id', $product['product_id'])->where('status', 1)->get();
        if ($activeOptions->isNotEmpty()) {
            $optionId = $product['delivery_option_id'] ?? null;
            $option = $optionId ? $activeOptions->firstWhere('id', (int) $optionId) : null;
            if (!$option) {
                throw new Exception('Please choose a delivery option for this product.', 400);
            }
            if (!$option->isInStock()) {
                throw new Exception('The selected delivery option is out of stock. Please choose another option.', 400);
            }
            return $quantity <= (int) $option->quantity;
        }

        $item = Helpers::getProductStock($product['product_id']);
        if (isset($product['variation_id'])) {
            $item = Helpers::getVariationStock($product['variation_id']);
        }

        // getProductStock()/getVariationStock() return null when the item is out
        // of stock or inactive; reading ->quantity on that crashed with a 500.
        if (!$item) {
            throw new Exception('This product is out of stock or no longer available. Please remove it from your cart.', 400);
        }

        if ($quantity <= $item->quantity && $item->quantity > 0) {
            return true;
        }

        return false;
    }

    /**
     * A product with active delivery options can only be in the cart with a valid, in-stock option.
     * Returns an error message, or null when the line is fine.
     */
    public function deliveryOptionError($item): ?string
    {
        $productId = $item['product_id'] ?? null;
        if (!$productId) {
            return null;
        }
        $options = \App\Models\ProductDeliveryOption::where('product_id', $productId)->where('status', 1)->get();
        if ($options->isEmpty()) {
            return null;
        }
        $optionId = $item['delivery_option_id'] ?? null;
        $option = $optionId ? $options->firstWhere('id', (int) $optionId) : null;
        if (!$option) {
            return 'Please choose a delivery option for this product.';
        }
        if (!$option->isInStock()) {
            return 'The selected delivery option is out of stock. Please choose another option.';
        }
        return null;
    }

    public function verifyCartItem($request)
    {
        try {
            // Products sold with delivery options need one chosen
            if ($error = $this->deliveryOptionError($request)) {
                throw new Exception($error, 400);
            }

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
                    'delivery_option_id' => $request['delivery_option_id'] ?? null,
                    'quantity' => $request['quantity'],
                    'sub_total' => Helpers::formatDecimal($subTotal),
                    'item_shipping_method' => $itemShippingMethod,
                    'item_shipping_cost' => $itemShippingCost,
                ]);
            }

            $cart->product;
            $cart->variation;
            $cart->deliveryOption;

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
            ['delivery_option_id', $product['delivery_option_id'] ?? null],
            ['consumer_id', Helpers::getCurrentUserId()]
        ])->first();
    }

    public function destroy($id)
    {
        try {

            $cart = $this->model->where('consumer_id', Helpers::getCurrentUserId())->find($id);
            if (!$cart) {
                throw new Exception("Cart item not found.", 404);
            }

            return $cart->delete();

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function syncCart($request)
    {
        DB::beginTransaction();
        try {

            foreach($request->all() as $cart) {
                // Guest-cart merge: skip lines that can no longer be bought as-is (e.g. the product
                // gained delivery options after it was added) instead of failing the whole merge.
                if ($this->deliveryOptionError($cart)) {
                    continue;
                }
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
