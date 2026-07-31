<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminOrderQRCodeController extends Controller
{
    protected $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;

        // Permission middleware
        $this->middleware(function ($request, $next) {
            $action = $request->route()->getActionMethod();

            $permissionMap = [
                'generateQRCodes' => 'order.edit',
                'showQRCodes' => 'order.index',
                'downloadQRCode' => 'order.index',
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
     * Generate QR codes for all items in an order
     */
    public function generateQRCodes($orderNumber)
    {
        try {
            $order = Order::where('order_number', $orderNumber)
                ->with(['products' => function($query) {
                    $query->withPivot(['id', 'product_id', 'quantity', 'item_status']);
                }])
                ->firstOrFail();

            $qrCodesGenerated = 0;
            $errors = [];

            foreach ($order->products as $product) {
                try {
                    // Get the pivot data
                    $pivotId = $product->pivot->id;
                    $productSku = $product->sku ?? 'NO-SKU';

                    // Generate QR code
                    $qrCodeBase64 = $this->qrCodeService->generateOrderItemQRCode(
                        $order->id,
                        $order->order_number,
                        $product->id,
                        $product->name,
                        $productSku,
                        $pivotId
                    );

                    // Update the pivot table with QR code
                    DB::table('order_products')
                        ->where('id', $pivotId)
                        ->update([
                            'qr_code' => $qrCodeBase64,
                            'updated_at' => now(),
                        ]);

                    $qrCodesGenerated++;
                } catch (\Exception $e) {
                    Log::error("Failed to generate QR code for product {$product->id}: " . $e->getMessage());
                    $errors[] = "Failed to generate QR code for {$product->name}";
                }
            }

            if ($qrCodesGenerated === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate any QR codes',
                    'errors' => $errors,
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => "Generated {$qrCodesGenerated} QR code(s) successfully",
                'qr_codes_count' => $qrCodesGenerated,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            Log::error("Error generating QR codes for order {$orderNumber}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR codes: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show QR codes for an order
     */
    public function showQRCodes($orderNumber)
    {
        try {
            $order = Order::where('order_number', $orderNumber)
                ->with(['products' => function($query) {
                    $query->withPivot(['id', 'product_id', 'quantity', 'item_status', 'qr_code']);
                }])
                ->firstOrFail();

            $orderItems = [];
            
            foreach ($order->products as $product) {
                $orderItems[] = [
                    'pivot_id' => $product->pivot->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku ?? 'NO-SKU',
                    'quantity' => $product->pivot->quantity,
                    'item_status' => $product->pivot->item_status,
                    'qr_code' => $product->pivot->qr_code,
                    'has_qr_code' => !empty($product->pivot->qr_code),
                ];
            }

            return view('admin.orders.qr-codes', [
                'order' => $order,
                'orderItems' => $orderItems,
            ]);

        } catch (\Exception $e) {
            Log::error("Error showing QR codes for order {$orderNumber}: " . $e->getMessage());
            
            return redirect()->back()->with('error', 'Failed to load QR codes: ' . $e->getMessage());
        }
    }

    /**
     * Download a single QR code as PNG
     */
    public function downloadQRCode($orderNumber, $pivotId)
    {
        try {
            $orderItem = DB::table('order_products')
                ->join('orders', 'order_products.order_id', '=', 'orders.id')
                ->join('products', 'order_products.product_id', '=', 'products.id')
                ->where('orders.order_number', $orderNumber)
                ->where('order_products.id', $pivotId)
                ->whereNull('order_products.deleted_at')
                ->select('order_products.qr_code', 'products.name', 'products.sku')
                ->first();

            if (!$orderItem || empty($orderItem->qr_code)) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR code not found',
                ], 404);
            }

            // Decode base64 QR code
            $qrCodeData = base64_decode($orderItem->qr_code);

            // Create filename
            $filename = "qr_order_{$orderNumber}_item_{$pivotId}.png";

            return response($qrCodeData)
                ->header('Content-Type', 'image/png')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");

        } catch (\Exception $e) {
            Log::error("Error downloading QR code: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to download QR code',
            ], 500);
        }
    }

    /**
     * Get QR codes data as JSON (for API calls)
     */
    public function getQRCodesJson($orderNumber)
    {
        try {
            $order = Order::where('order_number', $orderNumber)
                ->with(['products' => function($query) {
                    $query->withPivot(['id', 'product_id', 'quantity', 'item_status', 'qr_code']);
                }])
                ->firstOrFail();

            $orderItems = [];
            
            foreach ($order->products as $product) {
                $qrCodeData = null;
                if (!empty($product->pivot->qr_code)) {
                    $qrCodeData = 'data:image/png;base64,' . $product->pivot->qr_code;
                }

                $orderItems[] = [
                    'pivot_id' => $product->pivot->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku ?? 'NO-SKU',
                    'quantity' => $product->pivot->quantity,
                    'item_status' => $product->pivot->item_status,
                    'qr_code_data' => $qrCodeData,
                    'has_qr_code' => !empty($product->pivot->qr_code),
                ];
            }

            return response()->json([
                'success' => true,
                'order_number' => $order->order_number,
                'order_id' => $order->id,
                'items' => $orderItems,
            ]);

        } catch (\Exception $e) {
            Log::error("Error getting QR codes JSON for order {$orderNumber}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load QR codes',
            ], 500);
        }
    }
}
