<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WalletPointsDetail;
use App\Http\Traits\WalletPointsTrait;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Enums\RequestEnum;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Events\UpdateReturnRequestEvent;

class AdminReturnsController extends Controller
{
    use WalletPointsTrait;

    public function __construct()
    {
        $this->middleware('can:return.index')->only(['index']);
        $this->middleware('can:return.show')->only(['show']);
        $this->middleware('can:return.edit')->only(['update']);
    }

    /**
     * Display returns listing page
     */
    public function index(Request $request)
    {
        // Store current page in session
        if ($request->has('page')) {
            $request->session()->put('returns_list_page', $request->page);
        }

        $query = ReturnRequest::with(['user', 'order', 'product'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by preferred outcome
        if ($request->filled('preferred_outcome')) {
            $query->where('preferred_outcome', $request->preferred_outcome);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Search by order number, customer name, or product name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('order', function($orderQuery) use ($search) {
                    $orderQuery->where('order_number', 'LIKE', "%{$search}%");
                })
                ->orWhereHas('user', function($userQuery) use ($search) {
                    $userQuery->where('name', 'LIKE', "%{$search}%")
                              ->orWhere('email', 'LIKE', "%{$search}%");
                })
                ->orWhereHas('product', function($productQuery) use ($search) {
                    $productQuery->where('name', 'LIKE', "%{$search}%");
                });
            });
        }

        $returns = $query->paginate(20)->appends($request->query());

        return view('admin.returns.index', compact('returns'));
    }

    /**
     * Show return details
     */
    public function show($id)
    {
        $return = ReturnRequest::with([
            'user.paymentAccounts',
            'order.consumer',
            'order.products',
            'order.order_status',
            'order.shipping_address.country',
            'order.shipping_address.state',
            'order.billing_address.country',
            'order.billing_address.state',
            'product.product_thumbnail'
        ])->findOrFail($id);


        return view('admin.returns.show', compact('return'));
    }

    /**
     * Update return status
     */
    public function update(Request $request, $id)
    {

        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $return = ReturnRequest::with(['user', 'order', 'product'])->findOrFail($id);

            // Store old status for comparison
            $oldStatus = $return->status;

            // Update status
            $return->status = $request->status;

            if ($request->status === 'rejected' && $request->filled('rejection_reason')) {
                $return->rejection_reason = $request->rejection_reason;
            }

            // Clear rejection reason if reopening
            if ($request->status === 'pending' && in_array($oldStatus, ['approved', 'rejected'])) {
                $return->rejection_reason = null;
            }

            // If approved and preferred outcome is credit/wallet, credit the wallet
            if ($request->status === 'approved' && in_array($return->preferred_outcome, ['credit', 'wallet', 'replacement'])) {
                try {
                    $this->creditWallet(
                        $return->user_id,
                        $return->product_total ?? $return->product_price,
                        WalletPointsDetail::REFUND,
                        "Return approved - Credit for order #{$return->order->order_number}, Product: {$return->product->name}"
                    );
                } catch (\Exception $e) {
                    \Log::error('Failed to credit wallet for approved return', [
                        'return_id' => $return->id,
                        'user_id' => $return->user_id,
                        'error' => $e->getMessage()
                    ]);
                    // Continue with return approval even if wallet credit fails
                }
            }

            $return->save();

            DB::commit();

            // Audit log
            try {
                ActivityLogger::make()->useLog('return')->event('updated')->on($return)
                    ->withChanges(['status' => $oldStatus], ['status' => $request->status])
                    ->log("Return #{$id} status changed from '{$oldStatus}' to '{$request->status}'"
                        . ($return->order ? " (Order #{$return->order->order_number})" : '')
                        . ($return->product ? ", Product: {$return->product->name}" : ''));
            } catch (\Throwable) {}

            // Fire event to send notification email to customer
            // Pass old status to detect if this was a reopen action
            event(new UpdateReturnRequestEvent($return, $oldStatus));

            // Show appropriate message based on action
            if ($oldStatus !== 'pending' && $request->status === 'pending') {
                $message = 'Return reopened successfully and set to pending';
            } else {
                $message = $request->status === 'approved'
                    ? 'Return approved successfully. Wallet has been credited.'
                    : 'Return rejected successfully';
            }

            // Check if this is an AJAX request (returns JSON)
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'return' => $return
                ]);
            }

            // For regular form submissions, redirect back with message
            // Check if there's a 'from_page' parameter to return to that page
            $fromPage = $request->input('from_page', $request->session()->get('returns_list_page', 1));

            return redirect()->route('admin.returns.index', ['page' => $fromPage])
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            // JSON response for AJAX
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update return: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to update return: ' . $e->getMessage());
        }
    }
}

