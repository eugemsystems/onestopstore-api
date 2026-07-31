<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryShipment;
 use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProcessingOrdersLinkController extends Controller
{
    /**
     * Constructor with permission middleware
     */
    public function __construct()
    {
        $this->middleware('permission:processing-link-builder.index')->only(['index']);
        $this->middleware('permission:processing-link-builder.transfer-to-inventory')->only(['transferToInventory']);
    }

    public function index(Request $request)
    {
        try {
            // Get all order items (products) from orders with "processing" status
            $query = DB::table('order_products as op')
                ->join('orders as o', 'op.order_id', '=', 'o.id')
                ->join('order_status as os', 'o.order_status_id', '=', 'os.id')
                ->join('products as p', 'op.product_id', '=', 'p.id')
                ->whereRaw('LOWER(os.name) LIKE ?', ['%processing%'])
                ->whereNull('o.deleted_at')
                ->whereNull('op.deleted_at')
                ->whereNotNull('p.sku')
                ->where('p.sku', '!=', '')
                ->select(
                    'op.id as order_product_id',
                    'o.id as order_id',
                    'o.order_number',
                    'o.created_at as order_date',
                    'os.name as status',
                    'p.id as product_id',
                    'p.name as product_name',
                    'p.sku',
                    'op.quantity',
                    'op.single_price',
                    'op.item_status',
                    'op.added_to_inventory',
                    'op.inventory_shipment_id',
                    'o.delivery_description as destination',
                    DB::raw('COALESCE(op.variation_display_name, \'\') as variation')
                );

            // Filter by branch/delivery method
            if ($request->filled('branch')) {
                $branchFilter = $request->branch;

                $query->where(function($q) use ($branchFilter) {
                    if ($branchFilter === 'Harare Branch') {
                        $q->whereRaw('LOWER(o.delivery_description) LIKE ?', ['%harare branch%']);
                    } elseif ($branchFilter === 'Bulawayo Branch') {
                        $q->whereRaw('LOWER(o.delivery_description) LIKE ?', ['%bulawayo branch%']);
                    } elseif ($branchFilter === 'Lusaka Branch') {
                        $q->whereRaw('LOWER(o.delivery_description) LIKE ?', ['%lusaka branch%']);
                    } elseif ($branchFilter === 'Mutare Branch') {
                        $q->whereRaw('LOWER(o.delivery_description) LIKE ?', ['%mutare branch%']);
                    } elseif ($branchFilter === 'Home Delivery') {
                        $q->whereRaw('LOWER(o.delivery_description) LIKE ?', ['%standard home delivery%']);
                    } else {
                        // For other delivery methods, try to match exactly
                        $q->where('o.delivery_description', $branchFilter);
                    }
                });
            }

            // Filter by item status
            if ($request->filled('item_status')) {
                $itemStatusFilter = strtolower($request->item_status);
                $query->whereRaw('LOWER(op.item_status) = ?', [$itemStatusFilter]);
            }

            // Filter by inventory status
            if ($request->filled('inventory_status')) {
                if ($request->inventory_status === 'in_inventory') {
                    $query->where('op.added_to_inventory', true);
                } elseif ($request->inventory_status === 'not_in_inventory') {
                    $query->where(function($q) {
                        $q->where('op.added_to_inventory', false)
                          ->orWhereNull('op.added_to_inventory');
                    });
                }
            }

            // Search by product name or order number
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    // Search in product name (case-insensitive)
                    $q->whereRaw('LOWER(p.name) LIKE ?', ['%' . strtolower($searchTerm) . '%'])
                      // Search in order number
                      ->orWhere('o.order_number', 'LIKE', '%' . $searchTerm . '%')
                      // Search in SKU
                      ->orWhere('p.sku', 'LIKE', '%' . $searchTerm . '%')
                      // Search in variation name
                      ->orWhereRaw('LOWER(op.variation_display_name) LIKE ?', ['%' . strtolower($searchTerm) . '%']);
                });
            }

            // Apply price sorting if requested
            $sortBy = $request->input('sort_by', 'date');
            if ($sortBy === 'price_asc') {
                $query->orderBy('op.single_price', 'asc');
            } elseif ($sortBy === 'price_desc') {
                $query->orderBy('op.single_price', 'desc');
            } else {
                // Default sorting by date
                $query->orderBy('o.created_at', 'desc');
            }

            $query->orderBy('op.id', 'asc');

            // Pagination
            $perPage = $request->input('per_page', 100);
            $orderItems = $query->paginate($perPage);

            // Calculate counts per branch/delivery method
            $branchCountsQuery = DB::table('order_products as op')
                ->join('orders as o', 'op.order_id', '=', 'o.id')
                ->join('order_status as os', 'o.order_status_id', '=', 'os.id')
                ->join('products as p', 'op.product_id', '=', 'p.id')
                ->whereRaw('LOWER(os.name) LIKE ?', ['%processing%'])
                ->whereNull('o.deleted_at')
                ->whereNull('op.deleted_at')
                ->whereNotNull('p.sku')
                ->where('p.sku', '!=', '')
                ->select('o.delivery_description', DB::raw('COUNT(*) as count'))
                ->groupBy('o.delivery_description')
                ->get();

            // Aggregate counts by shortened branch names
            $aggregatedCounts = [];
            foreach ($branchCountsQuery as $item) {
                $shortName = $this->shortenDeliveryDescription($item->delivery_description);
                if (!isset($aggregatedCounts[$shortName])) {
                    $aggregatedCounts[$shortName] = 0;
                }
                $aggregatedCounts[$shortName] += $item->count;
            }

            $branchCounts = $aggregatedCounts;

            // If no items, let's check why
            if ($orderItems->total() === 0) {
                $statusCheck = DB::table('order_status')
                    ->whereRaw('LOWER(name) LIKE ?', ['%processing%'])
                    ->first();
            }

            return view('admin.orders.processing-link-builder', compact('orderItems', 'branchCounts'));
        } catch (\Exception $e) {
            \Log::error('Error fetching processing order items for link builder', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to load processing order items: ' . $e->getMessage());
        }
    }

    /**
     * Shorten delivery description for better readability
     */
    private function shortenDeliveryDescription(?string $description): string
    {
        if (empty($description)) {
            return 'Other';
        }

        // Check for partial matches (case-insensitive)
        $lowerDesc = strtolower($description);

        if (strpos($lowerDesc, 'harare') !== false) {
            return 'Harare Branch';
        }
        if (strpos($lowerDesc, 'bulawayo') !== false) {
            return 'Bulawayo Branch';
        }
        if (strpos($lowerDesc, 'lusaka') !== false) {
            return 'Lusaka Branch';
        }
        if (strpos($lowerDesc, 'mutare') !== false) {
            return 'Mutare Branch';
        }
        if (strpos($lowerDesc, 'home delivery') !== false || strpos($lowerDesc, 'standard home') !== false) {
            return 'Home Delivery';
        }

        // If no match found, return 'Other'
        return 'Other';
    }

    /**
     * Transfer selected order items to inventory shipment
     */
    public function transferToInventory(Request $request)
    {
        try {
            $validated = $request->validate([
                'order_product_ids' => 'required|array|min:1',
                'order_product_ids.*' => 'required|integer|exists:order_products,id',
            ]);

            $orderProductIds = $validated['order_product_ids'];

            // Get the order items with their details
            $orderItems = DB::table('order_products as op')
                ->join('orders as o', 'op.order_id', '=', 'o.id')
                ->join('products as p', 'op.product_id', '=', 'p.id')
                ->whereIn('op.id', $orderProductIds)
                ->whereNull('op.deleted_at')
                ->select(
                    'op.id as order_product_id',
                    'o.order_number',
                    'p.name as product_name',
                    'p.sku',
                    'op.quantity',
                    'op.variation_display_name',
                    'o.delivery_description'
                )
                ->get();

            if ($orderItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid items found to transfer.'
                ], 404);
            }

            $createdShipments = [];

            DB::beginTransaction();

            foreach ($orderItems as $item) {
                // Check if already added to inventory
                $alreadyAdded = DB::table('order_products')
                    ->where('id', $item->order_product_id)
                    ->value('added_to_inventory');

                if ($alreadyAdded) {
                    continue; // Skip items already added
                }

                // Determine destination from delivery description
                $shortName = $this->shortenDeliveryDescription($item->delivery_description);

                // Map branch names to valid destination values (matching validation rules)
                $destinationMap = [
                    'Harare Branch' => 'Harare',
                    'Bulawayo Branch' => 'Bulawayo',
                    'Mutare Branch' => 'Mutare',
                    'Lusaka Branch' => 'Zambia',
                    'Home Delivery' => 'Harare',
                    'Other' => 'Harare',
                ];

                $destination = $destinationMap[$shortName] ?? 'Harare';

                // Create title with product name and variation
                $title = $item->product_name;
                if (!empty($item->variation_display_name)) {
                    $title .= ' - ' . $item->variation_display_name;
                }

                // Create inventory shipment
                $shipment = InventoryShipment::create([
                    'order' => $item->order_number,
                    'title' => $title,
                    'quantity' => $item->quantity,
                    'destination' => $destination,
                    'status' => 'Not yet',
                    'notes' => 'Auto-created from processing order #' . $item->order_number . ' (SKU: ' . $item->sku . ')',
                    'created_by' => Auth::id(),
                ]);

                // Update order_product to mark as added to inventory
                DB::table('order_products')
                    ->where('id', $item->order_product_id)
                    ->update([
                        'added_to_inventory' => true,
                        'inventory_shipment_id' => $shipment->id,
                        'added_to_inventory_at' => now(),
                        'updated_at' => now(),
                    ]);

                $createdShipments[] = [
                    'shipment_id' => $shipment->id,
                    'order_number' => $item->order_number,
                    'product_name' => $title,
                ];
            }

            DB::commit();

            if (empty($createdShipments)) {
                return response()->json([
                    'success' => false,
                    'message' => 'All selected items have already been added to inventory.'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => count($createdShipments) . ' item(s) successfully transferred to inventory shipments.',
                'data' => $createdShipments
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error transferring items to inventory', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to transfer items to inventory: ' . $e->getMessage()
            ], 500);
        }
    }
}
