<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Repositories\Eloquents\RefundRepository;
use App\Enums\PaymentType;
use App\Enums\WalletPointsDetail;
use App\Http\Traits\WalletPointsTrait;
use Illuminate\Support\Facades\Log;
use App\Notifications\ReturnStatusUpdated;
use App\Notifications\ReturnCredited;
use App\Notifications\ReturnRefundIssued;
use App\Models\User;
use App\Models\Refund;
use App\Enums\RequestEnum;
use App\Helpers\Helpers;
use App\Events\CreateRefundRequestEvent;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Returns", description="Product return requests management")
 */
class ReturnController extends Controller
{
    use WalletPointsTrait;

    /**
     * Display a listing of return requests.
     *
     * @OA\Get(
     *   path="/api/returns",
     *   tags={"Returns"},
     *   summary="List return requests",
     *   description="Get all return requests (consumers see only their own, admins see all)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="order_id",
     *     in="query",
     *     description="Filter by order ID",
     *     required=false,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *     name="paginate",
     *     in="query",
     *     description="Items per page",
     *     required=false,
     *     @OA\Schema(type="integer", default=20)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="List of returns",
     *     @OA\JsonContent(
     *       @OA\Property(property="data", type="array",
     *         @OA\Items(
     *           @OA\Property(property="id", type="integer", example=1),
     *           @OA\Property(property="order_id", type="integer", example=123),
     *           @OA\Property(property="product_id", type="integer", example=45),
     *           @OA\Property(property="user_id", type="integer", example=5),
     *           @OA\Property(property="return_reason", type="string", example="Defective item"),
     *           @OA\Property(property="status", type="string", example="pending"),
     *           @OA\Property(property="preferred_outcome", type="string", example="refund"),
     *           @OA\Property(property="created_at", type="string", format="date-time")
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request)
    {
        $query = \App\Models\ReturnRequest::query();

        $user = auth()->user();
        $canSeeAll = false;
        if ($user) {
            try {
                // Admins or users with return.index can see all
                if (method_exists($user, 'can') && $user->can('return.index')) {
                    $canSeeAll = true;
                }
                if (method_exists($user, 'hasRole')) {
                    if ($user->hasRole('admin') || $user->hasRole('administrator') || $user->hasRole('super_admin')) {
                        $canSeeAll = true;
                    }
                }
            } catch (\Throwable $e) {
                // If permission system not available, default to own records
                $canSeeAll = false;
            }
        }

        if (!$canSeeAll && $user) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('order_id')) {
            $query->where('order_id', $request->input('order_id'));
        }

        // Eager-load order and product with useful nested relations to avoid N+1 on the client
        $query->with([
            'order' => function ($q) {
                $q->select([
                    'id','order_number','payment_method','payment_status','amount','shipping_total','delivery_price',
                    'points_amount','wallet_balance','total','currency','currency_symbol','exchange_rate','delivery_description','created_at'
                ])
                ->with([
                    'order_status:id,name',
                    // include products with pivot so UI can compute unit price and qty
                    'products' => function ($p) {
                        $p->select(['products.id','products.name','products.sku','products.product_thumbnail_id'])
                          ->with(['product_thumbnail']);
                    },
                ]);
            },
            'product' => function ($q) {
                $q->select(['id','name','sku','product_thumbnail_id','price'])
                  // Load full media record to ensure Spatie UrlGenerator has required fields (e.g., disk)
                  ->with(['product_thumbnail']);
            },
            'user' => function ($u) {
                $u->select(['id','name','email','phone','country_code'])
                  ->with(['payment_account' => function ($pa) {
                      $pa->select(['id','user_id','bank_name','bank_holder_name','bank_account_no','swift','paypal_email']);
                  }]);
            },
        ]);

        $paginator = $query->latest('id')->paginate($request->paginate ?? 20);

        // Attach derived order_item info for the returned product (unit price, qty, subtotal)
        $paginator->getCollection()->transform(function ($r) {
            try {
                $order = $r->order;
                if ($order && $order->relationLoaded('products')) {
                    $line = $order->products->first(function ($p) use ($r) {
                        return (int) ($p->id ?? 0) === (int) $r->product_id
                               || (int) (optional($p->pivot)->product_id ?? 0) === (int) $r->product_id;
                    });
                    if ($line) {
                        $r->order_item = [
                            'product_id' => (int) $r->product_id,
                            'variation_id' => (int) (optional($line->pivot)->variation_id ?? 0) ?: null,
                            'quantity' => (int) (optional($line->pivot)->quantity ?? 0),
                            'single_price' => (float) (optional($line->pivot)->single_price ?? 0),
                            'shipping_cost' => (float) (optional($line->pivot)->shipping_cost ?? 0),
                            'subtotal' => (float) (optional($line->pivot)->subtotal ?? 0),
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // ignore mapping errors for robustness
            }
            return $r;
        });

        return $paginator;
    }

    /**
     * Store a newly created return request.
     *
     * @OA\Post(
     *   path="/api/returns",
     *   tags={"Returns"},
     *   summary="Create return request",
     *   description="Consumer creates a return request for an ordered product",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"order_id", "product_id", "return_reason", "description", "preferred_outcome", "product_not_used", "in_original_packaging", "include_all_accessories"},
     *       @OA\Property(property="order_id", type="integer", example=123),
     *       @OA\Property(property="product_id", type="integer", example=45),
     *       @OA\Property(property="return_reason", type="string", example="Defective item"),
     *       @OA\Property(property="sub_reason", type="string", example="Does not work as expected"),
     *       @OA\Property(property="description", type="string", example="The product stopped working after 2 days"),
     *       @OA\Property(property="preferred_outcome", type="string", enum={"refund", "replacement", "credit"}, example="refund"),
     *       @OA\Property(property="product_not_used", type="boolean", example=true),
     *       @OA\Property(property="in_original_packaging", type="boolean", example=true),
     *       @OA\Property(property="include_all_accessories", type="boolean", example=true)
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Return request created",
     *     @OA\JsonContent(
     *       @OA\Property(property="id", type="integer", example=1),
     *       @OA\Property(property="order_id", type="integer", example=123),
     *       @OA\Property(property="product_id", type="integer", example=45),
     *       @OA\Property(property="user_id", type="integer", example=5),
     *       @OA\Property(property="status", type="string", example="pending"),
     *       @OA\Property(property="created_at", type="string", format="date-time")
     *     )
     *   ),
     *   @OA\Response(response=409, description="Return already submitted for this item"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'product_id' => 'required|exists:products,id',
            'return_reason' => 'required|string',
            'sub_reason' => 'nullable|string',
            'description' => 'required|string',
            'preferred_outcome' => 'required|in:refund,replacement,credit',
            'product_not_used' => 'required|boolean',
            'in_original_packaging' => 'required|boolean',
            'include_all_accessories' => 'required|boolean',
        ]);

        $payload = array_merge($validated, [
            'user_id' => auth()->id(),
            'status' => 'pending',
        ]);

        // prevent duplicate submission for the same user/order/product
        $exists = \App\Models\ReturnRequest::where([
            'user_id' => auth()->id(),
            'order_id' => $validated['order_id'],
            'product_id' => $validated['product_id'],
        ])->exists();
        if ($exists) {
            return response()->json(['message' => 'Return already submitted for this item'], 409);
        }

        $return = \App\Models\ReturnRequest::create($payload);

        try {
            \Mail::raw(
                "New return request #{$return->id} on order #{$return->order_id} for product #{$return->product_id}. Preferred outcome: {$return->preferred_outcome}.",
                function ($m) use ($return) {
                    $m->to('admin@raines.africa')
                      ->subject("New Return Request #{$return->id}");
                }
            );
        } catch (\Throwable $e) {
            // log mail failure but do not block the flow
            \Log::warning('Return mail failed', ['error' => $e->getMessage()]);
        }

        return response()->json($return, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,pending'
        ]);

        $return = ReturnRequest::findOrFail($id);
        $return->status = $validated['status'];
        // persist rejection reason if present
        if ($validated['status'] === 'rejected' && $request->filled('rejection_reason')) {
            $return->rejection_reason = $request->input('rejection_reason');
        }
        $return->save();

        // On approval, process preferred outcome
        if ($return->status === 'approved') {
            // Determine item amount from order pivot
            $order = Order::with('products')->findOrFail($return->order_id);
            $pivot = null;
            $productName = null;
            foreach ($order->products as $p) {
                if ((int)$p->id === (int)$return->product_id || (int)$p->pivot->product_id === (int)$return->product_id) {
                    $pivot = $p->pivot;
                    $productName = $p->name ?? null;
                    break;
                }
            }
            $amount = $pivot->subtotal ?? 0;

            if (in_array($return->preferred_outcome, ['credit','wallet']) && $amount > 0) {
                // Credit user's wallet
                try {
                    $this->creditWallet($return->user_id, $amount, WalletPointsDetail::ADMIN_CREDIT);
                    // Notify user
                    if ($user = User::find($return->user_id)) {
                        $user->notify(new ReturnCredited($amount));
                        // Also send an email with product and converted amount in order currency
                        try {
                            $rate    = is_numeric($order->exchange_rate ?? null) ? (float)$order->exchange_rate : 1.0;
                            $symbol  = $order->currency_symbol ?? '$';
                            $currency= $order->currency ?? 'USD';
                            $converted = round($amount * $rate, 2);
                            $productLabel = $productName ?: ('Product ID '.$return->product_id);
                            $orderNo = $order->order_number ?? $order->id;
                            $formatted = number_format($converted, 2, '.', ',');
                            $body = "Hello {$user->name},\n\n".
                                    "Your wallet has been credited with {$symbol} {$formatted} ({$currency}) for the returned product: {$productLabel} from order #{$orderNo}.\n\n".
                                    "Thank you for shopping with us.";
                            \Mail::raw($body, function ($m) use ($user) {
                                $m->to($user->email)->subject('Wallet Credited for Your Return');
                            });
                        } catch (\Throwable $e) {
                            Log::warning('Return credit email failed', ['return_id' => $return->id, 'error' => $e->getMessage()]);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Wallet credit failed for return', ['return_id' => $return->id, 'error' => $e->getMessage()]);
                }
            } elseif ($return->preferred_outcome === 'refund' && $amount > 0) {
                // Create refund record (admin flow) and mark pivot refund_status pending
                try {
                    DB::beginTransaction();

                    $consumerId = $return->user_id; // requester of return is the consumer
                    $storeId = Helpers::getStoreIdByProductId($return->product_id);

                    $refund = Refund::create([
                        'product_id'   => $pivot->product_id,
                        'variation_id' => $pivot->variation_id,
                        'consumer_id'  => $consumerId,
                        'store_id'     => $storeId,
                        'order_id'     => $pivot->order_id,
                        'amount'       => $pivot->subtotal,
                        'quantity'     => $pivot->quantity,
                        'reason'       => $return->return_reason,
                        'payment_type' => PaymentType::BANK,
                    ]);

                    $refund->order_number = $order->order_number;

                    $order->products()->updateExistingPivot($return->product_id, [
                        'refund_status' => RequestEnum::PENDING,
                    ]);

                    event(new CreateRefundRequestEvent($refund));

                    DB::commit();
                    // Customer email is sent by UpdateRefundRequestNotification when admin approves the refund
                } catch (\Throwable $e) {
                    DB::rollBack();
                    Log::warning('Refund trigger failed for return', ['return_id' => $return->id, 'error' => $e->getMessage()]);
                }
            }
        }

        // Notify user about outcome
        try {
            $user = User::find($return->user_id);
            if ($user) {
                $user->notify(new ReturnStatusUpdated($return->fresh()));
            }
        } catch (\Throwable $e) {
            Log::warning('Return notification failed', ['return_id' => $return->id, 'error' => $e->getMessage()]);
        }

        // Return consistent structure with 'data' wrapper for react-admin compatibility
        $freshReturn = $return->fresh();
        return response()->json(['data' => $freshReturn], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
