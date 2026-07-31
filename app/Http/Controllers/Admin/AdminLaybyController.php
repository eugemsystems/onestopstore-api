<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WalletPointsDetail;
use App\Http\Controllers\Controller;
use App\Http\Traits\WalletPointsTrait;
use App\Models\LaybyApplication;
use App\Models\LaybyPayment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminLaybyController extends BaseAdminController
{
    use WalletPointsTrait;

    protected string $permissionPrefix = 'layby';

    /**
     * Display all layby applications
     */
    public function index(Request $request)
    {
        $this->checkPermission('index');

        $status = $request->get('status', 'all');
        $search = $request->get('search', '');

        $query = LaybyApplication::with(['user', 'product.product_thumbnail', 'variation'])
            ->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Add search functionality by user name, surname (name field), and email
        if (!empty($search)) {
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $applications = $query->paginate(20);

        // Get counts for each status
        $statusCounts = [
            'all'       => LaybyApplication::count(),
            'pending'   => LaybyApplication::where('status', 'pending')->count(),
            'approved'  => LaybyApplication::where('status', 'approved')->count(),
            'active'    => LaybyApplication::where('status', 'active')->count(),
            'completed' => LaybyApplication::where('status', 'completed')->count(),
            'rejected'  => LaybyApplication::where('status', 'rejected')->count(),
            'cancelled' => LaybyApplication::where('status', 'cancelled')->count(),
            // Applications that exhausted all reminders and still have no ID document
            'escalated_no_document' => LaybyApplication::whereIn('status', ['pending', 'under_review'])
                ->whereNull('id_document_attachment_id')
                ->where(function ($q) {
                    $q->whereNull('id_document_path')->orWhere('id_document_path', '');
                })
                ->where('id_document_reminder_count', '>=', 5)
                ->count(),
        ];

        $statuses = ['all', 'pending', 'approved', 'rejected', 'active', 'completed', 'cancelled'];

        return view('admin.layby.index', compact('applications', 'statuses', 'status', 'statusCounts', 'search'));
    }

    /**
     * Show single application
     */
    public function show($id)
    {
        $this->checkPermission('show');

        $application = LaybyApplication::with([
            'user',
            'product.product_thumbnail',
            'variation',
            'order', // Add order relationship
            'idDocumentAttachment', // Add document attachment relationship (camelCase)
            'payments' => function($q) {
                $q->orderBy('created_at', 'desc');
            },
            'payments.capturedBy'
        ])->findOrFail($id);

        // Get user's order count
        $userOrderCount = Order::where('consumer_id', $application->user_id)
            ->whereNotNull('order_number')
            ->count();

        // Determine if product was on sale when application was created
        $isSaleProduct = $application->product && $application->product->sale_price > 0;

        // Get appropriate deposit percentage
        $depositPercent = $isSaleProduct
            ? (int)getLaybySetting('sale_products_deposit_percentage', 30)
            : (int)getLaybySetting('regular_products_deposit_percentage', 30);

        return view('admin.layby.show', compact('application', 'userOrderCount', 'depositPercent'));
    }

    /**
     * Update application status
     */
    public function updateStatus(Request $request, $id)
    {
        $this->checkPermission('update-status');

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string',
        ]);

        $application = LaybyApplication::findOrFail($id);

        DB::beginTransaction();
        try {
            $application->status = $validated['status'];

            if ($validated['status'] === 'approved') {
                $application->approved_at = now();
                $application->approved_by = Auth::id();
                $application->rejection_reason = null;

                // Send approval email
                try {
                    Mail::send('emails.layby.approved', [
                        'application' => $application,
                        'lang' => 'en'
                    ], function ($message) use ($application) {
                        $message->to($application->user->email, $application->user->name)
                                ->subject('Layby Application Approved - ' . $application->application_number);
                    });
                } catch (\Exception $e) {
                    Log::error('Failed to send layby approval email', [
                        'application_id' => $application->id,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                // Rejection
                $application->rejected_at = now();
                $application->rejection_reason = $validated['rejection_reason'];

                // Send rejection email
                try {
                    Mail::send('emails.layby.rejected', [
                        'application' => $application,
                        'lang' => 'en'
                    ], function ($message) use ($application) {
                        $message->to($application->user->email, $application->user->name)
                                ->subject('Layby Application Update - ' . $application->application_number);
                    });
                } catch (\Exception $e) {
                    Log::error('Failed to send layby rejection email', [
                        'application_id' => $application->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $application->save();

            DB::commit();

            return redirect()->back()->with('success', 'Application ' . $validated['status'] . ' successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update status: ' . $e->getMessage());
        }
    }

    /**
     * Capture payment for layby
     */
    public function capturePayment(Request $request, $id)
    {
        $this->checkPermission('capture-payment');

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'transaction_id' => 'nullable|string',
            'payment_method' => 'nullable|string',
        ]);

        $application = LaybyApplication::findOrFail($id);

        if (!in_array($application->status, ['approved', 'active', 'pending'])) {
            return redirect()->back()->with('error', 'Cannot add payment to this application');
        }

        if ($validated['amount'] > $application->balance_remaining) {
            return redirect()->back()->with('error', 'Payment amount exceeds balance remaining');
        }

        DB::beginTransaction();
        try {
            // Create payment record
            $payment = $application->payments()->create([
                'amount' => $validated['amount'],
                'currency' => $application->currency,
                'payment_method' => $validated['payment_method'] ?? 'manual',  // Default to 'manual' if not provided
                'transaction_id' => $validated['transaction_id'] ?? null,
                'payment_status' => 'completed',
                'payment_note' => $validated['payment_note'] ?? null,
                'paid_at' => now(),
                'captured_by' => Auth::id(),
            ]);

            // Update application
            $application->total_paid += $validated['amount'];
            $application->balance_remaining = $application->total_amount - $application->total_paid;
            $application->last_payment_at = now();

            // Update status
            if ($application->status === 'pending' || $application->status === 'approved') {
                $application->status = 'active';
            }

            // Check if completed
            $orderCreationError = null;
            if ($application->balance_remaining <= 0.01) { // Account for rounding
                $application->status = 'completed';
                $application->completed_at = now();

                // Create order for completed layby
                try {
                    $createdOrder = \App\Http\Controllers\LaybyController::createOrderForLayby($application);
                    $application->order_id = $createdOrder->id;
                } catch (\Exception $e) {
                    Log::error('Failed to create order for layby (admin capture)', [
                        'layby_id' => $application->id,
                        'error' => $e->getMessage()
                    ]);
                    $orderCreationError = $e->getMessage();
                }

                // Send completion email
                try {
                    Mail::send('emails.layby.completed', [
                        'application' => $application->fresh(['order', 'user']),
                        'order' => $application->order ?? null,
                        'lang' => 'en'
                    ], function ($message) use ($application) {
                        $message->to($application->user->email, $application->user->name)
                                ->subject('🎉 Layby Completed - ' . $application->application_number);
                    });
                } catch (\Exception $e) {
                    Log::error('Failed to send layby completion email (admin capture)', [
                        'application_id' => $application->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $application->save();

            // Send payment received email
            try {
                Mail::send('emails.layby.payment-received', [
                    'application' => $application->fresh(['user']),
                    'payment' => $payment,
                    'lang' => 'en'
                ], function ($message) use ($application) {
                    $message->to($application->user->email, $application->user->name)
                            ->subject('Payment Received - ' . $application->application_number);
                });
            } catch (\Exception $e) {
                Log::error('Failed to send payment received email (admin capture)', [
                    'application_id' => $application->id,
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage()
                ]);
            }

            DB::commit();

            if ($orderCreationError) {
                return redirect()->back()
                    ->with('success', 'Payment captured successfully — layby marked as completed.')
                    ->with('warning', 'Order could not be created automatically: ' . $orderCreationError . ' Once resolved, use the "Create Order" button on this layby.');
            }

            return redirect()->back()->with('success', 'Payment captured successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to capture payment: ' . $e->getMessage());
        }
    }

    /**
     * Delete payment
     */
    public function deletePayment($applicationId, $paymentId)
    {
        $application = LaybyApplication::findOrFail($applicationId);
        $payment = LaybyPayment::where('layby_application_id', $applicationId)
            ->findOrFail($paymentId);

        if ($payment->payment_status !== 'completed') {
            return redirect()->back()->with('error', 'Can only delete completed payments');
        }

        DB::beginTransaction();
        try {
            // Reverse the payment
            $application->total_paid -= $payment->amount;
            $application->balance_remaining = $application->total_amount - $application->total_paid;

            // Update status if was completed
            if ($application->status === 'completed') {
                $application->status = 'active';
                $application->completed_at = null;
            }

            $application->save();
            $payment->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Payment deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to delete payment: ' . $e->getMessage());
        }
    }

    /**
     * Edit payment
     */
    public function editPayment(Request $request, $applicationId, $paymentId)
    {
        $this->checkPermission('edit-payment');

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_note' => 'nullable|string',
            'transaction_id' => 'nullable|string',
        ]);

        $application = LaybyApplication::findOrFail($applicationId);
        $payment = LaybyPayment::where('layby_application_id', $applicationId)
            ->findOrFail($paymentId);

        if ($payment->payment_status !== 'completed') {
            return redirect()->back()->with('error', 'Can only edit completed payments');
        }

        $oldAmount = $payment->amount;
        $newAmount = $validated['amount'];
        $amountDifference = $newAmount - $oldAmount;

        DB::beginTransaction();
        try {
            // Update payment
            $payment->update([
                'amount' => $newAmount,
                'payment_method' => $validated['payment_method'],
                'transaction_id' => $validated['transaction_id'] ?? $payment->transaction_id,
                'payment_note' => $validated['payment_note'] ?? $payment->payment_note,
            ]);

            // Update application totals
            $application->total_paid += $amountDifference;
            $application->balance_remaining = $application->total_amount - $application->total_paid;

            // Check completion status
            // Check if fully paid
            if ($application->balance_remaining <= 0.01) {
                $application->status = 'completed';
                $application->completed_at = now();

                // Send completion email
                Mail::send('emails.layby.completed', [
                    'application' => $application,
                    'lang' => 'en'
                ], function ($message) use ($application) {
                    $message->to($application->user->email, $application->user->name)
                            ->subject('🎉 Layby Completed - ' . $application->application_number);
                });
            } elseif ($application->status === 'approved') {
                $application->status = 'active';
            }

            $application->save();

            // Send payment received email
            Mail::send('emails.layby.payment-received', [
                'application' => $application,
                'payment' => $payment,
                'lang' => 'en'
            ], function ($message) use ($application) {
                $message->to($application->user->email, $application->user->name)
                        ->subject('Payment Received - ' . $application->application_number);
            });

            DB::commit();

            return redirect()->back()->with('success', 'Payment captured successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to capture payment: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a layby application
     */
    public function cancel(Request $request, $id)
    {
        $this->checkPermission('update-status');

        $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        try {
            $application = LaybyApplication::with(['user', 'product', 'variation'])->findOrFail($id);

            // Check if layby can be cancelled - only completed laybys cannot be cancelled
            if ($application->status === 'completed') {
                return redirect()->back()->with('error', 'Cannot cancel a completed layby application.');
            }

            DB::beginTransaction();

            // Update status to cancelled
            $application->status = 'cancelled';
            $application->cancellation_reason = $request->cancellation_reason;
            $application->cancelled_at = now();
            $application->cancelled_by = Auth::id();
            $application->save();

            // If there were payments made, credit the amount back to user's wallet
            if ($application->total_paid > 0) {
                // Credit wallet
                try {
                    $this->creditWallet(
                        $application->user_id,
                        $application->total_paid,
                        WalletPointsDetail::REFUND,
                        "Layby cancellation refund for application #{$application->application_number}"
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to credit wallet for cancelled layby', [
                        'application_id' => $application->id,
                        'user_id' => $application->user_id,
                        'amount' => $application->total_paid,
                        'error' => $e->getMessage()
                    ]);
                    // Continue with cancellation even if wallet credit fails
                }
            }

            // Send cancellation email to user
            $this->sendCancellationEmail($application, $request->cancellation_reason);

            DB::commit();

            return redirect()->back()->with('success', 'Layby application cancelled successfully. An email has been sent to the customer.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to cancel layby application', [
                'application_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Failed to cancel layby application: ' . $e->getMessage());
        }
    }

    /**
     * Send cancellation email to user
     */
    private function sendCancellationEmail(LaybyApplication $application, string $reason)
    {
        try {
            Mail::send('emails.layby-cancelled', [
                'application' => $application,
                'reason' => $reason,
                'user' => $application->user,
            ], function ($message) use ($application) {
                $message->to($application->user->email, $application->user->name)
                        ->subject('Layby Application Cancelled - ' . $application->application_number);
            });
        } catch (\Exception $e) {
            Log::error('Failed to send layby cancellation email', [
                'application_id' => $application->id,
                'user_email' => $application->user->email,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if order can be created for this layby
     * Returns eligibility status and any error messages
     */
    public function checkOrderCreationEligibility($id)
    {
        try {
            $application = LaybyApplication::with(['user', 'order'])->findOrFail($id);

            $errors = [];
            $eligible = true;

            // Check 1: Must be completed
            if ($application->status !== 'completed') {
                $errors[] = "Layby status must be 'completed'. Current status: {$application->status}";
                $eligible = false;
            }

            // Check 2: Balance must be zero or negative
            if ($application->balance_remaining > 0.01) {
                $errors[] = "Outstanding balance: {$application->currency_symbol}" . number_format($application->balance_remaining, 2);
                $eligible = false;
            }

            // Check 3: Order must not already exist
            if ($application->order_id) {
                $errors[] = "Order already exists (Order #{$application->order->order_number})";
                $eligible = false;
            }

            // Check 4: User must have at least one address (or we'll create one)
            $hasAddress = $application->user->address()->exists();
            $addressWarning = null;

            if (!$hasAddress) {
                // This is just a warning, we'll auto-create the address
                $addressWarning = "User has no address configured. A default address will be created automatically.";
            }

            return response()->json([
                'eligible' => $eligible,
                'errors' => $errors,
                'warning' => $addressWarning,
                'application' => [
                    'id' => $application->id,
                    'application_number' => $application->application_number,
                    'status' => $application->status,
                    'total_amount' => $application->total_amount,
                    'total_paid' => $application->total_paid,
                    'balance_remaining' => $application->balance_remaining,
                    'order_id' => $application->order_id,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'eligible' => false,
                'errors' => ['Failed to check eligibility: ' . $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Manually create order for completed layby
     * This is used when automatic order creation failed
     */
    public function manuallyCreateOrder($id)
    {
        $this->checkPermission('capture-payment');

        try {
            $application = LaybyApplication::with(['user', 'order'])->findOrFail($id);

            // Validate eligibility
            if ($application->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Layby must be completed to create an order'
                ], 422);
            }

            if ($application->balance_remaining > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'Outstanding balance must be paid before creating order'
                ], 422);
            }

            if ($application->order_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order already exists for this layby'
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Create order using the LaybyController method
                $order = \App\Http\Controllers\LaybyController::createOrderForLayby($application);

                // Link order to layby
                $application->order_id = $order->id;
                $application->save();

                DB::commit();

                // Send completion email
                try {
                    Mail::send('emails.layby.completed', [
                        'application' => $application->fresh(['order', 'user']),
                        'order' => $order,
                        'lang' => 'en'
                    ], function ($message) use ($application) {
                        $message->to($application->user->email, $application->user->name)
                                ->subject('🎉 Layby Completed - ' . $application->application_number);
                    });
                } catch (\Exception $e) {
                    Log::error('Failed to send layby completion email (manual order creation)', [
                        'application_id' => $application->id,
                        'error' => $e->getMessage()
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Order created successfully!',
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                    ]
                ]);

            } catch (\Throwable $e) {
                DB::rollBack();

                Log::error('Failed to manually create order for layby', [
                    'layby_id' => $application->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create order: ' . $e->getMessage()
                ], 500);
            }

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}

