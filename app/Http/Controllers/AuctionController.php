<?php

namespace App\Http\Controllers;

use App\Models\AuctionItem;
use App\Models\AuctionBid;
use App\Models\AuctionAutoBid;
use App\Models\AuctionBidDeposit;
use App\Models\AuctionDepositRefund;
use App\Models\AuctionSetting;
use App\Models\Setting;
use App\Helpers\Helpers;
use App\Mail\AuctionOutbidMail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AuctionController extends Controller
{
    // ── Public: list auctions ────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        // Skip cache for search queries — too many unique keys
        $hasSearch = $request->filled('search');
        $cacheKey  = 'auctions_index_' . md5(json_encode([
            'tab'    => $request->get('tab', 'active'),
            'branch' => $request->get('branch', ''),
            'page'   => $request->get('page', 1),
        ]));

        if (!$hasSearch && Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }

        $query = AuctionItem::with(['winner:id,name'])
            ->where('status', '!=', 'draft');

        // Filter by status tab
        $tab = $request->get('tab', 'active');
        if ($tab === 'active') {
            $query->where('status', 'active')
                  ->where('starts_at', '<=', now())
                  ->where('ends_at', '>', now());
        } elseif ($tab === 'upcoming') {
            $query->where('status', 'active')->where('starts_at', '>', now());
        } elseif ($tab === 'ended') {
            $query->where('status', 'ended');
        }

        // Search
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ILIKE', $search)
                  ->orWhere('description', 'ILIKE', $search);
            });
        }

        // Branch filter
        if ($request->filled('branch')) {
            $query->where('branch', $request->branch);
        }

        $auctions = $query->orderBy('sort_order', 'asc')
                          ->orderByRaw("CASE WHEN status='active' AND starts_at <= NOW() AND ends_at > NOW() THEN 0 WHEN status='active' AND starts_at > NOW() THEN 1 ELSE 2 END")
                          ->orderBy('ends_at')
                          ->paginate(12);

        // Append helpers to each item
        $auctions->getCollection()->transform(function ($item) {
            $item->thumbnail = $item->thumbnail;
            $item->time_remaining_seconds = $item->timeRemainingSeconds();
            $item->minimum_next_bid = $item->minimumNextBid();
            $item->is_active = $item->isActive();
            $item->is_upcoming = $item->isUpcoming();
            $item->is_ended = $item->isEnded();
            return $item;
        });

        $payload = $auctions->toArray();

        if (!$hasSearch) {
            Cache::put($cacheKey, $payload, now()->addSeconds(10));
        }

        return response()->json($payload);
    }

    // ── Public: single auction detail ────────────────────────────

    public function show(Request $request, int $id): JsonResponse
    {
        $item = AuctionItem::with([
            'product:id,name,slug,description,unit,short_description,specifications,price,sale_price,stock_status,status,sku',
            'product.product_thumbnail',
            'product.product_galleries',
            'winner:id,name',
        ])
            ->where('status', '!=', 'draft')
            ->findOrFail($id);

        $item->thumbnail              = $item->thumbnail;
        $item->time_remaining_seconds = $item->timeRemainingSeconds();
        $item->minimum_next_bid       = $item->minimumNextBid();
        $item->is_active              = $item->isActive();
        $item->is_upcoming            = $item->isUpcoming();
        $item->is_ended               = $item->isEnded();

        // Fix product data for the auction detail page
        if ($item->product) {
            $item->product->makeVisible(['description', 'short_description', 'specifications']);

            $resolveUrl = fn($att) => $att?->image_url
                ?? $att?->original_url
                ?? $att?->file_name
                ?? null;

            $thumbnailUrl = $resolveUrl($item->product->product_thumbnail);

            $galleryUrls = collect($item->product->product_galleries ?? [])
                ->map(fn($att) => $resolveUrl($att))
                ->filter()
                ->values()
                ->all();

            $item->product->image_urls = collect(array_filter(array_merge(
                $thumbnailUrl ? [$thumbnailUrl] : [],
                $galleryUrls,
            )))->unique()->values()->all();
        }

        // ── Augment response for the authenticated winner ────────────────
        // Appends winner_bid, deposit_paid, and delivery_options so the pay
        // page and MyAuctions modal can render an accurate breakdown.
        //
        // NOTE: This route is public (no auth:sanctum middleware), so
        // $request->user() is always null even when a valid Bearer token is
        // sent. We must manually resolve the user from the token so that the
        // deposit deduction block runs for the authenticated winner.
        $user = $request->user();
        if (!$user && $request->bearerToken()) {
            $pat  = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken());
            $user = $pat?->tokenable;
        }
        if ($user && $item->winner_id == $user->id) {
            $deposit = AuctionBidDeposit::where('user_id', $user->id)
                ->where('auction_item_id', $id)
                ->whereNotNull('paid_at')
                ->first();

            $auctionSettings = AuctionSetting::current();

            $item->winner_bid   = (float) $item->winner_bid;
            $item->deposit_paid = $deposit ? (float) $deposit->amount : 0.0;
            $item->delivery_options = [
                'collection' => ['label' => 'Free Collection', 'cost' => 0],
                'delivery'   => [
                    'label'     => 'Home Delivery',
                    'cost'      => (float) $auctionSettings->delivery_price,
                    'enabled'   => (bool) $auctionSettings->delivery_enabled,
                    'radius_km' => (int) $auctionSettings->delivery_radius_km,
                ],
            ];
        }

        return response()->json($item);
    }

    // ── Public: recent bid history for a listing ─────────────────

    public function bids(int $id): JsonResponse
    {
        $item = AuctionItem::where('status', '!=', 'draft')->findOrFail($id);

        $bids = AuctionBid::with('user:id,name')
            ->where('auction_item_id', $id)
            ->latest()
            ->limit(30)
            ->get()
            ->map(function ($bid) {
                $firstName = $bid->user ? explode(' ', trim($bid->user->name))[0] : 'Bidder';
                $userId = $bid->user->id ?? 0;
                return [
                    'id'         => $bid->id,
                    'amount'     => $bid->amount,
                    'bidder'     => $firstName . ' #' . $userId,
                    'user_id'    => $userId,
                    'created_at' => $bid->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'bids'            => $bids,
            'current_bid'     => $item->current_bid,
            'bid_count'       => $item->bid_count,
            'minimum_next_bid'=> $item->minimumNextBid(),
            'time_remaining_seconds' => $item->timeRemainingSeconds(),
            'status'          => $item->status,
            'winner'          => $item->winner ? ['id' => $item->winner->id, 'name' => $item->winner->name] : null,
        ]);
    }

    // ── Public: Server-Sent Events stream for live bid updates ────
    // Client connects once; server pushes every 5 s until done.

    public function stream(int $id): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->stream(function () use ($id) {
            // Disable output buffering so events are flushed immediately
            if (ob_get_level()) ob_end_clean();

            $startedAt   = time();
            $maxDuration = 55; // seconds — keep under typical 60s proxy timeout
            $interval    = 5;  // push every 5 seconds

            while (true) {
                // Stop if client disconnected or we've streamed long enough
                if (connection_aborted() || (time() - $startedAt) >= $maxDuration) {
                    break;
                }

                // Fetch fresh state (cheap query — no eager loads)
                $item = AuctionItem::select([
                    'id', 'current_bid', 'bid_count', 'ends_at', 'status', 'winner_id',
                    'starting_price', 'minimum_bid_increment', 'reserve_price',
                ])->find($id);

                if (!$item) break;

                $payload = json_encode([
                    'current_bid'            => $item->current_bid,
                    'bid_count'              => $item->bid_count,
                    'minimum_next_bid'       => $item->minimumNextBid(),
                    'time_remaining_seconds' => $item->timeRemainingSeconds(),
                    'status'                 => $item->status,
                    'winner'                 => $item->winner
                        ? ['id' => $item->winner->id, 'name' => $item->winner->name]
                        : null,
                ]);

                echo "data: {$payload}\n\n";
                flush();

                // If auction has ended, send one final event then close
                if ($item->status === 'ended' || $item->timeRemainingSeconds() <= 0) {
                    break;
                }

                sleep($interval);
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',   // tell nginx not to buffer
            'Connection'        => 'keep-alive',
        ]);
    }

    // ── Auth: bid requirements for a listing ─────────────────────
    // Returns what the current user needs to do before they can bid.

    public function bidRequirements(Request $request, int $id): JsonResponse
    {
        AuctionItem::where('status', '!=', 'draft')->findOrFail($id);

        $settings = AuctionSetting::current();
        $user     = $request->user();

        // Email verification gate removed — all verified users can bid freely
        $emailVerified = true;
        $depositPaid   = false;

        if ($settings->bid_fee_enabled) {
            $deposit = AuctionBidDeposit::where('user_id', $user->id)
                ->where('auction_item_id', $id)
                ->first();

            if ($deposit) {
                if ($deposit->paid_at) {
                    // Fast path: already confirmed
                    $depositPaid = true;
                } elseif ($deposit->order_id) {
                    // Fallback: check the linked order's payment status
                    // (covers the window between gateway redirect and ITN webhook)
                    $order = \App\Models\Order::withTempLaybyOrders()->find($deposit->order_id);
                    if ($order) {
                        $isPaid = in_array(strtolower((string) $order->payment_status), [
                            'paid', 'complete', 'completed', 'success', 'approved', 'captured', '1', 'true',
                        ]);
                        if ($isPaid) {
                            // Eagerly set paid_at so future calls skip this check
                            $deposit->update(['paid_at' => now()]);
                            $depositPaid = true;
                        }
                    }
                }
            }
        }

        // Check for auction bid ban
        $activeBan   = \App\Models\AuctionBan::getActiveBan($user->id);
        $isBanned    = $activeBan !== null;
        $banMessage  = $isBanned
            ? 'Your account has been restricted from bidding and payments due to non-payment of a previously won auction item: "' . ($activeBan->auctionItem->title ?? 'Unknown') . '". Please contact support to resolve this ban.'
            : null;

        // Wallet balance for deposit payment option
        $walletBalance = $user->wallet ? (float) $user->wallet->balance : 0;

        return response()->json([
            'require_email_verification' => $settings->require_email_verification,
            'email_verified'             => $emailVerified,
            'bid_fee_enabled'            => $settings->bid_fee_enabled,
            'bid_fee_amount'             => (float) $settings->bid_fee_amount,
            'deposit_paid'               => $depositPaid,
            'auction_approved'           => (bool) $user->auction_approved,
            'is_banned'                  => $isBanned,
            'ban_message'                => $banMessage,
            'wallet_balance'             => $walletBalance,
        ]);
    }

    // ── Auth: pay refundable bid deposit ────────────────────────────

    public function payDeposit(Request $request, int $id)
    {
        $request->validate([
            'payment_method' => 'required|string',
            'return_url'     => 'nullable|url',
            'cancel_url'     => 'nullable|url',
        ]);

        $item     = AuctionItem::where('status', '!=', 'draft')->findOrFail($id);
        $settings = AuctionSetting::current();
        $user     = $request->user();

        if (!$settings->bid_fee_enabled) {
            return response()->json(['message' => 'Bid deposit is not currently required.'], 422);
        }

        if (!$item->isActive()) {
            return response()->json(['message' => 'This auction is not currently active.'], 422);
        }

        // Check if already paid
        $existing = AuctionBidDeposit::where('user_id', $user->id)
            ->where('auction_item_id', $id)
            ->whereNotNull('paid_at')
            ->first();

        if ($existing) {
            return response()->json(['message' => 'You have already paid the deposit for this auction.'], 422);
        }

        // Create or find a pending deposit record
        $deposit = AuctionBidDeposit::firstOrCreate(
            ['user_id' => $user->id, 'auction_item_id' => $id],
            [
                'amount'         => $settings->bid_fee_amount,
                'payment_method' => $request->payment_method,
            ]
        );

        // If deposit exists but order already created, re-use it
        if ($deposit->order_id) {
            $existingOrder = \App\Models\Order::find($deposit->order_id);
            if ($existingOrder) {
                $fakeRequest = new Request([
                    'order_number'   => $existingOrder->order_number,
                    'payment_method' => $request->payment_method,
                    'return_url'     => $request->return_url,
                    'cancel_url'     => $request->cancel_url,
                ]);
                $repo = app(\App\Repositories\Eloquents\OrderRepository::class);
                return $repo->rePayment($fakeRequest);
            }
        }

        // ── Wallet payment: instant deduction, no gateway redirect ──
        if (strtolower($request->payment_method) === 'wallet') {
            $amount = (float) $settings->bid_fee_amount;
            $wallet = $user->wallet;

            if (!$wallet || $wallet->balance < $amount) {
                return response()->json([
                    'message' => 'Insufficient wallet balance. You have $' . number_format($wallet->balance ?? 0, 2) . ' but need $' . number_format($amount, 2) . '.',
                ], 422);
            }

            return DB::transaction(function () use ($deposit, $user, $item, $amount, $wallet) {
                // Deduct from wallet
                $wallet->decrement('balance', $amount);

                // Log transaction
                $wallet->transactions()->create([
                    'amount'  => $amount,
                    'type'    => 'debit',
                    'detail'  => 'Bid deposit payment — ' . $item->title . ' (Lot #' . $item->id . ')',
                    'status'  => 'approved',
                ]);

                // Mark deposit as paid immediately
                $deposit->update([
                    'paid_at'        => now(),
                    'payment_method' => 'wallet',
                ]);

                return response()->json([
                    'message' => 'Deposit paid from wallet successfully.',
                    'paid'    => true,
                ]);
            });
        }

        // ── Gateway payment: create order and redirect ──
        $order = DB::transaction(function () use ($deposit, $user, $request, $item, $settings) {
            $orderStatus = \App\Models\OrderStatus::where('slug', 'pending')->first();
            // Use the same sequential order number as regular orders (DB-locked, consistent)
            $repo        = app(\App\Repositories\Eloquents\OrderRepository::class);
            $orderNumber = $repo->nextOrderNumber();

            $amount = (float) $settings->bid_fee_amount;

            $order = \App\Models\Order::create([
                'consumer_id'     => $user->id,
                'order_number'    => $orderNumber,
                'amount'          => $amount,
                'total'           => $amount,
                'grand_total'     => $amount,
                'payment_method'  => $request->payment_method,
                'payment_status'  => 'pending',
                'order_status_id' => optional($orderStatus)->id ?? 1,
                'note'            => 'AUC_DEPOSIT: Refundable bid deposit — ' . $item->title . ' (Lot #' . $item->id . ')',
                'currency'        => config('app.currency', 'USD'),
                'currency_symbol' => config('app.currency_symbol', '$'),
                'exchange_rate'   => 1,
                'status'          => 1,
            ]);

            // Link order to deposit record
            $deposit->update([
                'order_id'       => $order->id,
                'payment_method' => $request->payment_method,
            ]);

            return $order;
        });

        $repo        = app(\App\Repositories\Eloquents\OrderRepository::class);
        $fakeRequest = new Request([
            'order_number'   => $order->order_number,
            'payment_method' => $request->payment_method,
            'return_url'     => $request->return_url,
            'cancel_url'     => $request->cancel_url,
        ]);

        $result = $repo->rePayment($fakeRequest);
        return $result instanceof \Illuminate\Http\JsonResponse ? $result : response()->json($result);
    }

    // ── Auth: confirm deposit paid (called by payment webhook/redirect) ───

    public function confirmDeposit(Request $request, int $id): JsonResponse
    {
        $user    = $request->user();
        $deposit = AuctionBidDeposit::where('user_id', $user->id)
            ->where('auction_item_id', $id)
            ->whereNull('paid_at')
            ->first();

        if (!$deposit) {
            // Either already confirmed or doesn't exist
            $paid = AuctionBidDeposit::where('user_id', $user->id)
                ->where('auction_item_id', $id)
                ->whereNotNull('paid_at')
                ->exists();

            return response()->json([
                'deposit_paid' => $paid,
                'message'      => $paid ? 'Deposit already confirmed.' : 'No pending deposit found.',
            ]);
        }

        // Check linked order payment status
        // Covers all gateway status strings: PayFast='complete', others='paid','approved','success','captured','1'
        if ($deposit->order_id) {
            $order = \App\Models\Order::withTempLaybyOrders()->find($deposit->order_id);
            if ($order) {
                $isPaid = in_array(strtolower((string) $order->payment_status), [
                    'paid', 'complete', 'completed', 'success', 'approved', 'captured', '1', 'true',
                ]);
                // Also check order_status slug as a fallback
                if (!$isPaid && $order->order_status) {
                    $isPaid = in_array(strtolower($order->order_status->slug ?? ''), [
                        'paid', 'complete', 'completed', 'processing', 'delivered',
                    ]);
                }
                if ($isPaid) {
                    $deposit->update(['paid_at' => now()]);
                    return response()->json(['deposit_paid' => true, 'message' => 'Deposit confirmed!']);
                }
            }
        }

        return response()->json(['deposit_paid' => false, 'message' => 'Deposit payment is still pending.']);
    }

    // ── Auth: place a bid ────────────────────────────────────────

    public function bid(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $user     = $request->user();
        $settings = AuctionSetting::current();

        // ── Gate 1: Auction Ban (non-payment of previous won item) ─
        $activeBan = \App\Models\AuctionBan::getActiveBan($user->id);
        if ($activeBan) {
            $itemTitle = $activeBan->auctionItem->title ?? 'a previously won item';
            return response()->json([
                'message'    => "You are banned from placing bids because you failed to pay for \"{$itemTitle}\". Please contact support to resolve this.",
                'error_code' => 'auction_bid_banned',
                'banned_for' => $itemTitle,
            ], 403);
        }

        $item = AuctionItem::lockForUpdate()->findOrFail($id);

        if (!$item->isActive()) {
            return response()->json(['message' => 'This auction is not currently active.'], 422);
        }

        // ── Gate 2: Refundable Deposit ──────────────────────────────────────────────
        // Users with 'auction_approved' set by admin bypass the deposit gate entirely.
        if ($settings->bid_fee_enabled && !$user->auction_approved) {
            $depositPaid = AuctionBidDeposit::where('user_id', $user->id)
                ->where('auction_item_id', $id)
                ->whereNotNull('paid_at')
                ->exists();

            if (!$depositPaid) {
                return response()->json([
                    'message'        => 'You must pay the refundable bid deposit of $' . number_format((float) $settings->bid_fee_amount, 2) . ' before placing a bid. This will be refunded if you do not win.',
                    'error_code'     => 'deposit_required',
                    'deposit_amount' => (float) $settings->bid_fee_amount,
                ], 403);
            }
        }

        // ── Gate 3: Minimum bid amount ────────────────────────────
        $minBid = $item->minimumNextBid();
        if ((float) $request->amount < $minBid) {
            return response()->json([
                'message' => "Your bid must be at least $" . number_format($minBid, 2),
            ], 422);
        }

        if ($item->created_by === $user->id) {
            return response()->json(['message' => 'You cannot bid on your own auction.'], 422);
        }

        // Capture the previous leading bidder before recording the new bid
        $previousHighBid = $item->bids()->where('user_id', '!=', $user->id)->first();
        $previousLeader  = $previousHighBid?->user;

        DB::transaction(function () use ($item, $request, $user) {
            AuctionBid::create([
                'auction_item_id' => $item->id,
                'user_id'         => $user->id,
                'amount'          => $request->amount,
                'ip_address'      => $request->ip(),
            ]);

            $item->current_bid = $request->amount;
            $item->bid_count   = $item->bid_count + 1;

            $extendMinutes = $item->auto_extend_minutes;
            if ($extendMinutes > 0) {
                $threshold = $item->ends_at->copy()->subMinutes($extendMinutes);
                if (now()->gte($threshold)) {
                    $item->ends_at = $item->ends_at->copy()->addMinutes($extendMinutes);
                }
            }

            $item->save();
        });

        $item->refresh();

        // ── Auto-bid resolution (outside the human-bid transaction) ──────────
        // Find the highest active auto-bid that belongs to someone other than
        // the current bidder and can still afford the next minimum bid.
        $this->resolveAutoBids($item, $user->id);

        $item->refresh();

        // ── Outbid email for the previous leader ─────────────────────────────
        // Only fire if they exist, are a different user, and do NOT have an
        // active auto-bid still within their max (their auto-bid fires instead).
        if ($previousLeader && $previousLeader->id !== $user->id) {
            $theirAutoBid = AuctionAutoBid::where('auction_item_id', $item->id)
                ->where('user_id', $previousLeader->id)
                ->where('is_active', true)
                ->first();

            // Only email if they CAN'T auto-bid (no auto-bid, or max already exceeded)
            if (!$theirAutoBid || (float) $theirAutoBid->max_amount < $item->minimumNextBid()) {
                Mail::queue(new AuctionOutbidMail(
                    $previousLeader,
                    $item,
                    (float) $item->current_bid,
                    $theirAutoBid ? (float) $theirAutoBid->max_amount : 0,
                ));
            }
        }

        return response()->json([
            'message'                => 'Bid placed successfully!',
            'current_bid'            => $item->current_bid,
            'bid_count'              => $item->bid_count,
            'minimum_next_bid'       => $item->minimumNextBid(),
            'time_remaining_seconds' => $item->timeRemainingSeconds(),
            'ends_at'                => $item->ends_at->toIso8601String(),
        ]);
    }

    // ── Private: auto-bid resolution engine ─────────────────────────────────
    // Called after every human bid. Finds the highest counter auto-bid and
    // places one system bid on their behalf. Recurses until no one can counter.

    private function resolveAutoBids(AuctionItem $item, int $triggeredByUserId, int $depth = 0): void
    {
        // Safety: max 10 hops to avoid runaway loops
        if ($depth >= 10 || !$item->isActive()) {
            return;
        }

        $minBid = $item->minimumNextBid();

        // Find the highest auto-bid from someone OTHER than the person who just bid
        // (including any system auto-bids placed in previous iterations)
        // Use latest() to get the most recent record in case of stale duplicates
        $autoBid = AuctionAutoBid::where('auction_item_id', $item->id)
            ->where('user_id', '!=', $triggeredByUserId)
            ->where('is_active', true)
            ->where('max_amount', '>=', $minBid)
            ->orderByDesc('max_amount')
            ->orderByDesc('id') // tiebreak: prefer most recent record
            ->first();

        if (!$autoBid) {
            return;
        }

        // Place the counter-bid at exactly the minimum next bid
        $bidAmount = $minBid;

        // Previous leader before this auto-counter fires
        $previousLeader = \App\Models\AuctionBid::where('auction_item_id', $item->id)
            ->where('user_id', '!=', $autoBid->user_id)
            ->latest()
            ->first()?->user;

        DB::transaction(function () use ($item, $autoBid, $bidAmount) {
            AuctionBid::create([
                'auction_item_id' => $item->id,
                'user_id'         => $autoBid->user_id,
                'amount'          => $bidAmount,
                'ip_address'      => '127.0.0.1', // system bid
            ]);

            $item->current_bid = $bidAmount;
            $item->bid_count   = $item->bid_count + 1;

            $extendMinutes = $item->auto_extend_minutes;
            if ($extendMinutes > 0) {
                $threshold = $item->ends_at->copy()->subMinutes($extendMinutes);
                if (now()->gte($threshold)) {
                    $item->ends_at = $item->ends_at->copy()->addMinutes($extendMinutes);
                }
            }

            $item->save();
        });

        $item->refresh();

        // If the auto-bid max is now beaten by the minimum next bid, deactivate it
        if ($item->minimumNextBid() > (float) $autoBid->max_amount) {
            $autoBid->update(['is_active' => false]);
        }

        // Notify the person who just got outbid by this auto-bid
        if ($previousLeader && $previousLeader->id !== $autoBid->user_id) {
            $theirAutoBid = AuctionAutoBid::where('auction_item_id', $item->id)
                ->where('user_id', $previousLeader->id)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->first();

            if (!$theirAutoBid || (float) $theirAutoBid->max_amount < $item->minimumNextBid()) {
                // They can't counter — send outbid email
                Mail::queue(new AuctionOutbidMail(
                    $previousLeader,
                    $item,
                    (float) $item->current_bid,
                    $theirAutoBid ? (float) $theirAutoBid->max_amount : 0,
                ));
            } else {
                // They also have an active auto-bid that can still counter — recurse
                $this->resolveAutoBids($item, $autoBid->user_id, $depth + 1);
            }
        }
    }

    // ── Auth: user's own bids ────────────────────────────────────

    public function myBids(Request $request): JsonResponse

    {
        $bids = AuctionBid::with('auctionItem:id,title,images,status,current_bid,winner_id,ends_at,starting_price')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($bids);
    }

    // ── Auth: auctions user has won ───────────────────────────────

    public function myWins(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Check for active ban — pass to frontend so it can disable Pay Now
        $activeBan = \App\Models\AuctionBan::getActiveBan($userId);

        $wins = AuctionItem::with(['winner:id,name'])
            ->where('winner_id', $userId)
            ->where('status', 'ended')
            ->orderByDesc('ends_at')
            ->paginate(20);

        // Attach deposit_paid for each auction so frontend can show balance deduction
        $wins->getCollection()->transform(function ($item) use ($userId) {
            $deposit = AuctionBidDeposit::where('user_id', $userId)
                ->where('auction_item_id', $item->id)
                ->whereNotNull('paid_at')
                ->first();

            $item->deposit_paid = $deposit ? (float) $deposit->amount : 0;
            return $item;
        });

        // Collect auction IDs that already have a pending/approved refund request
        $refundedAuctionIds = AuctionDepositRefund::where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved'])
            ->pluck('auction_item_id')
            ->toArray();

        return response()->json([
            'data'                => $wins->items(),
            'meta'                => [
                'current_page' => $wins->currentPage(),
                'last_page'    => $wins->lastPage(),
                'total'        => $wins->total(),
            ],
            'is_banned'           => $activeBan !== null,
            'ban_reason'          => $activeBan ? ('Your bidding and payment privileges have been suspended due to non-payment of "' . ($activeBan->auctionItem->title ?? 'a previously won item') . '". Contact support to lift this ban.') : null,
            'existing_refund_ids' => $refundedAuctionIds,
        ]);
    }


    // ── Auth: delivery options for auction win ────────────────────────────────
    // Returns available fulfillment methods + delivery fee from Setting,
    // so the frontend can render collection vs delivery UI before payment.

    public function deliveryOptions(Request $request, int $id): JsonResponse
    {
        AuctionItem::findOrFail($id);

        $settings = AuctionSetting::current();

        return response()->json([
            'collection' => ['label' => 'Free Collection', 'cost' => 0],
            'delivery'   => [
                'label'      => 'Home Delivery',
                'cost'       => (float) $settings->delivery_price,
                'enabled'    => $settings->delivery_enabled,
                'radius_km'  => (int) $settings->delivery_radius_km,
            ],
        ]);
    }

    // ── Auth: initiate payment for won auction ────────────────────────
    // Creates an order for the auction win and initiates the payment gateway.
    // If user paid a deposit, it is deducted from the total owed.

    public function pay(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'payment_method'     => 'required|string',
            'return_url'         => 'nullable|url',
            'cancel_url'         => 'nullable|url',
            'fulfillment_method' => 'nullable|in:collection,delivery',
            'delivery_address'   => 'nullable|string|max:500',
        ]);

        $auction = AuctionItem::findOrFail($id);
        $user    = $request->user();

        // ── Ban check: banned users cannot initiate auction payments ──
        $activeBan = \App\Models\AuctionBan::getActiveBan($user->id);
        if ($activeBan) {
            $itemTitle = $activeBan->auctionItem->title ?? 'a previously won item';
            return response()->json([
                'message'    => "Your account is restricted from making payments because you did not pay for \"{$itemTitle}\". Please contact support to resolve this ban.",
                'error_code' => 'auction_bid_banned',
                'banned_for' => $itemTitle,
            ], 403);
        }

        // Only the winner can pay
        if ((int) $auction->winner_id !== (int) $user->id) {
            return response()->json(['message' => 'You are not the winner of this auction.'], 403);
        }

        // Check if a confirmed deposit exists for this user+auction
        $deposit = AuctionBidDeposit::where('user_id', $user->id)
            ->where('auction_item_id', $id)
            ->whereNotNull('paid_at')
            ->first();

        // Delivery cost (if chosen)
        $fulfillmentMethod = $request->input('fulfillment_method', 'collection');
        $deliveryCost      = 0;
        if ($fulfillmentMethod === 'delivery') {
            $deliveryCost = (float) (AuctionSetting::current()->delivery_price ?? 0);
        }

        // Store fulfillment choice on auction item immediately
        $auction->update([
            'fulfillment_method'  => $fulfillmentMethod,
            'delivery_cost'       => $deliveryCost,
            'delivery_address'    => $request->input('delivery_address', ''),
            'fulfillment_status'  => 'pending',
        ]);

        $depositDeducted = $deposit ? (float) $deposit->amount : 0;
        // Deposit also covers delivery cost if possible
        $totalDue  = (float) $auction->winner_bid + $deliveryCost;
        $amountDue = max(0, $totalDue - $depositDeducted);

        // If already linked to an order, re-use it to retry payment
        if ($auction->order_id) {
            $existingOrder = \App\Models\Order::withTempLaybyOrders()->find($auction->order_id);
            if ($existingOrder) {
                $fakeRequest = new Request([
                    'order_number'   => $existingOrder->order_number,
                    'payment_method' => $request->payment_method,
                    'return_url'     => $request->return_url,
                    'cancel_url'     => $request->cancel_url,
                ]);
                $repo = app(\App\Repositories\Eloquents\OrderRepository::class);
                $result = $repo->rePayment($fakeRequest);
                return $result instanceof \Illuminate\Http\JsonResponse ? $result : response()->json($result);
            }
        }

        // Create a pending order for the auction win
        $order = DB::transaction(function () use ($auction, $user, $request, $depositDeducted, $amountDue) {
            $orderStatus = \App\Models\OrderStatus::where('slug', 'pending')->first();

            // Use the same sequential order number as all other orders
            $repo        = app(\App\Repositories\Eloquents\OrderRepository::class);
            $orderNumber = $repo->nextOrderNumber();

            $order = \App\Models\Order::create([
                'consumer_id'     => $user->id,
                'order_number'    => $orderNumber,
                'amount'          => $amountDue,
                'total'           => $amountDue,
                'grand_total'     => $amountDue,
                'payment_method'  => $request->payment_method,
                'payment_status'  => 'pending',
                'order_status_id' => optional($orderStatus)->id ?? 1,
                'note'            => 'AUC_WIN: Auction win — ' . $auction->title . ' (Lot #' . $auction->id . ')'
                                      . ($depositDeducted > 0 ? ' | Deposit deducted: $' . number_format($depositDeducted, 2) . ' | Balance due: $' . number_format($amountDue, 2) : ''),
                'currency'        => config('app.currency', 'USD'),
                'currency_symbol' => config('app.currency_symbol', '$'),
                'exchange_rate'   => 1,
                'status'          => 1,
            ]);

            // Link auction to this order
            $auction->update(['order_id' => $order->id]);

            return $order;
        });

        // Delegate to OrderRepository::rePayment which handles all gateway redirects
        $repo = app(\App\Repositories\Eloquents\OrderRepository::class);
        $fakeRequest = new Request([
            'order_number'   => $order->order_number,
            'payment_method' => $request->payment_method,
            'return_url'     => $request->return_url,
            'cancel_url'     => $request->cancel_url,
        ]);

        $result = $repo->rePayment($fakeRequest);
        return $result instanceof \Illuminate\Http\JsonResponse ? $result : response()->json($result);
    }

    // ── Deposit refund info ──────────────────────────────────────────────────

    public function depositRefundInfo(Request $request, int $id): JsonResponse
    {
        $auction = AuctionItem::findOrFail($id);
        $user    = $request->user();

        $deposit = AuctionBidDeposit::where('user_id', $user->id)
            ->where('auction_item_id', $id)
            ->whereNotNull('paid_at')
            ->first();

        if (!$deposit) {
            return response()->json(['eligible' => false, 'message' => 'No paid deposit found for this auction.']);
        }

        $depositAmount  = (float) $deposit->amount;
        $winnerBid      = ($auction->winner_id == $user->id) ? (float) $auction->winner_bid : 0;
        $isWinner       = $auction->winner_id == $user->id;

        // Refund eligible if: not the winner, OR deposit > winner bid
        $refundAmount = $isWinner
            ? max(0, $depositAmount - $winnerBid)
            : $depositAmount;

        $eligible = $refundAmount > 0 && !$deposit->refunded_at;

        // Check for existing pending refund
        $existingRefund = AuctionDepositRefund::where('user_id', $user->id)
            ->where('auction_item_id', $id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        // Check banking details
        $paymentAccount = Helpers::getPaymentAccount($user->id);

        return response()->json([
            'eligible'          => $eligible,
            'deposit_amount'    => $depositAmount,
            'winner_bid_amount' => $winnerBid,
            'refund_amount'     => $refundAmount,
            'is_winner'         => $isWinner,
            'already_refunded'  => (bool) $deposit->refunded_at,
            'existing_request'  => $existingRefund ? $existingRefund->status : null,
            'has_banking_details' => (bool) $paymentAccount,
        ]);
    }

    // ── Request deposit refund ────────────────────────────────────────────────

    public function requestDepositRefund(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason'        => 'nullable|string|max:500',
            'reason_type'   => 'nullable|in:lost_auction,deposit_overpaid,auction_cancelled,other',
            'refund_method' => 'nullable|in:bank,wallet',
        ]);

        $auction = AuctionItem::findOrFail($id);
        $user    = $request->user();

        // Banned users cannot request deposit refunds
        $activeBan = \App\Models\AuctionBan::getActiveBan($user->id);
        if ($activeBan) {
            $itemTitle = $activeBan->auctionItem->title ?? 'a previously won item';
            return response()->json([
                'message'    => "You cannot request a deposit refund because your account has been restricted due to non-payment of \"{$itemTitle}\". Please contact support.",
                'error_code' => 'auction_bid_banned',
            ], 403);
        }

        $refundMethod = $request->refund_method ?? 'bank';

        // Banking details only required for bank refunds
        if ($refundMethod === 'bank') {
            $paymentAccount = Helpers::getPaymentAccount($user->id);
            if (!$paymentAccount) {
                return response()->json([
                    'message' => 'Please add your banking details before requesting a bank refund.',
                    'requires_banking_details' => true,
                ], 400);
            }
        }

        $deposit = AuctionBidDeposit::where('user_id', $user->id)
            ->where('auction_item_id', $id)
            ->whereNotNull('paid_at')
            ->whereNull('refunded_at')
            ->first();

        if (!$deposit) {
            return response()->json(['message' => 'No eligible deposit found for refund.'], 400);
        }

        // Check no pending/approved refund already exists
        $existing = AuctionDepositRefund::where('user_id', $user->id)
            ->where('auction_item_id', $id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($existing) {
            return response()->json(['message' => 'A refund request for this auction is already pending.'], 400);
        }

        $depositAmount  = (float) $deposit->amount;
        $isWinner       = $auction->winner_id == $user->id;
        $winnerBid      = $isWinner ? (float) $auction->winner_bid : 0;
        $refundAmount   = $isWinner ? max(0, $depositAmount - $winnerBid) : $depositAmount;

        if ($refundAmount <= 0) {
            return response()->json(['message' => 'No refundable amount — deposit covers the winning bid exactly.'], 400);
        }

        $reasonType = $request->reason_type ?? ($isWinner ? 'deposit_overpaid' : 'lost_auction');

        $refundRequest = AuctionDepositRefund::create([
            'user_id'                => $user->id,
            'auction_item_id'        => $id,
            'auction_bid_deposit_id' => $deposit->id,
            'deposit_amount'         => $depositAmount,
            'winner_bid_amount'      => $winnerBid,
            'refund_amount'          => $refundAmount,
            'reason_type'            => $reasonType,
            'reason'                 => $request->reason,
            'refund_method'          => $refundMethod,
            'status'                 => 'pending',
        ]);

        $methodLabel = $refundMethod === 'wallet' ? 'in-app wallet' : 'bank account';
        return response()->json([
            'message' => "Refund request submitted. We'll credit your {$methodLabel} within 3–5 business days.",
            'refund'  => $refundRequest,
        ], 201);
    }

    // ── My deposit refunds ────────────────────────────────────────────────────

    public function myDepositRefunds(Request $request): JsonResponse
    {
        $refunds = AuctionDepositRefund::where('user_id', $request->user()->id)
            ->with(['auction:id,title,winner_bid'])
            ->latest()
            ->paginate($request->paginate ?? 10);

        return response()->json($refunds);
    }
}
