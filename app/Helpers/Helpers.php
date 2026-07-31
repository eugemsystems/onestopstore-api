<?php
namespace App\Helpers;

use Carbon\Carbon;
use App\Models\Cart;
use App\Models\User;
use App\Models\Order;
use App\Models\Theme;
use App\Models\Store;
use App\Models\Coupon;
use App\Models\Review;
use App\Models\Product;
use App\Models\Setting;
use App\Enums\RoleEnum;
use App\Models\Currency;
use App\Enums\OrderEnum;
use App\Models\Category;
use App\Models\Variation;
use App\Enums\SortByEnum;
use App\Enums\StockStatus;
use App\Models\Attachment;
use App\Models\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Models\PaymentAccount;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Helpers
{

  // Get Current User Values
  public static function isUserLogin()
  {
    return Auth::guard('api')->check();
  }

  public static function getCurrentUserId()
  {
    if (self::isUserLogin()) {
      return Auth::guard('api')->user()?->id;
    }
  }

  public static function getCurrentRoleName()
  {
    if (self::isUserLogin()) {
      return Auth::guard('api')->user()?->tokens->first()->role_type;
    }
  }

  public static function getCurrentVendorStoreId()
  {
    if (self::isUserLogin()) {
      return Auth::guard('api')->user()?->store?->id;
    }
  }

  // Attachments
  public static function createAttachment()
  {
    $attachment = new Attachment();
    $attachment->save();
    return $attachment;
  }

  public static function addMedia($model, $media, $collectionName)
  {
    return $model->addMedia($media)->toMediaCollection($collectionName);
  }

  public static function storeImage($request, $model, $collectionName)
  {
    foreach ($request as $media) {
      $attachments[] = self::addMedia($model, $media, $collectionName);
    }
    $model->forcedelete($model->id);
    return $attachments;
  }

  public static function deleteImage($model)
  {
    return $model->delete($model->id);
  }

  // Get queary base data
  public static function getSettings()
  {
    return getCachedSettings()->pluck('values')->first();
  }

  public static function getAdmin()
  {
    return User::whereHas('roles', function($q) {
      $q->where('name',RoleEnum::ADMIN);
    })?->first();
  }

  public static function getAttachmentId($file_name)
  {
    return Attachment::where('file_name',$file_name)->pluck('id')->first();
  }

  public static function getRoleNameByUserId($user_id)
  {
    return User::find($user_id)?->role?->name;
  }

  public static function getCoupon($data)
  {
    return Coupon::where([['code', 'LIKE', '%'.$data.'%'],['status', true]])
	  ->orWhere('id', 'LIKE', '%'.$data.'%')
      ->with(['products', 'exclude_products'])
	  ->first();
  }

  public static function getDefaultCurrencySymbol()
  {
    $settings = self::getSettings();
    if (isset($settings['general']['default_currency'])) {
      $currency = $settings['general']['default_currency'];
      return $currency->symbol;
    }
  }

  public static function getActiveTheme()
  {
    return Theme::where('status',true)->pluck('slug');
  }

  public static function getStoreById($store_id)
  {
    return Store::where('id', $store_id)->first();
  }

  public static function getVendorIdByStoreId($store_id)
  {
    return self::getStoreById($store_id)?->vendor_id;
  }

  public static function getStoreIdByProductId($product_id)
  {
    return Product::where('id',$product_id)->pluck('store_id')->first();
  }

  public static function getProductByStoreSlug($store_slug)
  {
    return Product::whereHas('store', function (Builder $stores) use ($store_slug) {
      $stores->where('slug',$store_slug);
    });
  }

  public static function getRelatedProductId($model, $category_id, $product_id = null)
  {
    return $model->whereRelation('categories',
      function ($categories) use ($category_id) {
        $categories->Where('category_id',$category_id);
      }
    )->whereNot('id', $product_id)->inRandomOrder()->limit(6)->pluck('id')->toArray();
  }

  public static function getDefaultCurrencyCode()
  {
    $settings = Helpers::getSettings();
    $currency_id = $settings['general']['default_currency_id'];
    return Currency::whereId($currency_id)->pluck('code')->first();
  }

  public static function getCurrencyExchangeRate($currencyCode)
  {
    return Currency::where('code', $currencyCode)?->pluck('exchange_rate')?->first();
  }

  public static function convertToINR($amount)
  {
    $exchangeRate = self::getCurrencyExchangeRate('INR') ?? 1;
    $price = $amount * $exchangeRate;
    return self::roundNumber($price);
  }

    public static function convertToZAR($amount)
    {
        $exchangeRate = self::getCurrencyExchangeRate('ZAR') ?? 1;
        $price = $amount * $exchangeRate;
        return self::roundNumber($price);
    }

    public static function convertToZMK($amount)
    {
        $exchangeRate = self::getCurrencyExchangeRate('ZMW') ?? 1;
        $price = $amount * $exchangeRate;
        return self::roundNumber($price);
    }

    public static function convertToUSD($amount)
    {
        $exchangeRate = self::getCurrencyExchangeRate('USD') ?? 1;
        $price = $amount * $exchangeRate;
        return self::roundNumber($price);
    }

    /**
     * Detects native/mobile app clients (Flutter/Dart, okhttp, ReactNative, expo)
     * from the User-Agent header. Mirrors the regex used by LogMobileRequests
     * so both stay in sync.
     */
    public static function isMobileClient(?string $userAgent): bool
    {
        if (!$userAgent) {
            return false;
        }

        return (bool) preg_match('/okhttp|Dart\/|Flutter|ReactNative|CFNetwork.*Darwin|expo/i', $userAgent);
    }

    /**
     * Debug-only: dumps the exact payload sent back to a mobile client for a
     * given endpoint, so field types can be verified against what the app
     * actually receives instead of guessing. No-ops for non-mobile clients.
     * Logs to storage/logs/mobile_payload_debug.log — remove once the mobile
     * type-mismatch investigation is closed out.
     */
    public static function logMobilePayload(string $endpoint, ?string $userAgent, $payload): void
    {
        if (!self::isMobileClient($userAgent)) {
            return;
        }

        \Illuminate\Support\Facades\Log::channel('mobile_payload_debug')->info($endpoint, [
            'user_agent' => $userAgent,
            'payload' => $payload,
        ]);
    }

    /**
     * The shipped mobile app hard-casts several settings fields to types that
     * don't match what Laravel serializes (int where it expects String, int
     * where it expects bool). Rather than wait for an app update, reshape the
     * response for mobile clients only; web/React keep the original types.
     */
    public static function coerceSettingsForMobile(array $values): array
    {
        $toStringIfSet = function (&$arr, $key) {
            if (isset($arr[$key]) && $arr[$key] !== null) {
                $arr[$key] = (string) $arr[$key];
            }
        };

        // The published mobile app casts ONLY these three fields as `as String`.
        // Every other numeric/decimal field (exchange_rate, currency status,
        // min_order_free_shipping, signup_points, reward_per_order_amount,
        // shipping price) must keep its NATURAL type -- the app parses those as
        // num, and exchange_rate as an already-string decimal. Coercing them
        // here is exactly what was crashing checkout ("int/String/num" cast).
        if (isset($values['general'])) {
            $toStringIfSet($values['general'], 'min_order_amount');
        }
        if (isset($values['wallet_points'])) {
            $toStringIfSet($values['wallet_points'], 'min_per_order_amount');
            $toStringIfSet($values['wallet_points'], 'point_currency_ratio');
        }

        return $values;
    }

  public static function getConsumerOrderByProductId($consumer_id, $product_id)
  {
    return Order::where('consumer_id',$consumer_id)->whereHas('products', function ($products) use ($product_id) {
        $products->where('product_id',$product_id);
    });
  }

  public static function getStoreWiseLastThreeProductImages($store_id)
  {
    return Product::where('store_id',$store_id)->whereNull('deleted_at')
      ->latest()->limit(3)->with('product_thumbnail')->get()
      ->pluck('product_thumbnail.original_url')
      ->toArray();
  }

  public static function roundNumber($numb)
  {
    return number_format($numb, 2, '.', '');
  }

  public static function formatDecimal($value)
  {
    return floor($value * 100) / 100;
  }

  public static function removeCart(Order $order)
  {
    $productIds = [];
    $variationIds = [];
    $cartItems = Cart::where('consumer_id',$order->consumer_id)->get();

    if ($cartItems) {
      foreach ($order->products as $product) {
        $product = $product->pivot;
        if (isset($product->variation_id)) {
          $variationIds[] = $product->variation_id;
        }

        if (isset($product->product_id)) {
          $productIds[] = $product->product_id;
        }
      }

      $cart = Cart::where('consumer_id',self::getCurrentUserId())
        ->whereIn('product_id',$productIds);

      if (!empty($variationIds)) {
        $cart = Cart::where('consumer_id',self::getCurrentUserId())
          ->whereIn('variation_id',$variationIds);
      }

      $cart->delete();
    }
  }

  public static function getProductPrice($product_id)
  {
    return Product::where('id', $product_id)
      ->first(['price', 'discount', 'sale_price', 'is_sale_enable', 'sale_starts_at', 'sale_expired_at']);
  }

  public static function getVariationPrice($variation_id)
  {
    return Variation::where('id', $variation_id)
      ->first(['price', 'discount', 'sale_price']);
  }

  public static function getSalePrice($product)
  {
    $productPrices = self::getPrice($product);

    // Base price
    $base = (float) ($productPrices->price ?? 0);

    // If an explicit sale_price is present, prefer it when valid
    $hasSalePrice = isset($productPrices->sale_price) && $productPrices->sale_price !== null && (float)$productPrices->sale_price > 0;

    if ($hasSalePrice) {
      $salePrice = (float) $productPrices->sale_price;

      // Ensure sale price is actually a discount (less than regular price)
      if ($salePrice >= $base) {
        // Sale price is >= regular price, not a valid discount
        return $base;
      }

      // Check is_sale_enable flag (variations may not have this field, so default to true)
      $isEnabled = isset($productPrices->is_sale_enable) ? (bool) $productPrices->is_sale_enable : true;

      if (!$isEnabled) {
        // Sale is disabled
        return $base;
      }

      // Date validation logic
      $hasStartDate = isset($productPrices->sale_starts_at) && $productPrices->sale_starts_at !== null && trim($productPrices->sale_starts_at) !== '';
      $hasEndDate = isset($productPrices->sale_expired_at) && $productPrices->sale_expired_at !== null && trim($productPrices->sale_expired_at) !== '';

      // If BOTH dates are NULL/empty, sale is perpetual (always active)
      if (!$hasStartDate && !$hasEndDate) {
        return $salePrice;
      }

      // If dates are present, validate them
      try {
        $now = Carbon::now();

        // Check start date if it exists
        if ($hasStartDate) {
          $start = Carbon::parse($productPrices->sale_starts_at);
          if ($now->lt($start)) {
            // Sale hasn't started yet
            return $base;
          }
        }

        // Check end date if it exists
        if ($hasEndDate) {
          $end = Carbon::parse($productPrices->sale_expired_at);
          if ($now->gt($end)) {
            // Sale has expired
            return $base;
          }
        }

        // Sale is within valid date range (or no date restrictions)
        return $salePrice;

      } catch (\Throwable $e) {
        // If date parsing fails, log the error and use regular price for safety
        \Log::warning('Sale date parsing error for product', [
          'product_id' => $product['product_id'] ?? null,
          'variation_id' => $product['variation_id'] ?? null,
          'error' => $e->getMessage()
        ]);
        return $base;
      }
    }

    // Fallback to percentage discount if available
    // NOTE: Discount percentage should also respect sale dates and is_sale_enable flag
    $discount = (float) ($productPrices->discount ?? 0);
    if ($discount > 0) {
      // Check is_sale_enable flag (variations may not have this field, so default to true)
      $isEnabled = isset($productPrices->is_sale_enable) ? (bool) $productPrices->is_sale_enable : true;

      if (!$isEnabled) {
        // Sale is disabled, don't apply discount
        return $base;
      }

      // Date validation logic (same as for sale_price)
      $hasStartDate = isset($productPrices->sale_starts_at) &&
                      $productPrices->sale_starts_at !== null &&
                      trim($productPrices->sale_starts_at) !== '';

      $hasEndDate = isset($productPrices->sale_expired_at) &&
                    $productPrices->sale_expired_at !== null &&
                    trim($productPrices->sale_expired_at) !== '';

      // If BOTH dates are NULL, discount is perpetual (always active)
      if (!$hasStartDate && !$hasEndDate) {
        return $base - (($base * $discount) / 100);
      }

      // Validate dates if present
      try {
        $now = Carbon::now();

        // Check start date if it exists
        if ($hasStartDate) {
          $start = Carbon::parse($productPrices->sale_starts_at);
          if ($now->lt($start)) {
            // Sale/discount hasn't started yet
            return $base;
          }
        }

        // Check end date if it exists
        if ($hasEndDate) {
          $end = Carbon::parse($productPrices->sale_expired_at);
          if ($now->gt($end)) {
            // Sale/discount has expired
            return $base;
          }
        }

        // Discount is within valid date range
        return $base - (($base * $discount) / 100);

      } catch (\Throwable $e) {
        // If date parsing fails, use regular price for safety
        \Log::warning('Discount date parsing error', [
          'product_id' => $product['product_id'] ?? null,
          'error' => $e->getMessage()
        ]);
        return $base;
      }
    }

    // Otherwise, use the base price
    return $base;
  }

  public static function getSubTotal($price, $quantity)
  {
    return $price * $quantity;
  }

  public static function getTotalAmount($products)
  {
    $subtotal = [];
    foreach ($products as $product) {
      // ALWAYS validate price from the database — never trust a frontend-sent price.
      // This ensures that expired promo/sale prices are never charged if the client
      // has a stale cached price. getSalePrice() checks is_sale_enable and date ranges.
      $singleProductPrice = self::getSalePrice($product);
      $subtotal[] = self::getSubTotal($singleProductPrice, $product['quantity']);
    }

    return array_sum($subtotal);
  }

  public static function getPrice($product)
  {
    if (isset($product['variation_id'])) {
      return self::getVariationPrice($product['variation_id']);
    }

    return self::getProductPrice($product['product_id']);
  }

  public static function pointIsEnable()
  {
    $settings = self::getSettings();
    return $settings['activation']['point_enable'];
  }

  public static function walletIsEnable()
  {
    $settings = self::getSettings();
    return $settings['activation']['wallet_enable'];
  }

  public static function isMultiVendorEnable()
  {
    $settings = self::getSettings();
    return $settings['activation']['multivendor'];
  }

  public static function couponIsEnable()
  {
    $settings = self::getSettings();
    return $settings['activation']['coupon_enable'];
  }

  public static function getCategoryCommissionRate($categories)
  {
    return Category::whereIn('id', $categories)->pluck('commission_rate');
  }

  public static function getOrderStatusIdByName($name)
  {
    return OrderStatus::where('name',$name)->pluck('id')->first();
  }

  public static function getPaymentAccount($user_id)
  {
    return PaymentAccount::where('user_id',$user_id)->first();
  }

  public static function getTopSellingProducts($product)
  {
    $orders_count = $product->withCount(['orders'])->get()->sum('orders_count');
    $product = $product->orderByDesc('orders_count');
    if (!$orders_count) {
      $product = (new Product)->newQuery();
      $product->whereRaw('1 = 0');
      return $product;
    }

    return $product;
  }

  public static function getTopVendors($store)
  {
    $store = $store->orderByDesc('orders_count');
    $orders_count = $store->withCount(['orders'])->get()->sum('orders_count');
    if (!$orders_count) {
      $store = (new Store)->newQuery();
      $store->whereRaw('1 = 0');
      return $store;
    }

    return $store;
  }

  public static function getVariationStock($variation_id)
  {
    return Variation::where([['id', $variation_id],['stock_status', 'in_stock'],['quantity', '>', 0], ['status', true]])->first();
  }

  public static function getProductStock($product_id)
  {
    return Product::where([['id', $product_id],['stock_status', 'in_stock'], ['quantity', '>', 0], ['status', true]])->first();
  }

  public static function getCountUsedPerConsumer($consumer, $coupon)
  {
    return Order::where([['consumer_id', $consumer],['coupon_id', $coupon]])->count();
  }

  public static function getOrderByOrderNumber($order_number)
  {
    return Order::with(config('enums.order.with'))
        ->where('order_number', $order_number)
        ->first();
  }

  public static function decrementProductQuantity($product_id, $quantity)
  {
    $product = Product::findOrFail($product_id);
    $product->decrement('quantity', $quantity);
    $product = $product->fresh();
    if ($product->quantity <= 0) {
      $product->quantity = 0;
      self::updateProductStockStatus($product_id, StockStatus::OUT_OF_STOCK);
    }
  }

  public static function updateProductStockStatus($id, $stock_status)
  {
    return Product::where('id',$id)->update(['stock_status' => $stock_status]);
  }

  public static function incrementProductQuantity($product_id, $quantity)
  {
    $product = Product::findOrFail($product_id);
    if ($product->stock_status == StockStatus::OUT_OF_STOCK) {
      self::updateProductStockStatus($product_id, StockStatus::IN_STOCK);
    }
    $product->increment('quantity', $quantity);
  }

  public static function updateVariationStockStatus($id, $stock_status)
  {
    return Variation::findOrFail($id)->update(['stock_status' => $stock_status]);
  }

  public static function decrementVariationQuantity($variation_id, $quantity)
  {
    $variation = Variation::findOrFail($variation_id);
    $variation->decrement('quantity', $quantity);
    $variation = $variation->fresh();
    if ($variation->quantity <= 0) {
      $variation->quantity = 0;
      self::updateVariationStockStatus($variation_id, StockStatus::OUT_OF_STOCK);
    }
  }

  public static function incrementVariationQuantity($variation_id, $quantity)
  {
    $variation = Variation::findOrFail($variation_id);
    if ($variation->stock_status == StockStatus::OUT_OF_STOCK) {
      self::updateVariationStockStatus($variation_id, StockStatus::IN_STOCK);
    }
    $variation->increment('quantity', $quantity);
  }

  public static function isAlreadyReviewed($consumer_id, $product_id)
  {
    return Review::where([
      ['consumer_id', $consumer_id],
      ['product_id', $product_id]
    ])->first();
  }

  public static function countOrderAmount($product_id, $filter_by)
  {
    return self::getCompletedOrderByProductId($product_id, $filter_by)->sum('total');
  }

  public static function getStoreOrderCount($store_id, $filter_by)
  {
    return self::getCompleteOrderByStoreId($store_id, $filter_by)?->get()->count();
  }

  public static function countStoreOrderAmount($store_id, $filter_by)
  {
    return self::getCompleteOrderByStoreId($store_id, $filter_by)?->sum('total');
  }

  public static function getProductCountByStoreId($store_id, $filter_by)
  {
    return self::getProductByStoreId($store_id, $filter_by)?->count();
  }

  public static function getProductByStoreId($store_id, $filter_by)
  {
    $product = Product::where('store_id', $store_id)->whereNull('deleted_at');
    return self::getFilterBy($product, $filter_by);
  }

  public static function getCompleteOrderByStoreId($store_id, $filter_by)
  {
    $order = Order::where('store_id',$store_id)->where('payment_status',PaymentStatus::COMPLETED);
    return self::getFilterBy($order, $filter_by);
  }

  public static function getFilterBy($model, $filter_by)
  {
    switch($filter_by) {
      case SortByEnum::TODAY:
        $model = $model->where('created_at', Carbon::now());
        break;

      case SortByEnum::LAST_WEEK:
        $startWeek = Carbon::now()->subWeek()->startOfWeek();
        $endWeek = Carbon::now()->subWeek()->endOfWeek();
        $model = $model->whereBetween('created_at', [$startWeek, $endWeek]);
        break;

      case SortByEnum::LAST_MONTH:
        $model = $model->whereMonth('created_at', Carbon::now()->subMonth()->month);
        break;

      case SortByEnum::THIS_YEAR:
        $model = $model->whereYear('created_at', Carbon::now()->year);
        break;
    }

    return $model;
  }

  public static function getCompletedOrderByProductId($product_id, $filter_by)
  {
    $order = Order::whereHas('products', function ($query) use($product_id) {
      $query->where('product_id',$product_id);
    })->whereNull('deleted_at')->where('payment_status',PaymentStatus::COMPLETED);

    return self::getFilterBy($order, $filter_by);
  }

  public static function getOrderCount($product_id, $filter_by)
  {
    return self::getCompletedOrderByProductId($product_id, $filter_by)?->count();
  }

  public static function isOrderCompleted($order)
  {
    if ($order->payment_status == PaymentStatus::COMPLETED &&
      $order->order_status->name == OrderEnum::DELIVERED) {
      return true;
    }

   return false;
  }

  public static function user_review($consumer_id, $product_id)
  {
    return Review::where('consumer_id',$consumer_id)
      ->where('product_id',$product_id)->whereNull('deleted_at')->first();
  }

  public static function canReview($consumer_id, $product_id)
  {
    $orders = self::getConsumerOrderByProductId($consumer_id, $product_id);
    foreach($orders as $order) {
      if (isset($order->sub_orders)) {
        if (!$order->sub_orders->isEmpty()) {
          $tempOrder = null;
          foreach($order->sub_orders as $sub_order) {
            foreach($sub_order->products as $product) {
              if ($product->id == $product_id) {
                $tempOrder = $sub_order;
              }
            }
          }

          $order = $tempOrder;
        }
      }

      if ($order) {
        if (self::isOrderCompleted($order)) {
          return true;
        }
      }
    }

    return false;
  }

  public static function getReviewRatings($product_id)
  {
    $row = DB::table('reviews')
      ->where('product_id', $product_id)
      ->whereNull('deleted_at')
      ->selectRaw("
        SUM(CASE WHEN ROUND(rating::numeric) = 1 THEN 1 ELSE 0 END) AS star1,
        SUM(CASE WHEN ROUND(rating::numeric) = 2 THEN 1 ELSE 0 END) AS star2,
        SUM(CASE WHEN ROUND(rating::numeric) = 3 THEN 1 ELSE 0 END) AS star3,
        SUM(CASE WHEN ROUND(rating::numeric) = 4 THEN 1 ELSE 0 END) AS star4,
        SUM(CASE WHEN ROUND(rating::numeric) = 5 THEN 1 ELSE 0 END) AS star5
      ")
      ->first();
    return [
      (int)($row->star1 ?? 0),
      (int)($row->star2 ?? 0),
      (int)($row->star3 ?? 0),
      (int)($row->star4 ?? 0),
      (int)($row->star5 ?? 0),
    ];
  }

  public static function updateProductStock(Order $order)
  {
    if ($order->payment_status == PaymentStatus::COMPLETED ||
      $order->payment_method == PaymentMethod::COD ||
      $order->payment_method == PaymentMethod::BANK_TRANSFER) {
      foreach ($order->products as $product) {
        $product = $product->pivot;
        if (isset($product->variation_id)) {
          self::decrementVariationQuantity($product->variation_id, $product->quantity);
        } else {
          self::decrementProductQuantity($product->product_id, $product->quantity);
        }
      }
    }
  }



}
