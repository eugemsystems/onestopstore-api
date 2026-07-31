<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Refund;
use App\Models\User;
use App\Models\Order;
use App\Enums\RequestEnum;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Events\UpdateRefundRequestEvent;
use App\Repositories\Eloquents\RefundRepository;

class AdminRefundsController extends Controller
{
    protected $refundRepository;

    public function __construct(RefundRepository $refundRepository)
    {
        $this->refundRepository = $refundRepository;
        $this->middleware('can:refund.index')->only(['index']);
        $this->middleware('can:refund.show')->only(['show']);
        $this->middleware('can:refund.edit')->only(['update']);
    }

    /**
     * Display refunds listing page
     */
    public function index(Request $request)
    {
        // Store current page in session
        if ($request->has('page')) {
            $request->session()->put('refunds_list_page', $request->page);
        }

        $query = Refund::with(['user', 'order'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment type
        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Search by order number, customer name, or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('order', function($orderQuery) use ($search) {
                    $orderQuery->where('order_number', 'LIKE', "%{$search}%");
                })
                ->orWhereHas('user', function($userQuery) use ($search) {
                    $userQuery->where('name', 'LIKE', "%{$search}%")
                              ->orWhere('email', 'LIKE', "%{$search}%");
                });
            });
        }

        $refunds = $query->paginate(20)->appends($request->query());

        return view('admin.refunds.index', compact('refunds'));
    }

    /**
     * Show refund details
     */
    public function show($id)
    {
        $refund = Refund::with([
            'user.paymentAccounts',
            'order.consumer',
            'order.products.product_thumbnail',
            'order.order_status',
            'order.status_histories.old_status',
            'order.status_histories.new_status',
            'order.status_histories.updated_by',
            'product.product_thumbnail'
        ])->findOrFail($id);

        return view('admin.refunds.show', compact('refund'));
    }

    /**
     * Update refund status
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:500'
        ]);

        try {
            $refundBefore = Refund::findOrFail($id);
            $oldStatus = $refundBefore->status;

            // If rejection reason is provided, pass it to the repository
            $updateData = ['status' => $request->status];

            if ($request->status === 'rejected' && $request->filled('rejection_reason')) {
                $updateData['reason'] = $request->rejection_reason;
            }

            // Use the repository which handles wallet credits/debits properly
            $refund = $this->refundRepository->update($updateData, $id);

            // Audit log
            try {
                ActivityLogger::make()->useLog('refund')->event('updated')->on($refundBefore)
                    ->withChanges(['status' => $oldStatus], ['status' => $request->status])
                    ->log("Refund #{$id} status changed from '{$oldStatus}' to '{$request->status}'"
                        . ($refundBefore->order ? " (Order #{$refundBefore->order->order_number})" : ''));
            } catch (\Throwable) {}

            // Show appropriate message based on action
            $message = $request->status === 'approved' || $request->status === 'completed'
                ? 'Refund approved successfully. Money has been processed.'
                : ($request->status === 'rejected'
                    ? 'Refund rejected successfully. Wallet has been credited back.'
                    : 'Refund status updated successfully.');

            // Check if this is an AJAX request (returns JSON)
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'refund' => $refund
                ]);
            }

            // For regular form submissions, redirect back with message
            $fromPage = $request->input('from_page', $request->session()->get('refunds_list_page', 1));

            return redirect()->route('admin.refunds.index', ['page' => $fromPage])
                ->with('success', $message);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AdminRefundsController update failed', [
                'refund_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // JSON response for AJAX
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update refund: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to update refund: ' . $e->getMessage());
        }
    }
}

