<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\UpdateOrderItemToArrivedAtBranch;
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminQRScannerController extends Controller
{
    protected $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;

        // Permission middleware
        $this->middleware(function ($request, $next) {
            $action = $request->route()->getActionMethod();

            $permissionMap = [
                'showScanner' => 'order.edit',
                'scanQRCode' => 'order.edit',
                'handleCollectionQRScan' => 'order.edit',
            ];

            if (isset($permissionMap[$action])) {
                $requiredPermission = $permissionMap[$action];

                if (!auth()->user()->can($requiredPermission)) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => "Unauthorized. You need the '{$requiredPermission}' permission."
                        ], 403);
                    }
                    abort(403, "Unauthorized. You need the '{$requiredPermission}' permission.");
                }
            }

            return $next($request);
        });
    }

    /**
     * Show QR scanner interface
     */
    public function showScanner()
    {
        return view('admin.orders.qr-scanner');
    }

    /**
     * Process scanned QR code
     */
    public function scanQRCode(Request $request)
    {
        $request->validate([
            'qr_data' => 'required',
        ]);

        try {

            // Decode the QR data - handle both string and array inputs
            $qrData = $request->qr_data;

            // If it's a string, try to decode it as JSON
            if (is_string($qrData)) {
                $decoded = json_decode($qrData, true);

                // If json_decode succeeded and returned an array, use it
                if (is_array($decoded)) {
                    $qrData = $decoded;
                } else {
                    // If it's not valid JSON, log the error
                    $jsonError = json_last_error_msg();
                    Log::warning("QR data is not valid JSON", [
                        'qr_data' => substr($qrData, 0, 200),
                        'json_error' => $jsonError,
                        'user_id' => Auth::id(),
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid QR code format: Not valid JSON. Error: ' . $jsonError,
                    ], 400);
                }
            }

            // Validate the decoded data structure
            if (!is_array($qrData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code format: Expected array/object, got ' . gettype($qrData),
                ], 400);
            }

            if (!isset($qrData['type'])) {
                Log::warning("QR data missing 'type' field", [
                    'qr_data_keys' => array_keys($qrData),
                    'qr_data' => $qrData,
                    'user_id' => Auth::id(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code format: Missing "type" field. Found keys: ' . implode(', ', array_keys($qrData)),
                ], 400);
            }

            // Handle different QR code types
            if ($qrData['type'] === 'inventory_shipment') {
                return $this->processInventoryShipmentQR($qrData);
            } elseif ($qrData['type'] === 'order_item') {
                return $this->processOrderItemQR($qrData);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Unknown QR code type',
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error("Error processing QR scan: " . $e->getMessage(), [
                'qr_data' => $request->qr_data,
                'user_id' => Auth::id(),
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process QR code: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process inventory shipment QR code
     */
    protected function processInventoryShipmentQR($qrData)
    {
        if (!isset($qrData['shipment_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid shipment QR code - missing shipment ID',
            ], 400);
        }

        // Get inventory shipment
        $shipment = DB::table('inventory_shipments')
            ->where('id', $qrData['shipment_id'])
            ->whereNull('deleted_at')
            ->first();

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found or has been deleted',
            ], 404);
        }

        // Check if already received
        if ($shipment->status === 'Received') {
            return response()->json([
                'success' => false,
                'message' => 'This shipment has already been marked as received',
                'status' => $shipment->status,
                'already_scanned' => true,
            ], 200);
        }

        // Update shipment status to Received
        DB::table('inventory_shipments')
            ->where('id', $qrData['shipment_id'])
            ->update([
                'status' => 'Received',
                'f_status' => 'Received',
                'received_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Shipment scanned successfully and marked as received',
            'data' => [
                'shipment_id' => $shipment->id,
                'order_number' => $shipment->order ?? 'N/A',
                'product_name' => $qrData['product_name'] ?? $shipment->title,
                'quantity' => $shipment->quantity,
                'destination' => $shipment->destination,
                'previous_status' => $shipment->status,
                'new_status' => 'Received',
            ],
        ]);
    }

    /**
     * Process order item QR code (legacy)
     */
    protected function processOrderItemQR($qrData)
    {
        // Validate required fields
        if (!isset($qrData['pivot_id']) || !isset($qrData['order_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code data - missing required information',
            ], 400);
        }

        $pivotId = $qrData['pivot_id'];
        $orderId = $qrData['order_id'];

        // Check if order item exists and is not already processed
        $orderItem = DB::table('order_products')
            ->where('id', $pivotId)
            ->where('order_id', $orderId)
            ->whereNull('deleted_at')
            ->first();

        if (!$orderItem) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found or has been deleted',
            ], 404);
        }

        // Check if already arrived at local branch
        if ($orderItem->item_status === 'arrived at local branch') {
            return response()->json([
                'success' => false,
                'message' => 'This item has already been marked as arrived at local branch',
                'item_status' => $orderItem->item_status,
                'already_scanned' => true,
            ], 200);
        }

        // Get product information for response
        $product = DB::table('products')
            ->where('id', $orderItem->product_id)
            ->first();

        // Get order information
        $order = DB::table('orders')
            ->where('id', $orderId)
            ->first();

        // Dispatch job to update item status
        UpdateOrderItemToArrivedAtBranch::dispatch(
            $pivotId,
            $orderId,
            Auth::id()
        );


        return response()->json([
            'success' => true,
            'message' => 'Item scanned successfully and will be updated to "arrived at local branch"',
            'data' => [
                'order_number' => $order->order_number ?? 'N/A',
                'product_name' => $qrData['product_name'] ?? $product->name ?? 'Unknown',
                'product_sku' => $qrData['product_sku'] ?? $product->sku ?? 'N/A',
                'quantity' => $orderItem->quantity,
                'previous_status' => $orderItem->item_status,
                'new_status' => 'arrived at local branch',
            ],
        ]);
    }

    /**
     * Get scanning history (recent scans)
     */
    public function getScanHistory(Request $request)
    {
        try {
            $limit = $request->input('limit', 20);

            $history = DB::table('order_products')
                ->join('orders', 'order_products.order_id', '=', 'orders.id')
                ->join('products', 'order_products.product_id', '=', 'products.id')
                ->where('order_products.item_status', 'arrived at local branch')
                ->whereNotNull('order_products.qr_code')
                ->select(
                    'order_products.id as pivot_id',
                    'orders.order_number',
                    'products.name as product_name',
                    'products.sku as product_sku',
                    'order_products.quantity',
                    'order_products.updated_at as scanned_at'
                )
                ->orderBy('order_products.updated_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'history' => $history,
            ]);

        } catch (\Exception $e) {
            Log::error("Error getting scan history: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load scan history',
            ], 500);
        }
    }

    /**
     * Handle QR code scan from "Ready for Collection" email
     * This opens the order and optionally marks it as collected
     *
     * @param string $orderNumber
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleCollectionQRScan($orderNumber, Request $request)
    {
        try {
            // Find the order using Eloquent
            $order = \App\Models\Order::where('order_number', $orderNumber)
                ->with('order_status')
                ->first();

            if (!$order) {
                Log::warning("Collection QR scan - Order not found", [
                    'order_number' => $orderNumber,
                    'scanned_by' => Auth::id(),
                ]);

                return redirect()->route('admin.orders.show', $orderNumber)
                    ->with('error', 'Order not found');
            }

            // Check if auto_collect parameter is set (for automatic status update)
            $autoCollect = $request->query('auto_collect', 'false') === 'true';

            // Get current order status
            $currentStatus = $order->order_status;
            $currentStatusName = strtolower($currentStatus->name ?? '');


            // If auto_collect is enabled and order is "ready for collection", mark as collected
            if ($autoCollect && $currentStatusName === 'ready for collection') {
                $this->markOrderAsCollected($order);

                return redirect()->route('admin.orders.show', $orderNumber)
                    ->with('success', 'Order opened via QR scan and automatically marked as COLLECTED! ✓')
                    ->with('qr_scanned', true)
                    ->with('auto_collected', true);
            }

            // Otherwise just open the order
            return redirect()->route('admin.orders.show', $orderNumber)
                ->with('info', 'Order opened via QR scan. Status: ' . ($currentStatus->name ?? 'Unknown'))
                ->with('qr_scanned', true);

        } catch (\Exception $e) {
            Log::error("Error handling collection QR scan", [
                'order_number' => $orderNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'scanned_by' => Auth::id(),
            ]);

            return redirect()->route('admin.orders.show', $orderNumber)
                ->with('error', 'Error processing QR scan: ' . $e->getMessage());
        }
    }

    /**
     * Mark order as collected
     *
     * @param \App\Models\Order $order
     * @return void
     */
    protected function markOrderAsCollected($order)
    {
        try {
            // Find or create "Collected" status using Eloquent
            $collectedStatus = \App\Models\OrderStatus::where(function($query) {
                    $query->where('slug', 'collected')
                          ->orWhere('name', 'Collected');
                })
                ->first();

            if (!$collectedStatus) {
                // Create the status if it doesn't exist
                $maxSequence = \App\Models\OrderStatus::max('sequence') ?? 0;
                $collectedStatus = \App\Models\OrderStatus::create([
                    'name' => 'Collected',
                    'slug' => 'collected',
                    'status' => 1,
                    'sequence' => $maxSequence + 1,
                    'system_reserve' => 0,
                ]);
            }

            // Store previous status for logging
            $previousStatusId = $order->order_status_id;
            $previousStatusName = $order->order_status->name ?? 'Unknown';

            // Update order status using Eloquent
            $order->order_status_id = $collectedStatus->id;
            $order->save();


        } catch (\Exception $e) {
            throw $e;
        }
    }
}
