<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\InventoryShipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateOrderItemToArrivedAtBranch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $pivotId;
    public $orderId;
    public $scannedBy;

    /**
     * Create a new job instance.
     *
     * @param int $pivotId
     * @param int $orderId
     * @param int|null $scannedBy User ID who scanned the item
     */
    public function __construct(int $pivotId, int $orderId, ?int $scannedBy = null)
    {
        $this->pivotId = $pivotId;
        $this->orderId = $orderId;
        $this->scannedBy = $scannedBy;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            DB::beginTransaction();

            // Update the order item status to 'arrived at local branch'
            $updated = DB::table('order_products')
                ->where('id', $this->pivotId)
                ->where('order_id', $this->orderId)
                ->whereNull('deleted_at')
                ->update([
                    'item_status' => 'arrived at local branch',
                    'updated_at' => now(),
                ]);

            if (!$updated) {
                Log::warning("Failed to update order item {$this->pivotId} for order {$this->orderId}");
                DB::rollBack();
                return;
            }

            // Update inventory shipment if exists
            $this->updateInventoryShipment();

            // Check if all items in the order have arrived at local branch
            $this->checkAndUpdateOrderStatus();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating order item status: " . $e->getMessage(), [
                'pivot_id' => $this->pivotId,
                'order_id' => $this->orderId,
                'exception' => $e,
            ]);

            // Re-throw the exception to trigger job failure
            throw $e;
        }
    }

    /**
     * Update inventory shipment status if it exists
     */
    protected function updateInventoryShipment(): void
    {
        try {
            $order = Order::find($this->orderId);
            if (!$order) {
                return;
            }

            // Find inventory shipment by order number
            $shipment = InventoryShipment::where('order', $order->order_number)->first();

            if ($shipment) {
                $shipment->update([
                    'status' => 'arrived at local branch',
                    'received_by' => $this->scannedBy,
                    'updated_by' => $this->scannedBy,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error updating inventory shipment: " . $e->getMessage());
            // Don't throw exception, just log it - shipment update is not critical
        }
    }

    /**
     * Check if all order items have arrived at local branch
     * If yes, update order status to 'ready for collection'
     */
    protected function checkAndUpdateOrderStatus(): void
    {
        try {
            // Get all order items (excluding soft deleted)
            $orderItems = DB::table('order_products')
                ->where('order_id', $this->orderId)
                ->whereNull('deleted_at')
                ->get();

            if ($orderItems->isEmpty()) {
                return;
            }

            // Check if all items have status 'arrived at local branch'
            $allArrived = $orderItems->every(function ($item) {
                return $item->item_status === 'arrived at local branch';
            });

            if ($allArrived) {
                // Find 'ready for collection' order status
                $readyForCollectionStatus = OrderStatus::where('slug', 'ready-for-collection')
                    ->orWhere('name', 'Ready for Collection')
                    ->first();

                if (!$readyForCollectionStatus) {
                    Log::warning("'Ready for Collection' order status not found. Creating it...");

                    // Create the status if it doesn't exist
                    $maxSequence = OrderStatus::max('sequence') ?? 0;
                    $readyForCollectionStatus = OrderStatus::create([
                        'name' => 'Ready for Collection',
                        'slug' => 'ready-for-collection',
                        'status' => 1,
                        'sequence' => $maxSequence + 1,
                        'system_reserve' => 0,
                    ]);
                }

                // Update order status - this will trigger the OrderObserver
                // but we need to prevent email notification
                $order = Order::find($this->orderId);
                if ($order) {
                    // Set a flag to prevent email notification
                    $order->skipNotification = true;

                    $order->update([
                        'order_status_id' => $readyForCollectionStatus->id,
                    ]);

                }
            }
        } catch (\Exception $e) {
            Log::error("Error checking/updating order status: " . $e->getMessage());
            // Don't throw exception - item status update is already done
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("UpdateOrderItemToArrivedAtBranch job failed", [
            'pivot_id' => $this->pivotId,
            'order_id' => $this->orderId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
