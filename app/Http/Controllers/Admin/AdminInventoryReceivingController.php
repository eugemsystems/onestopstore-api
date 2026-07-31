<?php

namespace App\Http\Controllers\Admin;

use App\Jobs\SyncOrderItemStatusToCrm;
use App\Jobs\SyncOrderToCrm;
use App\Models\InventoryReceivingLog;
use App\Models\InventoryReceivingTemp;
use App\Models\InventoryShipment;
use App\Models\Order;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminInventoryReceivingController extends BaseAdminController
{
    protected string $permissionPrefix = 'inventory-shipment';

    /**
     * Show the inventory receiving scanner page
     */
    public function index()
    {
        $this->checkPermission('edit');

        // Get current scanned items with user information - load ALL items for branch filtering
        $items = InventoryReceivingTemp::with(['shipment', 'user'])
            ->orderBy('scanned_at', 'desc')
            ->get();

        // Format for view compatibility with user and branch info
        $scannedItems = [];
        foreach ($items as $item) {
            $scannedItems[$item->shipment_id] = [
                'id' => $item->id,
                'shipment_id' => $item->shipment_id,
                'order_number' => $item->order_number,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'destination' => $item->destination,
                'scanned_at' => $item->scanned_at->format('Y-m-d H:i:s'),
                'user_id' => $item->user_id,
                'user_name' => $item->user->name ?? 'Unknown',
                'user_branch' => $item->user->branch ?? 'None',
            ];
        }

        return view('admin.inventory-receiving.index', compact('scannedItems'));
    }

    /**
     * Scan QR code and add to session list
     */
    public function scan(Request $request)
    {
        $this->checkPermission('edit');
        $request->validate([
             'qr_data' => 'required',
        ]);

        try {

            // Decode QR data - handle both string and array inputs
            $qrData = $request->qr_data;

            // If it's a string, try to decode it as JSON
            if (is_string($qrData)) {
                $decoded = json_decode($qrData, true);

                if (is_array($decoded)) {
                    $qrData = $decoded;
                } else {
                    $jsonError = json_last_error_msg();
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid QR code format: Not valid JSON. Error: ' . $jsonError,
                    ], 400);
                }
            }

            // Validate data structure
            if (!is_array($qrData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code format: Expected array/object, got ' . gettype($qrData),
                ], 400);
            }

            if (!isset($qrData['type'])) {

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code format: Missing "type" field. Found keys: ' . implode(', ', array_keys($qrData)),
                ], 400);
            }

            if ($qrData['type'] !== 'inventory_shipment') {
                Log::error("ADMIN SCAN - Wrong Type", [
                    'type' => $qrData['type'],
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code type. Please scan inventory shipment QR codes only. Found type: ' . $qrData['type'],
                ], 400);
            }

            if (!isset($qrData['shipment_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code - missing shipment ID',
                ], 400);
            }

            $shipmentId = $qrData['shipment_id'];

            // Get shipment details
            $shipment = InventoryShipment::findOrFail($shipmentId);

            // Check if already received
            if ($shipment->status === 'Received' && $shipment->f_status === 'Received') {

                return response()->json([
                    'success' => false,
                    'message' => 'This shipment has already been received',
                    'already_scanned' => true,
                ], 200);
            }

            // Check if already in user's list (database)
            $existing = InventoryReceivingTemp::where('user_id', Auth::id())
                ->where('shipment_id', $shipmentId)
                ->first();

            if ($existing) {

                return response()->json([
                    'success' => false,
                    'message' => 'This item is already in your receiving list',
                    'already_in_list' => true,
                ], 200);
            }

            // Add to database
            $item = InventoryReceivingTemp::create([
                'user_id' => Auth::id(),
                'shipment_id' => $shipment->id,
                'order_number' => $shipment->order,
                'product_name' => $shipment->title,
                'quantity' => $shipment->quantity,
                'destination' => $shipment->destination,
                'qr_data' => $qrData,
                'scanned_at' => now(),
            ]);

            // Audit log
            try {
                ActivityLogger::make()->useLog('inventory')->event('scanned')->on($shipment)
                    ->log("Shipment #{$shipment->id} '{$shipment->title}' scanned into receiving list (Order: {$shipment->order}, Dest: {$shipment->destination})");
            } catch (\Throwable) {}

            // Get total items in list
            $totalItems = InventoryReceivingTemp::where('user_id', Auth::id())->count();

            $user = Auth::user();

            return response()->json([
                'success' => true,
                'message' => 'Item added to receiving list',
                'item' => [
                    'shipment_id' => $item->shipment_id,
                    'order_number' => $item->order_number,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'destination' => $item->destination,
                    'scanned_at' => $item->scanned_at->format('Y-m-d H:i:s'),
                    'user_name' => $user->name,
                    'user_branch' => $user->branch ?? 'None',
                ],
                'total_items' => $totalItems,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing QR code: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove item from database list
     */
    public function removeItem($shipmentId)
    {
        $this->checkPermission('edit');

        $item = InventoryReceivingTemp::where('user_id', Auth::id())
            ->where('shipment_id', $shipmentId)
            ->first();

        if ($item) {
            $shipmentLabel = $item->product_name ?? "shipment #{$shipmentId}";
            $item->delete();

            // Audit log
            try {
                ActivityLogger::make()->useLog('inventory')->event('deleted')
                    ->log("Removed '{$shipmentLabel}' (shipment #{$shipmentId}) from receiving list");
            } catch (\Throwable) {}

            $totalItems = InventoryReceivingTemp::where('user_id', Auth::id())->count();

            return response()->json([
                'success' => true,
                'message' => 'Item removed from list',
                'total_items' => $totalItems,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Item not found in list',
        ], 404);
    }

    /**
     * Clear all scanned items from database
     */
    public function clearList()
    {
        $this->checkPermission('edit');

        // Admin can clear all items, regular users can only clear their own
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');

        if ($isAdmin) {
            $deleted = InventoryReceivingTemp::count();
            InventoryReceivingTemp::truncate();
        } else {
            $deleted = InventoryReceivingTemp::where('user_id', Auth::id())->delete();
        }

        // Audit log
        try {
            ActivityLogger::make()->useLog('inventory')->event('deleted')
                ->log("Receiving list cleared — {$deleted} item(s) removed");
        } catch (\Throwable) {}

        return response()->json([
            'success' => true,
            'message' => "Cleared {$deleted} items from list",
            'total_items' => 0,
        ]);
    }

    /**
     * Bulk delete selected items
     */
    public function bulkDelete(Request $request)
    {
        $this->checkPermission('edit');

        $request->validate([
            'shipment_ids' => 'required|array',
            'shipment_ids.*' => 'required|integer',
        ]);

        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');
        $shipmentIds = $request->shipment_ids;

        $query = InventoryReceivingTemp::whereIn('shipment_id', $shipmentIds);

        // If not admin, restrict to own items only
        if (!$isAdmin) {
            $query->where('user_id', Auth::id());
        }

        $deleted = $query->delete();

        // Count remaining items based on user role
        $totalItems = $isAdmin
            ? InventoryReceivingTemp::count()
            : InventoryReceivingTemp::where('user_id', Auth::id())->count();

        return response()->json([
            'success' => true,
            'message' => "Deleted {$deleted} item(s) from list",
            'total_items' => $totalItems,
        ]);
    }

    /**
     * Save all scanned items - update inventory shipments and order items
     */
    public function saveReceiving(Request $request)
    {
        $this->checkPermission('edit');

        // Get items from database
        $scannedItems = InventoryReceivingTemp::where('user_id', Auth::id())
            ->with('shipment')
            ->get();

        if ($scannedItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No items to save. Please scan items first.',
            ], 400);
        }

        try {
            DB::beginTransaction();

            $userId = Auth::id();
            $updatedShipments = 0;
            $updatedOrderItems = 0;
            $ordersToCheck = [];

            foreach ($scannedItems as $item) {
                // Update inventory shipment
                $shipment = $item->shipment;

                if (!$shipment) {
                    Log::warning("Shipment not found", ['shipment_id' => $item->shipment_id]);
                    continue;
                }

                $shipment->update([
                    'f_status' => 'Received',
                    'status' => 'Received',
                    'received_by' => $userId,
                    'updated_by' => $userId,
                ]);
                $updatedShipments++;

                    // Find and update corresponding order item
                    if ($shipment->order) {
                        $order = Order::where('order_number', $shipment->order)
                            ->with(['products' => function($query) {
                                $query->withPivot(['id', 'product_id', 'variation_id', 'item_status', 'cancellation_reason', 'eta']);
                            }])
                            ->first();

                        if ($order) {
                            // Try to find the specific product - PRIORITY: SKU match, then NAME match
                            $matchedProduct = null;
                            $shipmentTitle = strtolower(trim($shipment->title));

                            // Helper function to normalize strings for fuzzy matching
                            $normalize = function($str) {
                                // Remove spaces, hyphens, special characters, convert to lowercase
                                return preg_replace('/[^a-z0-9]/', '', strtolower($str));
                            };

                            $normalizedShipmentTitle = $normalize($shipment->title);

                            // First pass: Try to match by SKU
                            foreach ($order->products as $product) {
                                if (!empty($product->sku) && stripos($shipmentTitle, strtolower($product->sku)) !== false) {
                                    $matchedProduct = $product;

                                    break;
                                }
                            }

                            // Second pass: If no SKU match, try NAME match (exact substring)
                            if (!$matchedProduct) {
                                foreach ($order->products as $product) {
                                    $productName = strtolower(trim($product->name));
                                    // Match if either contains the other (case-insensitive)
                                    if (stripos($productName, $shipmentTitle) !== false ||
                                        stripos($shipmentTitle, $productName) !== false) {
                                        $matchedProduct = $product;

                                        break;
                                    }
                                }
                            }

                            // Third pass: Fuzzy match - normalize and compare
                            if (!$matchedProduct) {
                                foreach ($order->products as $product) {
                                    $normalizedProductName = $normalize($product->name);

                                    // Check if normalized strings contain each other
                                    if (stripos($normalizedProductName, $normalizedShipmentTitle) !== false ||
                                        stripos($normalizedShipmentTitle, $normalizedProductName) !== false) {
                                        $matchedProduct = $product;

                                        break;
                                    }

                                    // Also calculate similarity percentage for very close matches
                                    similar_text($normalizedProductName, $normalizedShipmentTitle, $percent);
                                    if ($percent >= 80) { // 80% similarity threshold
                                        $matchedProduct = $product;

                                        break;
                                    }
                                }
                            }

                            if ($matchedProduct) {
                                // Update specific order item to "arrived at local branch"
                                $updated = DB::table('order_products')
                                    ->where('id', $matchedProduct->pivot->id)
                                    ->where('order_id', $order->id)
                                    ->whereNull('deleted_at')
                                    ->update([
                                        'item_status' => 'arrived at local branch',
                                        'updated_at' => now(),
                                    ]);

                                if ($updated > 0) {
                                    $updatedOrderItems += $updated;

                                    // Sync order item status to CRM
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

                            // Add order to check list for status update
                            if (!in_array($order->id, $ordersToCheck)) {
                                $ordersToCheck[] = $order->id;
                            }
                        }
                    }
            }

            // Check if orders should be updated to "ready for collection"
            foreach ($ordersToCheck as $orderId) {
                $this->checkAndUpdateOrderStatus($orderId);
            }

            DB::commit();

            // Audit log — one entry summarising the entire receive batch
            try {
                ActivityLogger::make()->useLog('inventory')->event('received')
                    ->log("Inventory receive saved: {$updatedShipments} shipment(s) marked Received, {$updatedOrderItems} order item(s) set to 'arrived at local branch'");
            } catch (\Throwable) {}

            // Persist scan history log before clearing temp table
            $savedAt = now();
            $user    = Auth::user();
            $logRows = [];
            foreach ($scannedItems as $logItem) {
                $logRows[] = [
                    'user_id'      => $userId,
                    'branch'       => $user->branch ?? 'None',
                    'shipment_id'  => $logItem->shipment_id,
                    'order_number' => $logItem->order_number,
                    'product_name' => $logItem->product_name,
                    'quantity'     => $logItem->quantity,
                    'destination'  => $logItem->destination,
                    'scanned_at'   => $logItem->scanned_at,
                    'saved_at'     => $savedAt,
                    'created_at'   => $savedAt,
                    'updated_at'   => $savedAt,
                ];
            }
            if (!empty($logRows)) {
                InventoryReceivingLog::insert($logRows);
            }

            // Clear database list after successful save
            InventoryReceivingTemp::where('user_id', $userId)->delete();

            return response()->json([
                'success' => true,
                'message' => "Successfully received {$updatedShipments} shipment(s) and updated {$updatedOrderItems} order item(s)",
                'data' => [
                    'shipments_updated' => $updatedShipments,
                    'order_items_updated' => $updatedOrderItems,
                    'orders_checked' => count($ordersToCheck),
                ],
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
     * Check if all order items have arrived and update order status
     */
    protected function checkAndUpdateOrderStatus($orderId)
    {
        try {
            $orderItems = DB::table('order_products')
                ->where('order_id', $orderId)
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
                $readyForCollectionStatus = \App\Models\OrderStatus::where('slug', 'ready-for-collection')
                    ->orWhere('name', 'Ready for Collection')
                    ->first();

                if (!$readyForCollectionStatus) {
                    // Create the status if it doesn't exist
                    $maxSequence = \App\Models\OrderStatus::max('sequence') ?? 0;
                    $readyForCollectionStatus = \App\Models\OrderStatus::create([
                        'name' => 'Ready for Collection',
                        'slug' => 'ready-for-collection',
                        'status' => 1,
                        'sequence' => $maxSequence + 1,
                        'system_reserve' => 0,
                    ]);
                }

                // Update order status
                $order = Order::find($orderId);
                if ($order) {
                    $oldStatusId = $order->order_status_id;

                    $order->update([
                        'order_status_id' => $readyForCollectionStatus->id,
                    ]);

                    // Sync order status change to CRM
                    // The OrderObserver should handle this, but we dispatch explicitly to be sure
                    if (!$order->parent_id && !$order->is_gift_order) {
                        SyncOrderToCrm::dispatch($order->id, 'updated')->afterCommit();

                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Error checking/updating order status: " . $e->getMessage());
        }
    }

    /**
     * Scan history — queryable log of all saved scan sessions
     */
    public function scanHistory(Request $request)
    {
        $this->checkPermission('edit');

        $branch = $request->query('branch');
        $date   = $request->query('date'); // expects Y-m-d

        $query = InventoryReceivingLog::with('user')
            ->orderBy('saved_at', 'desc');

        if ($branch && $branch !== 'all') {
            $query->where('branch', $branch);
        }

        if ($date) {
            $query->whereDate('saved_at', $date);
        }

        $perPage  = 25;
        $paginator = $query->paginate($perPage);
        $logCollection = $paginator->getCollection();

        // Batch-fetch inventory shipment status via shipment_id (direct FK — always reliable)
        $shipmentIds = $logCollection->pluck('shipment_id')->filter()->unique()->values()->toArray();
        $shipmentStatuses = [];
        if (!empty($shipmentIds)) {
            try {
                DB::table('inventory_shipments')
                    ->whereIn('id', $shipmentIds)
                    ->select('id', 'status')
                    ->get()
                    ->each(function ($s) use (&$shipmentStatuses) {
                        $shipmentStatuses[$s->id] = $s->status;
                    });
            } catch (\Throwable $e) {
                Log::error('scanHistory shipment status lookup failed: ' . $e->getMessage());
            }
        }

        $logs = $logCollection->map(function ($log) use ($shipmentStatuses) {
            return [
                'id'               => $log->id,
                'branch'           => $log->branch,
                'product_name'     => $log->product_name,
                'order_number'     => $log->order_number ?? 'N/A',
                'inventory_status' => $shipmentStatuses[$log->shipment_id] ?? null,
                'quantity'         => $log->quantity,
                'destination'      => $log->destination ?? 'N/A',
                'scanned_by'       => $log->user->name ?? 'Unknown',
                'scanned_at'       => $log->scanned_at ? $log->scanned_at->format('Y-m-d H:i:s') : 'N/A',
                'saved_at'         => $log->saved_at ? $log->saved_at->format('Y-m-d H:i:s') : 'N/A',
            ];
        });

        return response()->json([
            'success'      => true,
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'data'         => $logs,
        ]);
    }

    /**
     * Get current scanned items count
     */
    public function getScannedItemsCount()
    {
        $count = InventoryReceivingTemp::where('user_id', Auth::id())->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Debug: Check order item status for a shipment
     */
    public function debugOrderItem($shipmentId)
    {
        $this->checkPermission('edit');

        $shipment = InventoryShipment::find($shipmentId);

        if (!$shipment || !$shipment->order) {
            return response()->json([
                'error' => 'Shipment not found or has no order number',
            ]);
        }

        $order = Order::where('order_number', $shipment->order)
            ->with(['products' => function($query) {
                $query->withPivot(['id', 'product_id', 'item_status', 'quantity']);
            }])
            ->first();

        if (!$order) {
            return response()->json([
                'error' => 'Order not found',
                'order_number' => $shipment->order,
            ]);
        }

        $orderItems = [];
        foreach ($order->products as $product) {
            $orderItems[] = [
                'pivot_id' => $product->pivot->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku ?? 'N/A',
                'quantity' => $product->pivot->quantity,
                'item_status' => $product->pivot->item_status,
                'matches_shipment' => (stripos($product->name, $shipment->title) !== false ||
                                      stripos($shipment->title, $product->name) !== false),
            ];
        }

        return response()->json([
            'shipment' => [
                'id' => $shipment->id,
                'order_number' => $shipment->order,
                'title' => $shipment->title,
                'status' => $shipment->status,
                'f_status' => $shipment->f_status,
            ],
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'order_status_id' => $order->order_status_id,
            ],
            'order_items' => $orderItems,
        ]);
    }
}
