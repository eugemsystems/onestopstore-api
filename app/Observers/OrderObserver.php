<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\OrderCacheService;
use App\Jobs\SyncOrderToCrm;
use App\Http\Traits\CommissionTrait;
use App\Enums\OrderEnum;
use App\Enums\PaymentStatus;
use App\Models\AuctionBidDeposit;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Log;

/**
 * Order Observer
 * Automatically invalidates cache and syncs to CRM when orders are created, updated, or deleted
 */
class OrderObserver
{
    use CommissionTrait;

    protected $cacheService;

    /** Dedup guard: prevents logging the same order+fields combo twice per request */
    private static array $logged = [];

    public function __construct(OrderCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        $this->cacheService->invalidateOrderCaches($order->order_number);

        // Audit log — all authenticated users (admin, staff, and customers)
        try {
            ActivityLogger::make()->useLog('order')->event('created')->on($order)
                ->log("Order #{$order->order_number} created" . ($order->consumer ? " for {$order->consumer->name}" : ''));
        } catch (\Throwable) {}
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        $this->cacheService->invalidateOrderCaches($order->order_number);

        // Audit log changed fields — all authenticated users
        try {
            $dirty = $order->getDirty();
            if (!empty($dirty)) {
                $fields = array_keys($dirty);
                sort($fields);
                $dedupKey = $order->id . ':' . implode(',', $fields) . ':' . md5(serialize(array_values($dirty)));

                if (!in_array($dedupKey, self::$logged, true)) {
                    self::$logged[] = $dedupKey;
                    $old = array_intersect_key($order->getOriginal(), $dirty);
                    ActivityLogger::make()->useLog('order')->event('updated')->on($order)
                        ->withChanges($old, $dirty)
                        ->log("Order #{$order->order_number} updated: " . implode(', ', $fields));
                }
            }
        } catch (\Throwable) {}

        // Sync item statuses when order status changes to final states
        if ($order->wasChanged('order_status_id')) {
            $this->syncItemStatusesOnOrderStatusChange($order);
            $this->cleanupQRCodesOnFinalStatus($order);
            $this->generateQRCodeOnReadyForCollection($order);
        }

        // Calculate vendor commission when order is delivered/collected and paid
        if ($order->wasChanged('order_status_id') || $order->wasChanged('payment_status')) {

            if ($order->payment_status == PaymentStatus::COMPLETED && $order->order_status_id) {
                // Don't access $order->orderStatus relationship as it can cause transaction issues
                // Query the status directly instead
                $orderStatus = \App\Models\OrderStatus::where('id', $order->order_status_id)->first();
                $statusName = $orderStatus ? $orderStatus->name : null;

            }
        }

        // Auto-confirm auction deposit when its linked order is paid by any gateway
        if ($order->wasChanged('payment_status')) {
            $this->confirmAuctionDepositIfPaid($order);
        }

        // Sync order updates to CRM (especially payment status changes from gateways)
        // Only sync parent orders (not sub-orders)
        // Skip gift orders - they should not be synced to CRM
        if (is_null($order->parent_id) && !$order->is_gift_order) {
            SyncOrderToCrm::dispatch($order->id, 'updated')->afterCommit();
        }
    }

    /**
     * When any gateway marks the order as paid, set paid_at on the linked AuctionBidDeposit.
     * This fires for ALL gateways (PayFast ITN, Pesepay callback, DPO, etc.) because
     * they all call updateOrderPaymentStatus() which triggers this observer.
     */
    protected function confirmAuctionDepositIfPaid(Order $order): void
    {
        // Short-circuit for regular orders — only auction deposit orders carry this prefix.
        // Avoids a DB query on every single order payment update.
        if (!$order->note || !str_starts_with($order->note, 'AUC_DEPOSIT:')) {
            return;
        }

        $paidStatuses = [
            PaymentStatus::COMPLETED,
            'paid', 'complete', 'completed', 'success', 'approved', 'captured',
        ];

        $statusNow = strtolower((string) $order->payment_status);

        if (!in_array($statusNow, array_map('strtolower', $paidStatuses))) {
            return;
        }

        // Find any unpaid deposit linked to this order and mark it paid
        $deposit = AuctionBidDeposit::where('order_id', $order->id)
            ->whereNull('paid_at')
            ->first();

        if ($deposit) {
            $deposit->update(['paid_at' => now()]);
            Log::info('AuctionBidDeposit auto-confirmed via OrderObserver', [
                'deposit_id'       => $deposit->id,
                'order_id'         => $order->id,
                'order_number'     => $order->order_number,
                'payment_status'   => $order->payment_status,
            ]);
        }
    }

