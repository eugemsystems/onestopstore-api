<?php

namespace App\Http\Traits;

use Exception;
use App\Helpers\Helpers;
use App\Enums\AmountEnum;
use Illuminate\Http\Request;
use App\Enums\ShippingRuleEnum;
use App\GraphQL\Exceptions\ExceptionHandler;
use Illuminate\Support\Facades\Log;

trait CheckoutTrait
{
  use UtilityTrait, CouponTrait, ShippingTrait, WalletPointsTrait;

  public function calculate(Request $request)
  {
    try {
      // Check if cart contains gift cards and validate restrictions
      $hasGiftCards = $this->checkForGiftCards($request->products);

      if ($hasGiftCards) {
        // Validate gift card restrictions
        if ($request->wallet_balance || $request->points_amount) {
          throw new Exception("Gift vouchers cannot be purchased using wallet balance or points. Please use a standard payment method.", 400);
        }

        // All products must be gift cards (no mixing)
        $allGiftCards = $this->allProductsAreGiftCards($request->products);
        if (!$allGiftCards) {
          throw new Exception("Gift vouchers must be purchased separately from other products.", 400);
        }
      }

      $settings = Helpers::getSettings();
      $amount = Helpers::getTotalAmount($request->products);

      // Allow internal-only flows (wallet/points) without requiring a gateway method
      $pm = strtolower((string) ($request->payment_method ?? ''));
      $internalOnly = (($pm === '' || $pm === 'wallet') && ($request->wallet_balance || $request->points_amount));
      if ($internalOnly) {
        $minOrderAmount = $settings['general']['min_order_amount'];
        $products = $this->getUniqueProducts($request->products);
        $request->merge(['products' => $products]);
        if ($amount < $minOrderAmount) {
          throw new Exception("Please ensure your order is at least {$minOrderAmount} before proceed.", 422);
        }

        $outOfStockProduct = $this->isOutOfStock($request->products);
        if ($outOfStockProduct) {
          return $outOfStockProduct;
        }

        return $this->getCosts($request);
      }

     // If no payment method is provided, still allow cost calculation (for initial checkout load)
      if ($pm === '') {
        $minOrderAmount = $settings['general']['min_order_amount'];
        $products = $this->getUniqueProducts($request->products);
        $request->merge(['products' => $products]);
        if ($amount < $minOrderAmount) {
          throw new Exception("Please ensure your order is at least {$minOrderAmount} before proceed.", 422);
        }

        $outOfStockProduct = $this->isOutOfStock($request->products);
        if ($outOfStockProduct) {
          return $outOfStockProduct;
        }

        return $this->getCosts($request);
      }

      if ($this->isActivePaymentMethod($request->payment_method, $amount)) {
        $minOrderAmount = $settings['general']['min_order_amount'];
        $products = $this->getUniqueProducts($request->products);
        $request->merge(['products' => $products]);
        if ($amount < $minOrderAmount) {
          throw new Exception("Please ensure your order is at least {$minOrderAmount} before proceed.", 422);
        }

        $outOfStockProduct = $this->isOutOfStock($request->products);
        if ($outOfStockProduct) {
          return $outOfStockProduct;
        }

        return $this->getCosts($request);
      }

      // If we reach here, payment method is not active - throw error
      throw new Exception('The selected payment method is not available for this order amount.', 400);

    } catch (Exception $e) {
      throw new ExceptionHandler($e->getMessage(), $e->getCode());
    }
  }

  public function getCosts($request)
  {
    $shippingRules = $this->getShippingRules($request->shipping_address_id);
    return $this->calculateCosts($request, $shippingRules);
  }

