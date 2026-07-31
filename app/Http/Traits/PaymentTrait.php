<?php
namespace App\Http\Traits;

use App\Models\Order;
use App\Helpers\Helpers;
use App\Models\OrderTransaction;
use App\Enums\OrderEnum;
use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait PaymentTrait {

  use UtilityTrait;

  public static function updateOrderPaymentStatus(Order $order, $status)
  {
    $order->update([
      'payment_status' => $status
    ]);

    // Refresh the order so we have the latest data AND the order_status relation loaded.
    // fresh() without arguments only re-fetches the row — relations remain null.
    // The guard below depends on order_status->name, so we must load the relation here.
    $order = $order->fresh(['order_status']);

    // When payment completes, advance order status to PROCESSING (parent and suborders)
    if ($status === PaymentStatus::COMPLETED) {
      try {
        // Guard: do not rewind order_status if already past processing
        // (cancelled, shipped, out for delivery, collected, delivered)
        $currentOrderName = strtolower(trim((string) ($order->order_status->name ?? '')));
        $noRewindStatuses = [
          strtolower(trim(OrderEnum::CANCELLED)),
          strtolower(trim(OrderEnum::SHIPPED)),
          strtolower(trim(OrderEnum::OUT_FOR_DELIVERY)),
          strtolower(trim(OrderEnum::COLLECTED)),
          strtolower(trim(OrderEnum::DELIVERED)),
        ];
        if (in_array($currentOrderName, $noRewindStatuses)) {
          Log::info('PaymentTrait: skipping status rewind to processing — order already in status: ' . $currentOrderName, ['order_id' => $order->id]);
        } else {
          $processingId = Helpers::getOrderStatusIdByName(OrderEnum::PROCESSING);
          if ($processingId) {
            // Update main order status
            DB::table('orders')
              ->where('id', $order->id)
              ->update(['order_status_id' => $processingId, 'updated_at' => now()]);

            // Only update items that are currently 'pending' (not manually changed items)
            DB::table('order_products')
              ->where('order_id', $order->id)
              ->whereNull('deleted_at')
              ->where('item_status', 'pending')  // Only pending items
              ->update(['item_status' => 'processing', 'updated_at' => now()]);

            // Update sub-orders and their items
            $subOrderIds = DB::table('orders')
              ->where('parent_id', $order->id)
              ->pluck('id');

            if ($subOrderIds->isNotEmpty()) {
              DB::table('orders')
                ->whereIn('id', $subOrderIds)
                ->update(['order_status_id' => $processingId, 'updated_at' => now()]);

              // Only update items that are currently 'pending'
              DB::table('order_products')
                ->whereIn('order_id', $subOrderIds)
                ->whereNull('deleted_at')
                ->where('item_status', 'pending')  // Only pending items
                ->update(['item_status' => 'processing', 'updated_at' => now()]);
            }

          }
        }
      } catch (\Throwable $e) {
        Log::error('Failed to update order status on payment completion', [
          'order_id' => $order->id,
          'error' => $e->getMessage()
        ]);
      }
    }

    Order::where('parent_id', $order->id)->update(['payment_status' => $status]);
    $order = $order->fresh();
    // Ensure related data (including products) are loaded after refresh
    try {
      $order->load(config('enums.order.with'));
      // Also ensure nested products on sub-orders are available
      $order->loadMissing(['products', 'sub_orders.products']);
    } catch (\Throwable $e) { /* ignore */ }

    Helpers::updateProductStock($order);

    // Points allocation/revocation on payment status changes (guard against missing methods)
    try {
      if (\App\Helpers\Helpers::pointIsEnable()) {
        $repo = app(\App\Repositories\Eloquents\OrderRepository::class);
        if ($order->payment_status === \App\Enums\PaymentStatus::COMPLETED && method_exists($repo, 'awardPointsOnce')) {
          $repo->awardPointsOnce($order);
        }
        if ($order->payment_status === \App\Enums\PaymentStatus::CANCELLED && method_exists($repo, 'revokeAwardedPoints')) {
          $repo->revokeAwardedPoints($order);
        }
      }
    } catch (\Throwable $e) {
      report($e);
    }

    // Send auction payment confirmation email if this is an AUC_WIN order
    try {
      if ($order->payment_status === \App\Enums\PaymentStatus::COMPLETED
          && $order->note
          && str_starts_with((string) $order->note, 'AUC_WIN:')) {

        // Parse auction lot ID from note: "AUC_WIN: ... (Lot #9)"
        if (preg_match('/\(Lot #(\d+)\)/', (string) $order->note, $m)) {
          $auctionItem = \App\Models\AuctionItem::find((int) $m[1]);
          if ($auctionItem && $order->consumer) {
            $order->consumer->notify(
              new \App\Notifications\AuctionPaymentConfirmedNotification($auctionItem->fresh(), $order)
            );
            Log::info('AuctionPaymentConfirmedNotification sent via gateway', [
              'order_id'        => $order->id,
              'auction_item_id' => $auctionItem->id,
              'user_id'         => $order->consumer_id,
            ]);
          }
        }
      }
    } catch (\Throwable $e) {
      Log::error('Failed to send AuctionPaymentConfirmedNotification', [
        'order_id' => $order->id,
        'error'    => $e->getMessage(),
      ]);
    }

    return $order;
  }

  public static function updateOrderPaymentMethod(Order $order, $method)
  {
    $order->update([
      'payment_method' => $method
    ]);

    $order = $order->fresh();
    return $order;
  }

  public static function storeOrderTransaction(Order $order, $transaction_id,$payment_method) : void
  {
    $order = self::updateOrderPaymentMethod($order, $payment_method);
    $order->order_transactions()->updateOrCreate(['order_id' => $order->id],
      ['transaction_id' => $transaction_id]
    );
  }

  public static function verifyOrderTransaction($order_id, $transaction_id)
  {
    return OrderTransaction::where([['order_id', $order_id],['transaction_id', $transaction_id]])->first();
  }

  /**
   * Sync order_products item_status when order status changes.
   * Only updates items that still have the item_status matching the previous order status.
   */
  protected static function syncOrderProductsItemStatus($orderId, $previousOrderStatusId, $newOrderStatusId, $previousStatusSlug, $newStatusSlug)
  {
    try {
      // Map order status slugs to item_status values
      $previousItemStatus = self::mapOrderStatusToItemStatus($previousStatusSlug);
      $newItemStatus = self::mapOrderStatusToItemStatus($newStatusSlug);


      if ($previousItemStatus !== $newItemStatus) {
        // Only update order_products that have item_status matching the previous order status
        $rows = DB::table('order_products')
          ->where('order_id', $orderId)
          ->where('item_status', $previousItemStatus)
          ->update(['item_status' => $newItemStatus, 'updated_at' => now()]);

      }
    } catch (\Throwable $e) {
      Log::warning('Failed to sync order_products item_status', [
        'order_id' => $orderId,
        'error' => $e->getMessage()
      ]);
    }
  }

  /**
   * Map order status slug to item_status value.
   * This should match the mapping in OrderRepository.
   */
  protected static function mapOrderStatusToItemStatus(?string $orderStatusSlug)
  {
    $s = strtolower(trim((string) ($orderStatusSlug ?? '')));
    $map = [
      'pending' => 'pending',
      'processing' => 'processing',
      'warehouse packing' => 'processing',
      'warehouse-packing' => 'processing',
      'from supplier' => 'processing',
      'from-supplier' => 'processing',
      'stuck' => 'processing',
      'shipped' => 'shipped',
      'in transit to zim' => 'shipped',
      'in-transit-to-zim' => 'shipped',
      'dropped at the deport' => 'shipped',
      'dropped-at-the-deport' => 'shipped',
      'out for delivery' => 'shipped',
      'out-for-delivery' => 'shipped',
      'ready for collection' => 'shipped',
      'ready-for-collection' => 'shipped',
      'arrived at local branch' => 'shipped',
      'arrived-at-local-branch' => 'shipped',
      'delivered' => 'delivered',
      'collected' => 'collected', // Keep collected as collected
      'cancelled' => 'cancelled',
      'canceled' => 'cancelled',
      'refunded' => 'cancelled',
    ];

    return $map[$s] ?? 'pending';
  }
}
