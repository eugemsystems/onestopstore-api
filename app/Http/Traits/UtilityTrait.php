<?php

namespace App\Http\Traits;

use App\Models\Variation;
use Exception;
use App\Models\Tax;
use App\Models\Product;
use App\Helpers\Helpers;
use App\Enums\PaymentMethod;
use App\Enums\PaypalCurrencies;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait UtilityTrait
{
  public function getUniqueProducts($products)
  {
    // Normalize incoming product shapes to ensure backend expects consistent keys
    // Accepts multiple shapes for variation id: variation_id, variation.id, selectedVariation.id,
    // selected_variation.id, selectedVariationId, product.variation_id, etc.
    $normalized = collect($products)->map(function ($p) {
      $productId = data_get($p, 'product_id', data_get($p, 'product.id', null));
      // try a set of common variation locations
      $variationId = data_get($p, 'variation_id', data_get($p, 'variation.id',
                      data_get($p, 'selectedVariation.id', data_get($p, 'selected_variation.id',
                      data_get($p, 'selectedVariationId', data_get($p, 'product.variation_id', null))))));

      // Ensure numeric types when possible
      if ($productId !== null && is_numeric($productId)) $productId = (int) $productId;
      if ($variationId !== null && $variationId !== '') {
        if (is_numeric($variationId)) $variationId = (int) $variationId;
      } else {
        $variationId = null;
      }

      // Preserve all original fields but ensure top-level product_id and variation_id are present
      $p['product_id'] = $productId;
      $p['variation_id'] = $variationId;
      // Ensure quantity exists
      if (!isset($p['quantity'])) $p['quantity'] = data_get($p, 'qty', 1);

      // Extract selected attribute IDs for multi-attribute selections
      // Frontend may send this as selected_attribute_ids or variation.selected_attribute_ids
      $selectedAttrIds = data_get($p, 'selected_attribute_ids',
                          data_get($p, 'variation.selected_attribute_ids', null));
      if ($selectedAttrIds !== null) {
        $p['selected_attribute_ids'] = is_array($selectedAttrIds) ? $selectedAttrIds : json_decode($selectedAttrIds, true);
      }

      // Extract variation display name (e.g., "Blue, Brown - L")
      $displayName = data_get($p, 'variation_display_name',
                      data_get($p, 'variation.name', null));
      if ($displayName !== null) {
        $p['variation_display_name'] = $displayName;
      }

      return $p;
    });

    // Deduplicate by product_id + variation_id + selected_attribute_ids
    // This ensures different attribute combinations of the same product are separate items
    // e.g., "Blue + S" and "Blue + L" should be separate even if they map to same/no variation_id
    $unique = $normalized->unique(function ($product) {
      $pid = data_get($product, 'product_id', '');
      $vid = data_get($product, 'variation_id', '');
      $attrIds = data_get($product, 'selected_attribute_ids', []);

      // Sort attribute IDs to ensure consistent key for same combination
      if (is_array($attrIds)) {
        sort($attrIds);
        $attrKey = implode(',', $attrIds);
      } else {
        $attrKey = '';
      }

      // Also include variation_display_name as fallback for items without attribute IDs
      $vname = data_get($product, 'variation_display_name', '');

      return (string) $pid . '-' . (string) $vid . '-' . $attrKey . '-' . (string) $vname;
    })->values()->toArray();

    return $unique;
  }

  public function isEnablePaymentMethod($method)
  {
    $settings = Helpers::getSettings();
    if (isset($settings['payment_methods'][$method])) {
      $cfg = $settings['payment_methods'][$method];
      if (isset($cfg['status']) && $cfg['status']) {
        return true;
      }
    }

    return false;
  }

  public function isActivePaymentMethod($method, $amount = null)
  {
    // Allow internal settlement methods without requiring settings toggles
    if (in_array(strtolower((string)$method), ['wallet','points','layby'], true)) {
      return true;
    }

    $settings = Helpers::getSettings();
    if ($this->isEnablePaymentMethod($method)) {
      $defaultCurrencyCode = Helpers::getDefaultCurrencyCode();
      if ($method == PaymentMethod::PAYPAL) {
        if (!in_array($defaultCurrencyCode, array_column(PaypalCurrencies::cases(), 'value'))) {
          throw new Exception($defaultCurrencyCode . ' currency code is not support for '.$method, 400);
        }
      }

      return true;
    }

    throw new Exception('The provided payment method is not currently enable.', 400);
  }

  public function formatDecimal($value)
  {
    return Helpers::formatDecimal($value);
  }

  public function getConsumerId($request)
  {
    return $request->consumer_id ?? Helpers::getCurrentUserId();
  }

  public function getTaxId($product_id)
  {
    return Product::where('id', $product_id)->pluck('tax_id')->first();
  }

  public function getTaxRate($tax_id)
  {
    return Tax::where([['id', $tax_id], ['status', true]])->pluck('rate')->first();
  }

  public function isOutOfStockOriginal($products)
  {
    $outOfStockProducts = [];
    foreach ($products as $product) {
      if (isset($product['variation_id'])) {
        $variationStock = Helpers::getVariationStock($product['variation_id']);
        if (!isset($variationStock)) {
          $outOfStockProducts[] = [
            'product_id' => $product['product_id'],
            'variation_id' => $product['variation_id'],
          ];
        }
      } else {
        $productStock = Helpers::getProductStock($product['product_id']);
        if (!isset($productStock)) {
          $outOfStockProducts[] = [
            'product_id' => $product['product_id'],
          ];
        }
      }
    }

    if (!empty($outOfStockProducts)) {
      throw new Exception("Some of the products you've selected are either out of stock or inactive.", 400);
    }

    return false;
  }

    public function isOutOfStock(array $items)
    {
        foreach ($items as $row) {
            $pid = (int) data_get($row, 'product_id');
            $vid = data_get($row, 'variation_id');
            $qty = max(1, (int) data_get($row, 'quantity', 1));

            $product = Product::where('id', $pid)->whereNull('deleted_at')->first();

            if (!$product || (isset($product->status) && (int) $product->status !== 1)) {
                return response()->json([
                    'success' => false,
                    'message' => "Product {$pid} is inactive or missing."
                ], 400);
            }

            // Check if product has ACTIVE variations (not soft-deleted AND status = 1)
            $hasVariations = DB::table('variations')
                ->where('product_id', $pid)
                ->whereNull('deleted_at')
                ->where('status', 1)
                ->exists();

            if ($hasVariations) {
                if (!$vid) {
                    return response()->json([
                        'success' => false,
                        'message' => "Product {$pid} requires a variation."
                    ], 400);
                }

                $var = Variation::where('id', $vid)
                    ->where('product_id', $pid)
                    ->whereNull('deleted_at')
                    ->first();

                if (!$var || (isset($var->status) && (int) $var->status !== 1)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Variation {$vid} for product {$pid} is inactive."
                    ], 400);
                }

                // Treat NULL quantity as unlimited unless explicitly tracking stock
                $track = (bool) data_get($var, 'track_stock', false);
                $available = is_null(data_get($var, 'quantity')) ? PHP_INT_MAX : (int) data_get($var, 'quantity');

                if ($track && $available < $qty) {
                    return response()->json([
                        'success' => false,
                        'message' => "Variation {$vid} for product {$pid} has insufficient stock."
                    ], 400);
                }
            } else {
                // Simple product
                $track = (bool) data_get($product, 'track_stock', false);
                $available = is_null(data_get($product, 'quantity')) ? PHP_INT_MAX : (int) data_get($product, 'quantity');

                if ($track && $available < $qty) {
                    return response()->json([
                        'success' => false,
                        'message' => "Product {$pid} has insufficient stock."
                    ], 400);
                }
            }
        }

        return false; // everything ok
    }
}
