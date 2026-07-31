<?php
namespace App\Http\Traits;

use Exception;
use App\Models\Order;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use App\Enums\OrderEnum;
use App\Enums\PaymentStatus;
use App\Enums\WalletPointsDetail;
use App\Models\CommissionHistory;
use App\GraphQL\Exceptions\ExceptionHandler;

trait CommissionTrait {

  use WalletPointsTrait;

  public function isExistsCommissionHistory(Order $order)
  {
    return CommissionHistory::where('order_id', $order->id)->exists();
  }

  public function getMonthlyVendorCommissions($monthlyCommssions)
  {
    return $monthlyCommssions->where('store_id', Helpers::getCurrentVendorStoreId())->pluck('vendor_commission')->toArray();
  }

  public function getMonthlyAdminCommissions($monthlyCommssions)
  {
    return $monthlyCommssions->pluck('admin_commission')->toArray();
  }

  public function getMonthlyCommissions($year, $roleName)
  {
    $months = range(1, 12);
    foreach($months as $month) {
      $perMonthCommissions = [];
      $commissionHistory = CommissionHistory::whereMonth('created_at', $month)->whereYear('created_at', $year)->whereNull('deleted_at');
      if ($roleName == RoleEnum::VENDOR) {
        $perMonthCommissions = $this->getMonthlyVendorCommissions($commissionHistory);
      } else {
        $perMonthCommissions = $this->getMonthlyAdminCommissions($commissionHistory);
      }

      $commissions[] = array_sum($perMonthCommissions);
    }

    return $commissions;
  }

  public function adminVendorCommission(Order $order)
  {
    try {
      $settings = Helpers::getSettings();

      if (!$settings['vendor_commissions']['status']) {
        return;
      }

      if (!$settings['activation']['multivendor']) {
        return;
      }

      // Get order status name without accessing relationship (to avoid transaction issues)
      $orderStatus = \App\Models\OrderStatus::where('id', $order->order_status_id)->first();
      $statusName = $orderStatus ? $orderStatus->name : null;

      if ($order->payment_status != PaymentStatus::COMPLETED && $order->payment_status != PaymentStatus::COMPLETE) {
        return;
      }

      if (!$statusName) {
        return;
      }


      // Check if order is DELIVERED or COLLECTED
      if ($statusName != OrderEnum::DELIVERED && $statusName != OrderEnum::COLLECTED) {
        return;
      }

      // Check if commission already exists for this order
      if ($this->isExistsCommissionHistory($order)) {
        return; // Commission already calculated
      }

      // Group products by store (vendor)
      $productsByStore = [];
      $productsCount = $order->products->count();

      foreach ($order->products as $product) {

        if ($product->store_id) {
          $storeId = $product->store_id;
          if (!isset($productsByStore[$storeId])) {
            $productsByStore[$storeId] = [];
          }
          $productsByStore[$storeId][] = $product;
        } else {
        }
      }

      $vendorCount = count($productsByStore);

      if ($vendorCount === 0) {
        return;
      }

      // Calculate commission for each vendor
      foreach ($productsByStore as $storeId => $products) {
        $commissions = ['admin' => [], 'vendor' => []];
        $productDetails = []; // Store per-product commission details

        foreach ($products as $product) {
          $subTotal = $product->pivot->subtotal;
          $quantity = $product->pivot->quantity ?? 1;
          $productPrice = $product->pivot->single_price ?? ($subTotal / $quantity);

          // Get commission rate and determine source
          $commissionRate = 0;
          $commissionSource = 'default';
          $categoryId = null;
          $categoryName = null;

          if ($settings['vendor_commissions']['is_category_based_commission']) {
            // Get all categories with their commission rates
            // Use selectRaw to avoid ambiguous column issues and ensure proper mapping
            $categoriesWithRates = $product->categories()
              ->selectRaw('categories.id as id, categories.name as name, categories.commission_rate as commission_rate')
              ->whereNotNull('categories.commission_rate')
              ->where('categories.commission_rate', '>', 0)
              ->get();

            if ($categoriesWithRates->isNotEmpty()) {

              // Find the category with the highest commission rate
              $maxCategory = $categoriesWithRates->sortByDesc('commission_rate')->first();
              $commissionRate = (float) $maxCategory->commission_rate;
              $commissionSource = 'category';
              $categoryId = $maxCategory->id;
              $categoryName = $maxCategory->name;
            }
          }

          // Fallback to default rate if no category rate found
          if (!$commissionRate) {
            $commissionRate = (float) $settings['vendor_commissions']['default_commission_rate'];
            $commissionSource = 'default';
          }

          // Calculate commissions for this product
          $adminCommission = $this->getAdminCommission($subTotal, $commissionRate);
          $vendorCommission = $this->getVendorCommission($subTotal, $commissionRate);


          $commissions['admin'][] = $adminCommission;
          $commissions['vendor'][] = $vendorCommission;

          // Store detailed product information
          $productDetails[] = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku ?? null,
            'product_price' => $productPrice,
            'quantity' => $quantity,
            'subtotal' => $subTotal,
            'commission_rate' => $commissionRate,
            'commission_source' => $commissionSource,
            'category_id' => $categoryId,
            'category_name' => $categoryName,
            'admin_commission' => $adminCommission,
            'vendor_commission' => $vendorCommission,
          ];
        }

        $totalAdminCommission = array_sum($commissions['admin']);
        $totalVendorCommission = array_sum($commissions['vendor']);


        // Get store and credit vendor wallet
        $store = Helpers::getStoreById($storeId);
        if (!$store) {
          continue;
        }

        if (!$store->vendor) {
          continue;
        }

        // Credit vendor wallet
        $this->creditVendorWallet($store->vendor->id, $totalVendorCommission, WalletPointsDetail::COMMISSION);
        // Create commission history with per-product details
        $this->createCommissionHistory($order, $store->id, $commissions, $productDetails);
      }

    } catch (Exception $e) {
      throw new ExceptionHandler($e->getMessage(), $e->getCode());
    }
  }

  public function getVendorCommission($subTotal, $commissionRate)
  {
    return ($subTotal - $this->getAdminCommission($subTotal, $commissionRate));
  }

  public function getAdminCommission($subTotal, $commissionRate)
  {
    return (($subTotal * $commissionRate )/100);
  }

  public function createCommissionHistory($sub_order, $store_id, $commissions, $productDetails = [])
  {
    // Create the main commission history record
    $commissionHistory = $sub_order->commission_history()->create([
      'admin_commission' => array_sum($commissions['admin']),
      'vendor_commission' =>  array_sum($commissions['vendor']),
      'store_id' => $store_id,
    ]);

    // Create per-product commission details
    if (!empty($productDetails) && $commissionHistory) {
      foreach ($productDetails as $detail) {
        $item = $commissionHistory->items()->create($detail);
      }
    } else {
      \Log::warning("No product details to create - productDetails is empty or commission history failed");
    }

    return $sub_order;
  }
}