    /**
     * Sync item statuses when order status changes to final/specific states
     * Requirement #1: cancelled, delivered, collected, refunded
     */
    protected function syncItemStatusesOnOrderStatusChange(Order $order): void
    {
        $orderStatus = \App\Models\OrderStatus::where('id', $order->order_status_id)->first();
        if (!$orderStatus) {
            return;
        }

        $statusSlug = strtolower(trim($orderStatus->slug ?? $orderStatus->name ?? ''));

        // Define statuses that should trigger item status sync
        $syncTriggerStatuses = [
            'cancelled' => 'cancelled',
            'canceled' => 'cancelled',
            'delivered' => 'delivered',
            'collected' => 'collected', // collected items should be marked as collected (not delivered)
            'refunded' => 'cancelled', // refunded items should be cancelled
        ];

        if (isset($syncTriggerStatuses[$statusSlug])) {
            $newItemStatus = $syncTriggerStatuses[$statusSlug];

            // Update all order items to the new status
            \DB::table('order_products')
                ->where('order_id', $order->id)
                ->whereNull('deleted_at')
                ->update([
                    'item_status' => $newItemStatus,
                    'updated_at' => now(),
                ]);

        }
    }

    /**
     * Generate QR code image and save URL when order becomes "ready for collection"
     */
    protected function generateQRCodeOnReadyForCollection(Order $order): void
    {
        try {
            $orderStatus = \App\Models\OrderStatus::where('id', $order->order_status_id)->first();
            if (!$orderStatus) {
                return;
            }

            $statusSlug = strtolower(trim($orderStatus->slug ?? $orderStatus->name ?? ''));

            if ($statusSlug !== 'ready-for-collection' && $statusSlug !== 'ready for collection') {
                return;
            }

            // Build QR data - same format used in admin show blade and notifications
            $qrData = json_encode([
                'type' => 'order_collection',
                'order_number' => $order->order_number,
                'order_id' => $order->id,
                'auto_collect' => true,
                'customer_name' => $order->consumer->name ?? 'Customer',
                'timestamp' => now()->timestamp,
            ], JSON_UNESCAPED_SLASHES);

            // Generate QR code and upload to laravel-media
            $qrCodeService = app(\App\Services\OrderQRCodeService::class);
            $qrUrl = $qrCodeService->generateAndSave(
                (string) $order->order_number,
                $qrData,
                'collection'
            );

            if ($qrUrl) {
                // Save qr_code_url directly without triggering observer again
                \DB::table('orders')
                    ->where('id', $order->id)
                    ->update(['qr_code_url' => $qrUrl]);

                Log::info('QR code generated and saved for order', [
                    'order_number' => $order->order_number,
                    'qr_code_url' => $qrUrl,
                ]);
            } else {
                Log::warning('QR code generation failed, no URL returned', [
                    'order_number' => $order->order_number,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate QR code for ready for collection order', [
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clean up QR code files when order reaches final status
     * (collected, delivered, cancelled)
     */
    protected function cleanupQRCodesOnFinalStatus(Order $order): void
    {
        $orderStatus = \App\Models\OrderStatus::where('id', $order->order_status_id)->first();
        if (!$orderStatus) {
            return;
        }

        $statusSlug = strtolower(trim($orderStatus->slug ?? $orderStatus->name ?? ''));

        // Delete QR codes when order is completed
        $cleanupStatuses = ['collected', 'delivered', 'cancelled', 'canceled', 'refunded'];

        if (in_array($statusSlug, $cleanupStatuses)) {
            $qrCodeService = app(\App\Services\OrderQRCodeService::class);
            $qrCodeService->delete($order->order_number);

            // Clear qr_code_url without triggering observer again
            \DB::table('orders')
                ->where('id', $order->id)
                ->update(['qr_code_url' => null]);
        }
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        $this->cacheService->invalidateOrderCaches($order->order_number);

        // Audit log
        try {
            ActivityLogger::make()->useLog('order')->event('deleted')->on($order)
                ->log("Order #{$order->order_number} deleted");
        } catch (\Throwable) {}

        // Also cleanup QR codes when order is deleted
        $qrCodeService = app(\App\Services\OrderQRCodeService::class);
        $qrCodeService->delete($order->order_number);
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        $this->cacheService->invalidateOrderCaches($order->order_number);
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        $this->cacheService->invalidateOrderCaches($order->order_number);
    }

    /**
     * Returns true only when the current HTTP request is driven by an admin/staff web session.
     * Prevents customer self-service order placements from appearing in the admin audit trail.
     */
    private function isAdminAction(): bool
    {
        $user = auth('web')->user();
        if (!$user) return false;
        if (!method_exists($user, 'hasAnyRole')) return false;
        return $user->hasAnyRole(['admin', 'Staff Raines', 'vendor']);
    }
}
