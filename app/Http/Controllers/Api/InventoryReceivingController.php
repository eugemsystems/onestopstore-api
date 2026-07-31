<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncOrderItemStatusToCrm;
use App\Jobs\SyncOrderToCrm;
use App\Models\InventoryReceivingTemp;
use App\Models\InventoryShipment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InventoryReceivingController extends Controller
{
    /**
     * Scan QR code and add to user's receiving list
     *
     * POST /api/inventory-receiving/scan
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function scan(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'qr_data' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request',
                'errors' => $validator->errors(),
            ], 422);
        }

        $qrData = $request->input('qr_data');

        // Get the bearer token
        $bearerToken = $request->bearerToken();

        // Use Sanctum guard for API authentication
        $user = Auth::guard('sanctum')->user();

        // Check if token exists in database
        if ($bearerToken) {
            $tokenHash = hash('sha256', $bearerToken);
            $tokenRecord = DB::table('personal_access_tokens')
                ->where('token', $tokenHash)
                ->first();
        }

        if (!$user) {

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please provide a valid API token.',
                'debug' => [
                    'token_length' => $bearerToken ? strlen($bearerToken) : 0,
                    'expected_length' => '40+ characters',
                ],
            ], 401);
        }

        // DEBUG: Log QR data received
        try {
            // Parse QR code data (JSON)
            $parsedData = json_decode($qrData, true);

            if (!$parsedData || !isset($parsedData['type']) || $parsedData['type'] !== 'inventory_shipment') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code format. Expected inventory shipment QR code.',
                ], 400);
            }

            // Extract data
            $shipmentId = $parsedData['shipment_id'] ?? null;
            $orderNumber = $parsedData['order_number'] ?? null;
            $productName = $parsedData['product_name'] ?? null;
            $quantity = $parsedData['quantity'] ?? 1;
            $destination = $parsedData['destination'] ?? null;

            if (!$shipmentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code: Missing shipment ID',
                ], 400);
            }

            // Check if shipment exists
            $shipment = InventoryShipment::find($shipmentId);

            if (!$shipment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shipment not found',
                ], 404);
            }

            // Check if already in user's list
            $existing = InventoryReceivingTemp::where('user_id', $user->id)
                ->where('shipment_id', $shipmentId)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'already_in_list' => true,
                    'message' => 'This item is already in your receiving list',
                    'item' => [
                        'id' => $existing->id,
                        'shipment_id' => $existing->shipment_id,
                        'order_number' => $existing->order_number,
                        'product_name' => $existing->product_name,
                        'quantity' => $existing->quantity,
                        'destination' => $existing->destination,
                        'scanned_at' => $existing->scanned_at->toISOString(),
                    ],
                ], 200);
            }

            // Add to receiving list
            $item = InventoryReceivingTemp::create([
                'user_id' => $user->id,
                'shipment_id' => $shipmentId,
                'order_number' => $orderNumber,
                'product_name' => $productName,
                'quantity' => $quantity,
                'destination' => $destination,
                'qr_data' => $parsedData,
                'scanned_at' => now(),
            ]);

            // Get total items in list
            $totalItems = InventoryReceivingTemp::where('user_id', $user->id)->count();

            return response()->json([
                'success' => true,
                'message' => 'Item added to receiving list',
                'item' => [
                    'id' => $item->id,
                    'shipment_id' => $item->shipment_id,
                    'order_number' => $item->order_number,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'destination' => $item->destination,
                    'scanned_at' => $item->scanned_at->toISOString(),
                ],
                'total_items' => $totalItems,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process QR code: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's current receiving list
     *
     * GET /api/inventory-receiving/list
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getList()
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $items = InventoryReceivingTemp::where('user_id', $user->id)
            ->with('shipment')
            ->orderBy('scanned_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'shipment_id' => $item->shipment_id,
                    'order_number' => $item->order_number,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'destination' => $item->destination,
                    'scanned_at' => $item->scanned_at->toISOString(),
                    'shipment_status' => $item->shipment->status ?? null,
                ];
            });

        return response()->json([
            'success' => true,
            'items' => $items,
            'total_items' => $items->count(),
        ]);
    }

    /**
     * Remove item from receiving list
     *
     * DELETE /api/inventory-receiving/{id}
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeItem($id)
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $item = InventoryReceivingTemp::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found in your receiving list',
            ], 404);
        }

        $item->delete();

        $totalItems = InventoryReceivingTemp::where('user_id', $user->id)->count();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from receiving list',
            'total_items' => $totalItems,
        ]);
    }

    /**
     * Clear all items from receiving list
     *
     * POST /api/inventory-receiving/clear
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearList()
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $deleted = InventoryReceivingTemp::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => "Cleared {$deleted} items from receiving list",
            'total_items' => 0,
        ]);
    }

    /**
     * Save and process all items in receiving list
     *
     * POST /api/inventory-receiving/save
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function save()
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        DB::beginTransaction();

        try {
            $items = InventoryReceivingTemp::where('user_id', $user->id)
                ->with('shipment')
                ->get();

            if ($items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No items in receiving list',
                ], 400);
            }

            $processedCount = 0;
            $ordersToCheck = [];

            foreach ($items as $item) {
                $shipment = $item->shipment;

                if (!$shipment) {
                    Log::warning("API: Shipment not found", ['shipment_id' => $item->shipment_id]);
                    continue;
                }

                // Update inventory shipment
                $shipment->update([
                    'f_status' => 'Received',
                    'status' => 'Received',
                    'received_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                // Find and update corresponding order item
                if ($shipment->order) {
                    $order = Order::where('order_number', $shipment->order)
                        ->with(['products' => function($query) {
                            $query->withPivot(['id', 'product_id', 'variation_id', 'item_status', 'cancellation_reason', 'eta']);
                        }])
                        ->first();

                    if ($order) {
                        // Match product by SKU or NAME
                        $matchedProduct = null;
                        $shipmentTitle = strtolower(trim($shipment->title));

                        // First: Try SKU match
                        foreach ($order->products as $product) {
                            if (!empty($product->sku) && stripos($shipmentTitle, strtolower($product->sku)) !== false) {
                                $matchedProduct = $product;

                                break;
                            }
                        }

                        // Second: Try NAME match
                        if (!$matchedProduct) {
                            foreach ($order->products as $product) {
                                $productName = strtolower(trim($product->name));
                                if (stripos($productName, $shipmentTitle) !== false ||
                                    stripos($shipmentTitle, $productName) !== false) {
                                    $matchedProduct = $product;

                                    break;
                                }
                            }
                        }

                        if ($matchedProduct) {
                            // Update order item
                            $updated = DB::table('order_products')
                                ->where('id', $matchedProduct->pivot->id)
                                ->where('order_id', $order->id)
                                ->whereNull('deleted_at')
                                ->update([
                                    'item_status' => 'arrived at local branch',
                                    'updated_at' => now(),
                                ]);

                            if ($updated > 0) {

                                // Sync to CRM
                                SyncOrderItemStatusToCrm::dispatch(
                                    $order->id,
                                    $matchedProduct->id,
                                    $matchedProduct->pivot->variation_id,
                                    'arrived at local branch',
                                    $matchedProduct->pivot->cancellation_reason,
                                    $matchedProduct->pivot->eta
                                )->afterCommit();


                            }
                        }

                        if (!in_array($order->id, $ordersToCheck)) {
                            $ordersToCheck[] = $order->id;
                        }
                    }
                }

                $processedCount++;
            }

            // Check and update order statuses
            foreach ($ordersToCheck as $orderId) {
                $this->checkAndUpdateOrderStatus($orderId);
            }

            // Clear the receiving list
            InventoryReceivingTemp::where('user_id', $user->id)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully received {$processedCount} items",
                'items_processed' => $processedCount,
                'orders_checked' => count($ordersToCheck),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to save receiving: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if all order items are "arrived at local branch" and update order status
     */
    private function checkAndUpdateOrderStatus($orderId)
    {
        $order = Order::with(['consumer', 'order_status'])->find($orderId);

        if (!$order) {
            return;
        }

        // Get all order items
        $orderItems = DB::table('order_products')
            ->where('order_id', $orderId)
            ->whereNull('deleted_at')
            ->get();

        // Check if all items have status "arrived at local branch"
        $allArrived = $orderItems->every(function ($item) {
            return $item->item_status === 'arrived at local branch';
        });


        if ($allArrived) {
            // Find 'ready for collection' order status
            $readyForCollectionStatus = \App\Models\OrderStatus::where('slug', 'ready-for-collection')
                ->orWhere('name', 'Ready for Collection')
                ->first();

            if (!$readyForCollectionStatus) {
                $maxSequence = \App\Models\OrderStatus::max('sequence') ?? 0;
                $readyForCollectionStatus = \App\Models\OrderStatus::create([
                    'name' => 'Ready for Collection',
                    'slug' => 'ready-for-collection',
                    'status' => 1,
                    'sequence' => $maxSequence + 1,
                    'system_reserve' => 0,
                ]);
            }

            $oldStatusId = $order->order_status_id;
            $oldStatusName = $order->order_status->name ?? 'Unknown';

            // Update order status
            $order->update([
                'order_status_id' => $readyForCollectionStatus->id,
            ]);

            // Sync to CRM - dispatch order sync job
            if (!$order->parent_id && !$order->is_gift_order) {
                SyncOrderToCrm::dispatch($order->id, 'updated')->afterCommit();

            }

            // Send ready for collection notification
            $this->sendReadyForCollectionNotification($order);
        }
    }

    /**
     * Send ready for collection notification to customer
     */
    private function sendReadyForCollectionNotification($order)
    {
        try {
            // Get CRM base URL
            $crmBaseUrl = rtrim(config('services.crm.base_url'), '/');

            if (!$crmBaseUrl) {
                return;
            }

            // Send notification request to CRM
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->retry(2, 100)
                ->post($crmBaseUrl . '/api/webhooks/order/ready-for-collection', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]);

        } catch (\Exception $e) {
            Log::error("API: Error sending ready for collection notification", [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
