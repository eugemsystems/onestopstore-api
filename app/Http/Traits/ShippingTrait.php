<?php

namespace App\Http\Traits;

use App\Models\Address;
use App\Models\Product;
use App\Helpers\Helpers;
use App\Enums\AmountEnum;
use App\Models\ShippingRule;
use App\Models\Shipping as ShippingModal;

trait ShippingTrait
{
  public function getProductWeight($product_id)
  {
    return Product::where('id', $product_id)->pluck('weight')->first();
  }

  public function isNotFreeShipping($product_id)
  {
    return Product::where('id', $product_id)->where('is_free_shipping', false)->pluck('id')->first();
  }

  public function getCountryId($shipping_address_id)
  {
    return Address::where('id', $shipping_address_id)->pluck('country_id')->first();
  }

  public function getShipping($shipping_address_id)
  {
    $country_id = $this->getCountryId($shipping_address_id);
    return ShippingModal::where('country_id', $country_id)->where('status', true)->get();
  }

  public function getShippingRules($shipping_address_id, $shippingRule = null)
  {
    $shippings = $this->getShipping($shipping_address_id);
    $allRules = collect();


    foreach ($shippings as $shipping) {
      $rules = ShippingRule::where('shipping_id', $shipping->id)->where('status', true)->get();

      $allRules = $allRules->merge($rules);
    }


    return $allRules->isNotEmpty() ? $allRules : null;
  }

  public function isOptimum($value, $shippingRule)
  {
    return (max(min($value, $shippingRule->max), $shippingRule->min) == $value);
  }

  public function baseOnWeight($product, $shippingRule)
  {
    $shippingAmount = 0;
    $productWeight = $this->getProductWeight($product['product_id']);
    $singleProductPrice = Helpers::getSalePrice($product);
    $subTotal = Helpers::getSubTotal($singleProductPrice, $product['quantity']);

    if ($this->isOptimum($productWeight, $shippingRule)) {
      switch ($shippingRule->shipping_type) {

        case AmountEnum::FIXED:
          $shippingAmount += $product['quantity'] * $shippingRule->amount;
          break;

        case AmountEnum::PERCENTAGE:
          $shippingAmount +=  ($subTotal * $shippingRule->amount) / 100;
          break;

        default:
          $shippingAmount = 0;
      }
    }

    return $shippingAmount;
  }

  public function baseOnPrice($product, $shippingRule)
  {
    $shippingAmount = 0;
    $singleProductPrice = Helpers::getSalePrice($product);
    $subTotal = Helpers::getSubTotal($singleProductPrice, $product['quantity']);



    // For price-based shipping, typically we check if ANY product in the order qualifies
    // The rule Min: 1, Max: 99 means "apply R10 shipping if product price is 1-99"
    // Since your products are likely in this range, we should apply it once per order, not per product

    $priceInRange = $this->isOptimum($singleProductPrice, $shippingRule);
    $subtotalInRange = $this->isOptimum($subTotal, $shippingRule);

    // Apply if either the single price OR subtotal falls within the range
    if ($priceInRange || $subtotalInRange) {
      switch ($shippingRule->shipping_type) {

        case AmountEnum::FIXED:
          // For FIXED shipping, apply the amount ONCE (not per product, not per quantity)
          $shippingAmount = $shippingRule->amount;

          break;

        case AmountEnum::PERCENTAGE:
          // For PERCENTAGE, apply based on subtotal
          $shippingAmount = ($subTotal * $shippingRule->amount) / 100;
          break;

        default:
          $shippingAmount = 0;
      }
    }

    return $shippingAmount;
  }
}