    public function calculateCosts($request, $shippingRules)
    {
        //Log::info('requests', [$request['delivery_price']]);
        try {

            $tax = [];
            $points = 0;
            $pointsAmount = 0;
            $walletBalance = 0;
            $shippingTotal = 0;
            $fastShippingTotal = 0;
            $perProductCost = [];
            $couponTotalDiscount = [];
            $convert_point_amount = 0;
            $convert_wallet_balance = 0;
            $settings = Helpers::getSettings();
            $amount = Helpers::getTotalAmount($request->products);
            $deliveryPrice = $request['delivery_price'] ?? 0;

            // Get free shipping threshold, default to 100 if not set
            $freeShippingThreshold = floatval($settings['general']['min_order_free_shipping'] ?? 100);

            // Check if cart contains ONLY gift cards - if so, skip all shipping
            $hasGiftCards = $this->checkForGiftCards($request->products);

            foreach ($request->products as $product) {
                $shippingCost = 0;
                $perProductTax = 0;
                $perProductDiscount = 0;
                $perProductShippingCost = 0;
                $perProductFastShippingCost = 0;

                // ALWAYS fetch and validate price from the database (checks promo dates, is_sale_enable, etc.).
                // Ignore any price the client sends — the getSalePrice() method handles Yoco and all
                // other payment methods correctly by returning the currently-valid sale or base price.
                // This prevents expired promo prices from being applied if the client has a stale cache.
                $singleProductPrice = Helpers::getSalePrice($product);

                $subTotal = Helpers::getSubTotal($singleProductPrice, $product['quantity']);
                $selectedMethod = null;

                // Skip all shipping logic for gift cards
                $prodModel = \App\Models\Product::find($product['product_id']);
                $isGiftCard = $prodModel && $prodModel->is_gift_card;

                // Check if fast shipping was selected in frontend (from order creation form)
                // Skip this for gift cards
                if (!$isGiftCard && isset($product['has_fast_shipping']) && $product['has_fast_shipping'] && isset($product['fast_shipping_cost'])) {
                    // Fast shipping selected from frontend
                    $perProductFastShippingCost = (float) $product['fast_shipping_cost'] * (int)($product['quantity'] ?? 1);
                    $fastShippingTotal += $perProductFastShippingCost;
                    $selectedMethod = 'expedited';

                }

                // Per-item shipping choice (standard/expedited) for flagged products
                // Only check this if fast shipping wasn't already set from frontend
                // Skip this for gift cards
                if (!$isGiftCard && $selectedMethod === null) {
                try {
                    // ONLY process per-item shipping if product actually HAS shipping prices > 0
                    // Check for > 0 because 0 means "not configured", should use global rules
                    $hasExpedited = $prodModel && $prodModel->expedited_shipping_price !== null && $prodModel->expedited_shipping_price > 0;
                    $hasStandard = $prodModel && $prodModel->standard_shipping_price !== null && $prodModel->standard_shipping_price > 0;

                    if ($hasExpedited || $hasStandard) {
                        $method = strtolower((string)($product['item_shipping_method'] ?? ''));

                        // DEBUG: Log what we're receiving


                        // Default to 'standard' if none provided and standard price exists and is > 0
                        if ($method === '' && $hasStandard) {
                            $method = 'standard';
                        }

                        if ($method === 'expedited' && $hasExpedited) {
                            // Only add to fast_shipping_cost when expedited is selected
                            $perProductFastShippingCost = (float) $prodModel->expedited_shipping_price * (int)($product['quantity'] ?? 1);
                            $fastShippingTotal += $perProductFastShippingCost;
                            $selectedMethod = 'expedited';


                        } elseif ($method === 'standard' && $hasStandard) {
                            // Standard shipping goes to regular shipping_cost, NOT fast_shipping_cost
                            $perProductShippingCost = (float) $prodModel->standard_shipping_price * (int)($product['quantity'] ?? 1);
                            $shippingTotal += $perProductShippingCost;
                            $selectedMethod = 'standard';

                        }
                    }
                } catch (\Throwable $e) {
                    // ignore method errors and continue with default shipping rules
                    Log::error('SHIPPING METHOD ERROR', ['error' => $e->getMessage()]);
                }
                } // End of if ($selectedMethod === null) check

                // Only apply default shipping rules if no per-item shipping was selected
                // For the FIRST product, check if we should apply shipping rules
                // Skip all of this for gift cards
                static $shippingRulesApplied = false;

                if (!$isGiftCard && $selectedMethod === null && $amount < $freeShippingThreshold) {


                    if ($shippingRules && !$shippingRulesApplied) {
                        if ($this->isNotFreeShipping($product['product_id'])) {
                            foreach ($shippingRules as $shippingRule) {
                                switch ($shippingRule->rule_type) {

                                    case ShippingRuleEnum::BASE_ON_WEIGHT:
                                        // Weight-based: apply per product (heavier = more cost)
                                        $shippingCost = $this->baseOnWeight($product, $shippingRule);
                                        if ($shippingCost > 0) {
                                            $perProductShippingCost = $shippingCost;
                                        }

                                        $shippingTotal += $shippingCost;

                                        break;

                                    case ShippingRuleEnum::BASE_ON_PRICE:
                                        // Price-based: apply ONCE per order (flat rate)
                                        $shippingCost = $this->baseOnPrice($product, $shippingRule);
                                        if ($shippingCost > 0) {
                                            $perProductShippingCost = $shippingCost;
                                            $shippingTotal += $shippingCost;
                                            $shippingRulesApplied = true; // Mark as applied so we don't apply again

                                        }
                                        break;

                                    default:
                                        $shippingCost = 0;
                                        $shippingTotal += $shippingCost;
                                }
                            }
                        }

                    }
                }
                if (isset($request->coupon)) {
                    $coupon = Helpers::getCoupon($request->coupon);
                    if ($this->isValidCoupon($coupon, $amount, $this->getConsumerId($request))) {
                        if ($this->isIncludeOrExclude($coupon, $product)) {
                            switch ($coupon->type) {
                                case AmountEnum::FIXED:
                                    $perProductDiscount = $this->fixedDiscount($subTotal, $coupon->amount);
                                    break;

                                case AmountEnum::PERCENTAGE:
                                    $perProductDiscount =  $this->percentageDiscount($subTotal, $coupon->amount);
                                    break;

                                default:
                                    $perProductShippingCost = 0;
                                    $shippingTotal = 0;
                            }

                            $couponTotalDiscount[] = $perProductDiscount;
                            $subTotal = $subTotal - $perProductDiscount;
                        }
                    }
                }

                $perProductTax = $this->getTax($product['product_id'], $subTotal);
                $tax[] = $perProductTax;

                // Get estimated_delivery_text from product model
                $estimatedDeliveryText = null;
                if ($prodModel && $prodModel->estimated_delivery_text) {
                    $estimatedDeliveryText = $prodModel->estimated_delivery_text;
                }

                // Build the per-product cost array with all required fields
                $perProductCost[] = [
                    'store_id'              => Helpers::getStoreIdByProductId($product['product_id']),
                    'product_id'            => $product['product_id'],
                    'variation_id'          => $product['variation_id'] ?? null,
                    'selected_attribute_ids' => $product['selected_attribute_ids'] ?? null,
                    'variation_display_name' => $product['variation_display_name'] ?? null,
                    'tax'                   => $perProductTax,
                    'shipping_cost'         => $perProductShippingCost,  // From global shipping rules OR 0
                    'fast_shipping_cost'    => $perProductFastShippingCost, // From per-product expedited shipping
                    'item_shipping_method'  => $selectedMethod ?? 'standard', // Default to 'standard' if not explicitly set
                    'has_fast_shipping'     => $perProductFastShippingCost > 0,
                    'item_status'           => 'pending',
                    'estimated_delivery_text' => $estimatedDeliveryText,
                    'single_price'          => $singleProductPrice,
                    'quantity'              => $product['quantity'],
                    'subtotal'              => $subTotal,
                    // Product snapshot — frozen at order time so order history survives product edits/deletes
                    'product_name'          => $prodModel->name ?? null,
                    'product_sku'           => $prodModel->sku ?? null,
                    'product_slug'          => $prodModel->slug ?? null,
                    'product_price'         => $prodModel->price ?? null,
                    'product_sale_price'    => $prodModel->sale_price ?? null,
                    'product_image_url'     => $prodModel?->product_thumbnail?->image_url
                                               ?? $prodModel?->product_thumbnail?->original_url
                                               ?? null,
                ];


            }

            if (Helpers::isMultiVendorEnable()) {
                foreach (array_unique(data_get($perProductCost, '*.store_id')) as $storeIds) {
                    $store_ids[] = $storeIds;
                }
            } else {
                $store_ids = array_unique(data_get($perProductCost, '*.store_id'));
            }

            $filtered_sub_Total = [];
            foreach ($store_ids as $store_id) {

                $_total = [];
                $_products = [];
                $_tax_total = [];
                $_shipping_total = [];
                $_fast_shipping_total = [];

                foreach ($perProductCost as $value) {
                    if ($value['store_id'] == $store_id) {
                        $_products[] = $value;
                        $_tax_total[] = $value['tax'];
                        $_shipping_total[] = $value['shipping_cost'];
                        $_fast_shipping_total[] = $value['fast_shipping_cost'] ?? 0;
                        $_total[] = $value['subtotal'];
                    }
                }

                $_item['store'] = $store_id;
                $_item['products'] = $_products;
                $_item['total'] = [
                    'tax_total' => $this->formatDecimal(array_sum($_tax_total)),
                    'shipping_total' => $this->formatDecimal(array_sum($_shipping_total)),
                    'fast_shipping_total' => $this->formatDecimal(array_sum($_fast_shipping_total)),
                    'sub_total' => $this->formatDecimal(array_sum($_total)),
                    'total' => $this->formatDecimal(array_sum($_tax_total) + array_sum($_shipping_total) + array_sum($_fast_shipping_total) + array_sum($_total)),
                    'convert_point_amount' => $this->formatDecimal($convert_point_amount),
                    'convert_wallet_balance' => $this->formatDecimal($convert_wallet_balance),
                    'coupon_total_discount' => $this->formatDecimal(array_sum($couponTotalDiscount)),
                ];

                $filtered_sub_Total[] = array_sum($_total);
                $items['items'][] = $_item;
            }

            if (Helpers::pointIsEnable()) {
                $points = $this->getPointAmount($this->getConsumerId($request));
                $convert_point_amount = - ($this->pointsToCurrency($points));
                $pointsAmount = abs($convert_point_amount);
            }

            if (Helpers::walletIsEnable()) {
                $convert_wallet_balance =  - ($this->getWalletBalance($this->getConsumerId($request)));
                $walletBalance = abs($convert_wallet_balance);
            }

            $subTotal = array_sum($filtered_sub_Total);
            $couponDiscount = array_sum($couponTotalDiscount);

            // If an admin-provided tax_total override exists (e.g. from the Apply Tax checkbox on the
            // POS create-order form), use it when it exceeds the DB-computed tax. Products might not
            // have a tax_id assigned in the DB, so array_sum($tax) could be 0 even though the admin
            // manually entered a tax amount. Using the override ensures the wallet is capped against
            // the FULL grand total (subtotal + shipping + delivery + tax).
            $dbComputedTax = array_sum($tax);
            $adminTaxOverride = floatval($request->input('tax_total', 0));
            $effectiveTax = max($dbComputedTax, $adminTaxOverride);

            // Build base total including tax, shipping, fast_shipping and delivery BEFORE applying discounts and wallet/points
            $total = $subTotal + $effectiveTax + $shippingTotal + $fastShippingTotal + $deliveryPrice;

            // Apply coupon first
            if ($couponDiscount > 0) {
                $total -= $couponDiscount;
                if ($total < 0) {
                    $couponDiscount = abs($total);
                    $total = 0;
                }
            }

            // Apply points against remaining total
            $convert_point_amount = 0;
            if ($request->points_amount) {
                if ($this->verifyPoints($this->getConsumerId($request), $pointsAmount)) {
                    $applyPoints = min($pointsAmount, $total);
                    $convert_point_amount = -$applyPoints;
                    $pointsAmount -= $applyPoints;
                    $total -= $applyPoints;
                }
            }

            // Apply wallet against remaining total
            $convert_wallet_balance = 0;
            if ($request->wallet_balance) {
                if ($this->verifyWallet($this->getConsumerId($request), $walletBalance)) {
                    $applyWallet = min($walletBalance, $total);
                    $convert_wallet_balance = -$applyWallet;
                    $walletBalance -= $applyWallet;
                    $total -= $applyWallet;
                }
            }

            $itemTotal = [
                'tax_total' => $this->formatDecimal(array_sum($tax)),
                'shipping_total' => $this->formatDecimal($shippingTotal),
                'fast_shipping_total' => $this->formatDecimal($fastShippingTotal),
                'points' => $this->formatDecimal($points),
                'convert_point_amount' => $this->formatDecimal($convert_point_amount),
                'points_amount' => $this->formatDecimal($pointsAmount),
                'wallet_balance' => $this->formatDecimal($walletBalance),
                'convert_wallet_balance' => $this->formatDecimal($convert_wallet_balance),
                'coupon_total_discount' => $this->formatDecimal($couponDiscount),
                'delivery_price' => $this->formatDecimal($deliveryPrice),
                'sub_total' => $this->formatDecimal($subTotal),
                'total' => $this->formatDecimal($total)
            ];


            $items['total'] = $itemTotal;
            return $items;

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

  public function calculateCosts_($request, $shippingRules)
  {
    try {

      $tax = [];
      $points = 0;
      $pointsAmount = 0;
      $walletBalance = 0;
      $shippingTotal = 0;
      $perProductCost = [];
      $couponTotalDiscount = [];
      $convert_point_amount = 0;
      $convert_wallet_balance = 0;
      $settings = Helpers::getSettings();
      $amount = Helpers::getTotalAmount($request->products);

      foreach ($request->products as $product) {
        $shippingCost = 0;
        $perProductTax = 0;
        $perProductDiscount = 0;
        $perProductShippingCost = 0;
        $singleProductPrice = Helpers::getSalePrice($product);
        $subTotal = Helpers::getSubTotal($singleProductPrice, $product['quantity']);

        if ($settings['general']['min_order_free_shipping'] >= $amount) {
          if ($shippingRules) {
            if ($this->isNotFreeShipping($product['product_id'])) {
              foreach ($shippingRules as $shippingRule) {
                switch ($shippingRule->rule_type) {

                  case ShippingRuleEnum::BASE_ON_WEIGHT:
                    $shippingCost = $this->baseOnWeight($product, $shippingRule);
                    if ($shippingCost > 0) {
                      $perProductShippingCost = $shippingCost;
                    }

                    $shippingTotal += $shippingCost;
                    break;

                  case ShippingRuleEnum::BASE_ON_PRICE:
                    $shippingCost = $this->baseOnPrice($product, $shippingRule);
                    if ($shippingCost > 0) {
                      $perProductShippingCost = $shippingCost;
                    }

                    $shippingTotal += $shippingCost;
                    break;

                  default:
                    $shippingCost = 0;
                    $shippingTotal += $shippingCost;
                }
              }
            }
          }
        }

        if (isset($request->coupon)) {
          $coupon = Helpers::getCoupon($request->coupon);
          if ($this->isValidCoupon($coupon, $amount, $this->getConsumerId($request))) {
            if ($this->isIncludeOrExclude($coupon, $product)) {
              switch ($coupon->type) {
                case AmountEnum::FIXED:
                  $perProductDiscount = $this->fixedDiscount($subTotal, $coupon->amount);
                  break;

                case AmountEnum::PERCENTAGE:
                  $perProductDiscount =  $this->percentageDiscount($subTotal, $coupon->amount);
                  break;

                default:
                  $perProductShippingCost = 0;
                  $shippingTotal = 0;
              }

              $couponTotalDiscount[] = $perProductDiscount;
              $subTotal = $subTotal - $perProductDiscount;
            }
          }
        }

        $perProductTax = $this->getTax($product['product_id'], $subTotal);
        $tax[] = $perProductTax;

        // Get estimated_delivery_text from product model
        $prodModel = \App\Models\Product::find($product['product_id']);
        $estimatedDeliveryText = null;
        if ($prodModel && $prodModel->estimated_delivery_text) {
          $estimatedDeliveryText = $prodModel->estimated_delivery_text;
        }

        $perProductCost[] = [
          'store_id'  =>      Helpers::getStoreIdByProductId($product['product_id']),
          'product_id' =>     $product['product_id'],
          'variation_id' =>   $product['variation_id'],
          'selected_attribute_ids' => $product['selected_attribute_ids'] ?? null,
          'variation_display_name' => $product['variation_display_name'] ?? null,
          'tax' =>            $perProductTax,
          'shipping_cost' =>  $perProductShippingCost,
          'estimated_delivery_text' => $estimatedDeliveryText,
          'single_price' =>   $singleProductPrice,
          'quantity' =>       $product['quantity'],
          'subtotal' =>       $subTotal,
          // Product snapshot — frozen at order time so order history survives product edits/deletes
          'product_name'       => $prodModel->name ?? null,
          'product_sku'        => $prodModel->sku ?? null,
          'product_slug'       => $prodModel->slug ?? null,
          'product_price'      => $prodModel->price ?? null,
          'product_sale_price' => $prodModel->sale_price ?? null,
          'product_image_url'  => $prodModel?->product_thumbnail?->image_url
                                  ?? $prodModel?->product_thumbnail?->original_url
                                  ?? null,
        ];
      }

      if (Helpers::isMultiVendorEnable()) {
        foreach (array_unique(data_get($perProductCost, '*.store_id')) as $storeIds) {
          $store_ids[] = $storeIds;
        }
      } else {
        $store_ids = array_unique(data_get($perProductCost, '*.store_id'));
      }

      $filtered_sub_Total = [];
      foreach ($store_ids as $store_id) {

        $_total = [];
        $_products = [];
        $_tax_total = [];
        $_shipping_total = [];

        foreach ($perProductCost as $value) {
          if ($value['store_id'] == $store_id) {
            $_products[] = $value;
            $_tax_total[] = $value['tax'];
            $_shipping_total[] = $value['shipping_cost'];
            $_total[] = $value['subtotal'];
          }
        }

        $_item['store'] = $store_id;
        $_item['products'] = $_products;
        $_item['total'] = [
          'tax_total' => $this->formatDecimal(array_sum($_tax_total)),
          'shipping_total' => $this->formatDecimal(array_sum($_shipping_total)),
          'sub_total' => $this->formatDecimal(array_sum($_total)),
          'total' => $this->formatDecimal(array_sum($_tax_total) + array_sum($_shipping_total) + array_sum($_total)),
          'convert_point_amount' => $this->formatDecimal($convert_point_amount), // FIXED: was $convert_wallet_balance
          'convert_wallet_balance' => $this->formatDecimal($convert_wallet_balance), // FIXED: was $convert_point_amount
          'coupon_total_discount' => $this->formatDecimal(array_sum($couponTotalDiscount)),
        ];

        $filtered_sub_Total[] = array_sum($_total);
        $items['items'][] = $_item;
      }

      if (Helpers::pointIsEnable()) {
        $points = $this->getPointAmount($this->getConsumerId($request));
        $convert_point_amount = - ($this->pointsToCurrency($points));
        $pointsAmount = abs($convert_point_amount);
      }

      if (Helpers::walletIsEnable()) {
        $convert_wallet_balance =  - ($this->getWalletBalance($this->getConsumerId($request)));
        $walletBalance = abs($convert_wallet_balance);
      }

      $subTotal = array_sum($filtered_sub_Total);
      $total = $amount;
      $couponDiscount = array_sum($couponTotalDiscount);

      if ($request->wallet_balance) {
        if ($this->verifyWallet($this->getConsumerId($request), $walletBalance)) {
          $convert_wallet_balance = abs($walletBalance);
          $walletBalance -=  $convert_wallet_balance;
          $total -= $convert_wallet_balance;
          if ($total < 0) {
            $walletBalance = abs($total);
            $total = 0;
          }

          if ($walletBalance > 0) {
            $convert_wallet_balance -= $walletBalance;
          }

          if ($walletBalance <= 0) {
            $convert_point_amount = - (min($pointsAmount, ($total - $walletBalance)));
          }

          $convert_wallet_balance = -$convert_wallet_balance;
        }
      }

      if ($request->points_amount) {
        if ($this->verifyPoints($this->getConsumerId($request), $pointsAmount)) {
          $convert_point_amount =  abs($pointsAmount);
          $pointsAmount -=  $convert_point_amount;
          $total -= $convert_point_amount;

          if ($total < 0) {
            $pointsAmount = abs($total);
            $total = 0;
          }

          if ($pointsAmount > 0) {
            $convert_point_amount -= $pointsAmount;
          }

          $convert_point_amount = -$convert_point_amount;
        }
      }

      if ($couponDiscount > 0) {
        $total -= $couponDiscount;
        if ($total < 0) {
          $couponDiscount = abs($total);
          $total = 0;
        }
      }

      $total +=  array_sum($tax) + $shippingTotal;
      $itemTotal = [
        'tax_total' => $this->formatDecimal(array_sum($tax)),
        'shipping_total' => $this->formatDecimal($shippingTotal),
        'points' => $this->formatDecimal($points),
        'convert_point_amount' => $this->formatDecimal($convert_point_amount),
        'points_amount' => $this->formatDecimal($pointsAmount),
        'wallet_balance' => $this->formatDecimal($walletBalance),
        'convert_wallet_balance' => $this->formatDecimal($convert_wallet_balance),
        'coupon_total_discount' => $this->formatDecimal($couponDiscount),
        'sub_total' => $this->formatDecimal($subTotal),
        'total' => $this->formatDecimal($total)
      ];

      $items['total'] = $itemTotal;
      return $items;

    } catch (Exception $e) {

      throw new ExceptionHandler($e->getMessage(), $e->getCode());
    }
  }

  public function getTax($product_id, $subtotal)
  {
    $tax = 0;
    $tax_id = $this->getTaxId($product_id);
    $taxRate = $this->getTaxRate($tax_id);
    if ($taxRate) {
      $tax = ($subtotal * $taxRate) / 100;
    }

    return  $tax;
  }

  /**
   * Check if any product in the cart is a gift card
   */
  protected function checkForGiftCards($products)
  {
    foreach ($products as $product) {
      $productModel = \App\Models\Product::find($product['product_id']);
      if ($productModel && $productModel->is_gift_card) {
        return true;
      }
    }
    return false;
  }

  /**
   * Check if ALL products are gift cards
   */
  protected function allProductsAreGiftCards($products)
  {
    foreach ($products as $product) {
      $productModel = \App\Models\Product::find($product['product_id']);
      if (!$productModel || !$productModel->is_gift_card) {
        return false;
      }
    }
    return true;
  }
}
