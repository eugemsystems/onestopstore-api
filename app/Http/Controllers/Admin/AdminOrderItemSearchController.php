<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminOrderItemSearchController extends BaseAdminController
{
    protected string $permissionPrefix = 'order-item';

    /**
     * Display the order item search page
     */
    public function index()
    {
        $this->checkPermission('search');

        return view('admin.orders.item-search');
    }

    /**
     * Search for order items
     * GET /admin/orders/item-search/search?q=search_term
     */
    public function search(Request $request)
    {
        $this->checkPermission('search');
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        try {
            // Split query into individual words for better matching
            $searchTerms = array_filter(explode(' ', $query));

            // Search order items with product names matching the query
            // Prioritize recent orders that are not pending or processing
            $results = DB::table('order_products as op')
                ->join('orders as o', 'op.order_id', '=', 'o.id')
                ->join('products as p', 'op.product_id', '=', 'p.id')
                ->join('order_status as os', 'o.order_status_id', '=', 'os.id')
                ->leftJoin('attachments as a', 'p.product_thumbnail_id', '=', 'a.id')
                ->select(
                    'op.id as order_item_id',
                    'o.id as order_id',
                    'o.order_number',
                    'o.created_at as order_date',
                    'p.id as product_id',
                    'p.name as product_name',
                    'op.variation_display_name',
                    'op.quantity',
                    'op.single_price',
                    'op.subtotal',
                    'op.item_status',
                    'os.name as order_status',
                    'os.slug as order_status_slug',
                    'a.image_url as product_image',
                    DB::raw("CASE
                        WHEN os.slug NOT IN ('pending', 'processing') THEN 1
                        ELSE 2
                    END as priority_order")
                )
                ->where(function($q) use ($searchTerms, $query) {
                    // If multiple words, search for each word anywhere in the product name
                    if (count($searchTerms) > 1) {
                        $q->where(function($subQ) use ($searchTerms) {
                            foreach ($searchTerms as $term) {
                                $subQ->whereRaw('p.name ILIKE ?', ['%' . $term . '%']);
                            }
                        })
                        ->orWhere(function($subQ) use ($searchTerms) {
                            foreach ($searchTerms as $term) {
                                $subQ->whereRaw('op.variation_display_name ILIKE ?', ['%' . $term . '%']);
                            }
                        });
                    } else {
                        // Single word search
                        $q->whereRaw('p.name ILIKE ?', ['%' . $query . '%'])
                          ->orWhereRaw('op.variation_display_name ILIKE ?', ['%' . $query . '%']);
                    }

                    // Also search by order number
                    $q->orWhereRaw('CAST(o.order_number AS TEXT) LIKE ?', ['%' . $query . '%']);
                })
                ->orderBy('priority_order', 'ASC')  // Non-pending/processing first
                ->orderBy('o.created_at', 'DESC')   // Then most recent
                ->limit(20)
                ->get();

            // Format the results
            $formattedResults = $results->map(function($item) {
                return [
                    'order_item_id' => $item->order_item_id,
                    'order_id' => $item->order_id,
                    'order_number' => $item->order_number,
                    'order_date' => $item->order_date,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'variation_display_name' => $item->variation_display_name,
                    'quantity' => (int) $item->quantity,
                    'single_price' => (float) $item->single_price,
                    'subtotal' => (float) $item->subtotal,
                    'item_status' => $item->item_status,
                    'order_status' => $item->order_status,
                    'order_status_slug' => $item->order_status_slug,
                    'product_image' => $item->product_image,
                    'display_text' => $this->formatDisplayText($item),
                    'is_priority' => $item->priority_order == 1, // true for non-pending/processing orders
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedResults
            ]);
        } catch (\Exception $e) {
            \Log::error('Order item search error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format display text for autocomplete dropdown
     */
    private function formatDisplayText($item)
    {
        $text = $item->product_name;

        if ($item->variation_display_name) {
            $text .= ' - ' . $item->variation_display_name;
        }

        $text .= ' (Order #' . $item->order_number . ')';
        $text .= ' - ' . ucfirst($item->order_status);

        return $text;
    }
}

