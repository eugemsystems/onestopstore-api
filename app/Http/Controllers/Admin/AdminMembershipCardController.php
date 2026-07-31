<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Enums\WalletPointsDetail;
use App\Http\Traits\WalletPointsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminMembershipCardController extends Controller
{
    use WalletPointsTrait;

    public function __construct()
    {
        // Permission middleware
        $this->middleware(function ($request, $next) {
            $action = $request->route()->getActionMethod();

            $permissionMap = [
                'index' => 'membership_card.view',
                'scanCard' => 'membership_card.scan',
                'assignCard' => 'membership_card.assign',
                'addPoints' => 'membership_card.manage_points',
                'addWallet' => 'membership_card.manage_wallet',
                'searchUser' => 'membership_card.view',
                'getUserOrders' => 'membership_card.scan',
                'awardOrderPoints' => 'membership_card.manage_points',
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
     * Show membership card scanner page
     */
    public function index()
    {
        return view('admin.membership-cards.scanner');
    }

    /**
     * Scan membership card and get user details
     */
    public function scanCard(Request $request)
    {
        try {
            $request->validate([
                'card_number' => 'required|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            // Trim and normalize card number
            $cardNumber = trim($request->card_number);

            // Find user by membership card number (case-insensitive and trimmed)
            $user = User::whereRaw('LOWER(TRIM(membership_card_number)) = ?', [strtolower($cardNumber)])
                ->with(['wallet', 'points'])
                ->first();

            // If not found, also try exact match as fallback
            if (!$user) {
                $user = User::where('membership_card_number', $cardNumber)
                    ->with(['wallet', 'points'])
                    ->first();
            }

            if (!$user) {
                Log::warning("No user found for card", [
                    'card_number' => $cardNumber,
                    'searched_lowercase' => strtolower($cardNumber),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'No user found with this membership card number.',
                    'card_number' => $cardNumber,
                    'card_available' => true, // Card can be assigned to someone
                ], 404);
            }

            // Get user points balance
            $pointsBalance = 0;
            if ($user->points) {
                $pointsBalance = $user->points->balance ?? 0;
            }

            // Get wallet balance
            $walletBalance = 0;
            if ($user->wallet) {
                $walletBalance = $user->wallet->balance ?? 0;
            }

            return response()->json([
                'success' => true,
                'message' => 'User found!',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? 'N/A',
                    'membership_card_number' => $user->membership_card_number,
                    'card_assigned_at' => $user->card_assigned_at ?
                        ($user->card_assigned_at instanceof \Carbon\Carbon ?
                            $user->card_assigned_at->format('Y-m-d H:i:s') :
                            $user->card_assigned_at) :
                        null,
                    'points_balance' => $pointsBalance,
                    'wallet_balance' => $walletBalance,
                    'created_at' => $user->created_at instanceof \Carbon\Carbon ?
                        $user->created_at->format('Y-m-d H:i:s') :
                        $user->created_at,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error("Error scanning membership card", [
                'card_number' => $request->card_number ?? 'N/A',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'scanned_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error scanning card: ' . $e->getMessage(),
                'error_details' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null,
            ], 500);
        } catch (\Throwable $e) {
            Log::critical("Critical error scanning membership card", [
                'card_number' => $request->card_number ?? 'N/A',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'scanned_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Critical error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign membership card to a user
     */
    public function assignCard(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'card_number' => 'required|string',
        ]);

        try {
            $cardNumber = trim($request->card_number);
            $userId = $request->user_id;

            // Check if card is already assigned to another user
            $existingUser = User::where('membership_card_number', $cardNumber)
                ->where('id', '!=', $userId)
                ->first();

            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => "This card is already assigned to {$existingUser->name} ({$existingUser->email})",
                ], 400);
            }

            // Assign card to user
            $user = User::findOrFail($userId);
            $user->membership_card_number = $cardNumber;
            $user->card_assigned_at = now();
            $user->save();

            return response()->json([
                'success' => true,
                'message' => "Card {$cardNumber} successfully assigned to {$user->name}",
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'membership_card_number' => $user->membership_card_number,
                    'card_assigned_at' => $user->card_assigned_at->format('Y-m-d H:i:s'),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error("Error assigning membership card", [
                'card_number' => $request->card_number,
                'user_id' => $request->user_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error assigning card: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add points to user
     */
    public function addPoints(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'points' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            $points = $request->points;
            $reason = $request->reason ?? 'Manual points addition via membership card scanner';

            // Add points using the points system
            $user->addPoints($points, $reason);

            // Get updated balance
            $newBalance = $user->points->balance ?? 0;

            return response()->json([
                'success' => true,
                'message' => "{$points} points added successfully!",
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'points_balance' => $newBalance,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error("Error adding points via membership card", [
                'user_id' => $request->user_id,
                'points' => $request->points,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error adding points: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add money to wallet
     */
    public function addWallet(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            $userId = $request->user_id;
            $amount = $request->amount;
            $reason = $request->reason ?? 'Manual wallet top-up via membership card scanner';

            // Add to wallet using WalletPointsTrait
            $this->creditWallet($userId, $amount, $reason);


            // Get updated balance
            $user = $user->fresh(['wallet']); // Refresh user with wallet relationship
            $newBalance = $user->wallet->balance ?? 0;

            return response()->json([
                'success' => true,
                'message' => '$' . number_format($amount, 2) . " added to wallet successfully!",
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'wallet_balance' => $newBalance,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error("Error adding to wallet via membership card", [
                'user_id' => $request->user_id,
                'amount' => $request->amount,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error adding to wallet: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search for users to assign card
     */
    public function searchUser(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:2',
        ]);

        try {
            $search = $request->search;

            $users = User::where(function($query) use ($search) {
                    $query->where('name', 'LIKE', "%{$search}%")
                          ->orWhere('email', 'LIKE', "%{$search}%")
                          ->orWhere('phone', 'LIKE', "%{$search}%");
                })
                ->limit(10)
                ->get(['id', 'name', 'email', 'phone', 'membership_card_number']);

            return response()->json([
                'success' => true,
                'users' => $users->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone ?? 'N/A',
                        'has_card' => !empty($user->membership_card_number),
                        'card_number' => $user->membership_card_number ?? null,
                    ];
                }),
            ]);

        } catch (\Exception $e) {
            Log::error("Error searching users for card assignment", [
                'search' => $request->search,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error searching users: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recent orders for a user
     */
    public function getUserOrders(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            $userId = $request->user_id;

            // Get status IDs for 'ready for collection' and 'collected'
            $allowedStatusIds = OrderStatus::whereIn('name', ['ready for collection', 'collected'])
                ->pluck('id')
                ->toArray();

            // Get user's recent orders (limit to last 20)
            // Only show orders that are ready for collection or collected
            $orders = Order::where('consumer_id', $userId)
                ->whereNull('parent_id') // Only get parent orders, not sub-orders
                ->whereIn('order_status_id', $allowedStatusIds) // Filter by status
                ->with(['order_status', 'products'])
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            $orderData = $orders->map(function($order) {
                // Check if points have been awarded for this order
                // Points transactions are stored in 'transactions' table with point_id
                $pointsAwarded = DB::table('transactions')
                    ->whereNotNull('point_id')
                    ->where('detail', 'LIKE', '%Order #' . $order->order_number . '%')
                    ->where('type', 'credit')
                    ->exists();

                // Calculate 1% of order total as cashback in currency
                $orderTotal = (float) $order->total;
                $cashbackAmount = $orderTotal * 0.01; // 1% of order value in dollars

                // Convert the cashback amount to points using the point_currency_ratio setting
                // For example: $90.08 cashback × 100 ratio = 9008 points
                $pointsToDisplay = $this->currencyToPoints($cashbackAmount);

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total' => number_format($orderTotal, 2),
                    'total_raw' => $orderTotal,
                    'status' => $order->order_status->name ?? 'Unknown',
                    'status_id' => $order->order_status_id,
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                    'created_at_human' => $order->created_at->diffForHumans(),
                    'points_awarded' => $pointsAwarded,
                    'calculated_points' => round($pointsToDisplay, 2),
                    'product_count' => $order->products->count(),
                ];
            });

            return response()->json([
                'success' => true,
                'orders' => $orderData,
                'total_orders' => $orderData->count(),
            ]);

        } catch (\Exception $e) {
            Log::error("Error getting user orders", [
                'user_id' => $request->user_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading orders: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Award points based on order value (1% of order total)
     */
    public function awardOrderPoints(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'order_id' => 'required|exists:orders,id',
        ]);

        try {
            $userId = $request->user_id;
            $orderId = $request->order_id;

            // Get the order
            $order = Order::with('order_status')->findOrFail($orderId);

            // Verify order belongs to user
            if ($order->consumer_id != $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order does not belong to this user.',
                ], 400);
            }

            // Check if points have already been awarded for this order
            // Points transactions are stored in 'transactions' table with point_id
            $alreadyAwarded = DB::table('transactions')
                ->whereNotNull('point_id')
                ->where('detail', 'LIKE', '%Order #' . $order->order_number . '%')
                ->where('type', 'credit')
                ->exists();

            if ($alreadyAwarded) {
                return response()->json([
                    'success' => false,
                    'message' => 'Points have already been awarded for this order.',
                ], 400);
            }

            // Calculate 1% of order total as cashback in currency
            $orderTotal = (float) $order->total;
            $cashbackAmount = $orderTotal * 0.01; // 1% of order value in dollars

            // Convert the cashback amount to points using the point_currency_ratio setting
            // For example: $90.07 cashback × 100 ratio = 9007 points
            $pointsToAward = $this->currencyToPoints($cashbackAmount);
            $pointsToAward = round($pointsToAward, 2);

            if ($pointsToAward <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot award points for this order (calculated points: 0).',
                ], 400);
            }

            // Award points using WalletPointsTrait
            $detail = 'Reward Points for Order #' . $order->order_number . ' (1% of $' . number_format($orderTotal, 2) . ' = $' . number_format($cashbackAmount, 2) . ' cashback)';
            $this->creditPoints($userId, $pointsToAward, $detail);

            // Get updated user points balance
            $user = User::with('points')->findOrFail($userId);
            $newBalance = $user->points->balance ?? 0;

            return response()->json([
                'success' => true,
                'message' => number_format($pointsToAward, 2) . ' points awarded successfully for Order #' . $order->order_number . '!',
                'points_awarded' => $pointsToAward,
                'order_number' => $order->order_number,
                'order_total' => number_format($orderTotal, 2),
                'new_balance' => $newBalance,
            ]);

        } catch (\Exception $e) {
            Log::error("Error awarding order points", [
                'user_id' => $request->user_id,
                'order_id' => $request->order_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error awarding points: ' . $e->getMessage(),
            ], 500);
        }
    }
}
