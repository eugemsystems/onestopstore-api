<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuctionItem;
use App\Models\AuctionBid;
use App\Models\AuctionBidDeposit;
use App\Models\AuctionBan;
use App\Models\AuctionSetting;
use App\Models\AuctionEvent;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\User;
use App\Mail\AuctionWonMail;
use App\Http\Traits\WalletPointsTrait;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AdminAuctionController extends Controller
{
    use WalletPointsTrait;
    // ── List ──────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = AuctionItem::with(['createdBy:id,name', 'winner:id,name'])
            ->withCount('bids');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('title', 'ILIKE', '%' . $request->search . '%');
        }

        $allowedPerPage = [10, 25, 50, 75, 100];
        $perPage = (int) $request->get('per_page', 20);

        if ($perPage === 0) {
            // "All" — fetch every row with no LIMIT so drag-and-drop can reorder the full list
            $allItems = $query
                ->orderByRaw('sort_order IS NULL')
                ->orderBy('sort_order')
                ->orderByDesc('created_at')
                ->get();

            $auctions = new LengthAwarePaginator(
                $allItems,          // items for the current "page" (the whole collection)
                $allItems->count(), // total
                max(1, $allItems->count()), // per_page == total (one big page)
                1,                  // current page
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            if (!in_array($perPage, $allowedPerPage)) {
                $perPage = 20;
            }
            $auctions = $query
                ->orderByRaw('sort_order IS NULL')
                ->orderBy('sort_order')
                ->orderByDesc('created_at')
                ->paginate($perPage)
                ->withQueryString();
        }

        $statusCounts = [
            'all'       => AuctionItem::count(),
            'draft'     => AuctionItem::where('status', 'draft')->count(),
            'active'    => AuctionItem::where('status', 'active')->count(),
            'ended'     => AuctionItem::where('status', 'ended')->count(),
            'cancelled' => AuctionItem::where('status', 'cancelled')->count(),
        ];

        return view('admin.auctions.index', compact('auctions', 'statusCounts', 'perPage'));
    }

    // ── Create form ───────────────────────────────────────────────

    public function create()
    {
        return view('admin.auctions.create');
    }

    // ── Show (detail view) ────────────────────────────────────────

    public function show(AuctionItem $auction)
    {
        $auction->load([
            'bids.user:id,name,email',
            'product:id,name,slug',
            'createdBy:id,name',
            'winner:id,name,email',
        ]);

        // Bid history ordered latest first
        $bids = $auction->bids()->with('user:id,name,email')->latest()->get();

        return view('admin.auctions.show', compact('auction', 'bids'));
    }

    // ── Store ─────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'               => 'required|string|max:255',
            'branch'              => 'required|string|in:All,Harare,Mutare,Bulawayo,Zambia',
            'description'         => 'nullable|string',
            'condition'           => 'required|in:damaged,refurbished,as-is,boxed-damaged,no-box,returned,dented,missing-accessories',
            'product_id'          => 'nullable|exists:products,id',
            'starting_price'      => 'required|numeric|min:0.01',
            'reserve_price'       => 'nullable|numeric|min:0',
            'min_bid_increment'   => 'required|numeric|min:0.01',
            'status'              => 'required|in:draft,active',
            'starts_at'           => 'required|date',
            'ends_at'             => 'required|date|after:starts_at',
            'auto_extend_minutes' => 'required|integer|min:0',
            'images.*'            => 'nullable|image|max:5120',
        ]);

        // Handle image uploads
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('auction-images', 'public');
                // Use auction-files streaming route (bypasses nginx symlink issues on production)
                $imagePaths[] = rtrim(config('app.url'), '/') . '/api/auction-files/' . $path;
            }
        }
        $data['images']     = $imagePaths;
        $data['created_by'] = auth()->id();
        $data['current_bid']= null; // Will be set to starting_price on first bid or display fallback

        AuctionItem::create($data);

        return redirect()->route('admin.auctions.index')
                         ->with('success', 'Auction created successfully!');
    }

    // ── Edit form ─────────────────────────────────────────────────

    public function edit(AuctionItem $auction)
    {
        return view('admin.auctions.edit', compact('auction'));
    }

    // ── Update ────────────────────────────────────────────────────

    public function update(Request $request, AuctionItem $auction)
    {
        $data = $request->validate([
            'title'               => 'required|string|max:255',
            'branch'              => 'required|string|in:All,Harare,Mutare,Bulawayo,Zambia',
            'description'         => 'nullable|string',
            'condition'           => 'required|in:damaged,refurbished,as-is,boxed-damaged,no-box,returned,dented,missing-accessories',
            'product_id'          => 'nullable|exists:products,id',
            'starting_price'      => 'required|numeric|min:0.01',
            'reserve_price'       => 'nullable|numeric|min:0',
            'min_bid_increment'   => 'required|numeric|min:0.01',
            'status'              => 'required|in:draft,active,cancelled',
            'starts_at'           => 'required|date',
            'ends_at'             => 'required|date|after:starts_at',
            'auto_extend_minutes' => 'required|integer|min:0',
            'images.*'            => 'nullable|image|max:5120',
        ]);

        // Append new images to existing ones
        $existingImages = $auction->images ?? [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('auction-images', 'public');
                // Use auction-files streaming route (bypasses nginx symlink issues on production)
                $existingImages[] = rtrim(config('app.url'), '/') . '/api/auction-files/' . $path;
            }
        }
        $data['images'] = $existingImages;

        $auction->update($data);

        return redirect()->route('admin.auctions.index')
                         ->with('success', 'Auction updated successfully!');
    }

    // ── Delete ────────────────────────────────────────────────────

    public function destroy(AuctionItem $auction)
    {
        $auction->delete();
        return redirect()->route('admin.auctions.index')
                         ->with('success', 'Auction deleted.');
    }

    // ── Bulk Actions ────────────────────────────────────────────────

    public function bulkAction(Request $request)
    {
        $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer|exists:auction_items,id',
            'action' => 'required|string',
        ]);

        $ids    = $request->ids;
        $action = $request->action;
        $count  = count($ids);

        switch ($action) {
            case 'activate':
                AuctionItem::whereIn('id', $ids)->where('status', 'draft')->update(['status' => 'active']);
                return back()->with('success', "✓ {$count} auction(s) activated.");

            case 'draft':
                AuctionItem::whereIn('id', $ids)->whereIn('status', ['active', 'cancelled'])->update(['status' => 'draft']);
                return back()->with('success', "✓ {$count} auction(s) moved to draft.");

            case 'cancel':
                AuctionItem::whereIn('id', $ids)->whereIn('status', ['active', 'draft'])->update(['status' => 'cancelled']);
                return back()->with('success', "✓ {$count} auction(s) cancelled.");

            case 'delete':
                AuctionItem::whereIn('id', $ids)->whereIn('status', ['draft', 'cancelled'])->delete();
                return back()->with('success', "✓ {$count} auction(s) deleted.");

            case 'change_branch':
                $request->validate(['branch' => 'required|in:All,Harare,Mutare,Bulawayo,Zambia']);
                AuctionItem::whereIn('id', $ids)->update(['branch' => $request->branch]);
                return back()->with('success', "✓ {$count} auction(s) moved to {$request->branch}.");

            case 'extend_time':
                $request->validate(['extend_hours' => 'required|integer|min:1|max:168']);
                $hours = (int) $request->extend_hours;
                AuctionItem::whereIn('id', $ids)->where('status', 'active')->each(function ($item) use ($hours) {
                    $item->update(['ends_at' => \Carbon\Carbon::parse($item->ends_at)->addHours($hours)]);
                });
                return back()->with('success', "✓ {$count} auction(s) extended by {$hours} hours.");

            default:
                return back()->with('error', 'Unknown bulk action.');
        }
    }

    // ── Manually end auction ──────────────────────────────────────

    public function end(AuctionItem $auction)
    {
        if ($auction->status !== 'active') {
            return back()->with('error', 'Only active auctions can be ended.');
        }

        // Determine winner: highest bid
        $winningBid = AuctionBid::where('auction_item_id', $auction->id)
                                  ->orderByDesc('amount')
                                  ->first();

        $auction->update([
            'status'     => 'ended',
            'ends_at'    => now(),
            'winner_id'  => $winningBid?->user_id,
            'winner_bid' => $winningBid?->amount,
        ]);

        // Dispatch winner email
        if ($winningBid) {
            try {
                $winner = User::find($winningBid->user_id);
                if ($winner) {
                    Mail::to($winner->email)->queue(new AuctionWonMail($auction->fresh(), $winner));
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("AuctionWonMail admin-end failed: " . $e->getMessage());
            }
        }

        $winnerName = $winningBid?->user?->name ?? ('User #' . $winningBid?->user_id);
        $msg = $winningBid
            ? "Auction ended. Winner: {$winnerName} (\${$winningBid->amount}). Winner email queued."
            : 'Auction ended. No bids were placed.';

        return redirect()->route('admin.auctions.index')->with('success', $msg);
    }

    // ── AJAX: product search ──────────────────────────────────────

    public function searchProducts(Request $request)
    {
        $term = trim($request->get('q', ''));

        // Try Elasticsearch first (mirrors ProductController search)
        try {
            $client = \App\Services\ElasticsearchService::client();
            $index  = \App\Services\ElasticsearchService::indexName();

            if ($client && $term) {
                $response = $client->search([
                    'index' => $index,
                    'body'  => [
                        'size'  => 15,
                        'query' => [
                            'multi_match' => [
                                'query'     => $term,
                                'fields'    => ['name^3', 'sku^2', 'combined'],
                                'type'      => 'best_fields',
                                'fuzziness' => 'AUTO',
                            ],
                        ],
                        '_source' => ['id', 'name', 'price', 'product_thumbnail'],
                    ],
                ]);

                $hits = $response['hits']['hits'] ?? [];
                $products = collect($hits)->map(function ($hit) {
                    $src   = $hit['_source'];
                    $thumb = data_get($src, 'product_thumbnail.original_url')
                        ?? data_get($src, 'product_thumbnail.file_name');

                    return [
                        'id'        => $src['id'],
                        'name'      => $src['name'],
                        'price'     => $src['price'] ?? 0,
                        'image_url' => $thumb ? asset('storage/' . ltrim($thumb, '/')) : null,
                    ];
                });

                return response()->json($products->values());
            }
        } catch (\Throwable $e) {
            // Elasticsearch unavailable — fall through to DB
        }

        // DB fallback with thumbnail eager load
        $products = Product::with('product_thumbnail')
            ->where('name', 'ILIKE', "%{$term}%")
            ->select('id', 'name', 'price', 'product_thumbnail_id')
            ->limit(15)
            ->get()
            ->map(function ($p) {
                $thumb = $p->product_thumbnail;
                return [
                    'id'        => $p->id,
                    'name'      => $p->name,
                    'price'     => $p->price,
                    'image_url' => $thumb ? asset('storage/' . ltrim($thumb->file_name, '/')) : null,
                ];
            });

        return response()->json($products->values());
    }

    // ── AJAX: remove a single image ───────────────────────────────

    public function removeImage(Request $request, AuctionItem $auction)
    {
        $url = $request->input('url');
        $images = array_values(array_filter($auction->images ?? [], fn($img) => $img !== $url));
        $auction->update(['images' => $images]);
        return response()->json(['success' => true, 'images' => $images]);
    }

    // ── Auction Statistics ────────────────────────────────────────

    public function statistics(Request $request)
    {
        $days = (int) ($request->days ?? 30);
        $since = now()->subDays($days);

        $analyticsDb = DB::connection('analytics');

        // Overall event totals (last N days)
        $totals = $analyticsDb->table('auction_events')
            ->where('created_at', '>=', $since)
            ->selectRaw('event, count(*) as cnt')
            ->groupBy('event')
            ->pluck('cnt', 'event')
            ->toArray();

        // Unique sessions (approximate unique visitors)
        $uniqueSessions = $analyticsDb->table('auction_events')
            ->where('created_at', '>=', $since)
            ->whereNotNull('session_id')
            ->distinct('session_id')
            ->count('session_id');

        // Top auctions by page views
        $topByViews = $analyticsDb->table('auction_events')
            ->where('event', 'page_view')
            ->where('created_at', '>=', $since)
            ->selectRaw('auction_item_id, count(*) as views')
            ->groupBy('auction_item_id')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        // Enrich with auction titles from main DB
        $auctionIds = $topByViews->pluck('auction_item_id')->filter()->all();
        $auctionTitles = AuctionItem::whereIn('id', $auctionIds)->pluck('title', 'id');
        $topByViews = $topByViews->map(fn($r) => [
            'id'     => $r->auction_item_id,
            'title'  => $auctionTitles[$r->auction_item_id] ?? "Auction #{$r->auction_item_id}",
            'views'  => $r->views,
        ]);

        // Daily trend (events per day for last N days)
        $dailyTrend = $analyticsDb->table('auction_events')
            ->where('created_at', '>=', $since)
            ->selectRaw("DATE(created_at) as day, event, count(*) as cnt")
            ->groupByRaw('DATE(created_at), event')
            ->orderBy('day')
            ->get()
            ->groupBy('day');

        // Bid error breakdown
        $bidErrors = $analyticsDb->table('auction_events')
            ->where('event', 'bid_error')
            ->where('created_at', '>=', $since)
            ->get()
            ->map(fn($e) => json_decode($e->meta, true)['error'] ?? 'Unknown')
            ->countBy()
            ->sortDesc()
            ->take(10);

        // Funnel: views -> bid_focus -> bid_submit -> bid_success
        $funnel = [
            'page_view'   => $totals['page_view']   ?? 0,
            'bid_focus'   => $totals['bid_focus']   ?? 0,
            'bid_submit'  => $totals['bid_submit']  ?? 0,
            'bid_success' => $totals['bid_success'] ?? 0,
        ];

        return view('admin.auctions.statistics', compact(
            'totals', 'uniqueSessions', 'topByViews',
            'dailyTrend', 'bidErrors', 'funnel', 'days'
        ));
    }

    public function auctionStats(AuctionItem $auction)
    {
        $analyticsDb = DB::connection('analytics');

        // All events for this auction
        $events = $analyticsDb->table('auction_events')
            ->where('auction_item_id', $auction->id)
            ->get();

        $totals = $events->groupBy('event')->map->count();

        $funnel = [
            'page_view'   => $totals['page_view']   ?? 0,
            'bid_focus'   => $totals['bid_focus']   ?? 0,
            'bid_submit'  => $totals['bid_submit']  ?? 0,
            'bid_success' => $totals['bid_success'] ?? 0,
        ];

        // Errors
        $errors = $events->where('event', 'bid_error')
            ->map(fn($e) => json_decode($e->meta, true)['error'] ?? 'Unknown')
            ->countBy()->sortDesc();

        // Image click distribution
        $imageClicks = $events->where('event', 'image_click')
            ->map(fn($e) => 'Image #' . ((json_decode($e->meta, true)['index'] ?? 0) + 1))
            ->countBy()->sortDesc();

        // Tab switches
        $tabSwitches = $events->where('event', 'tab_switch')
            ->map(fn($e) => json_decode($e->meta, true)['tab'] ?? 'unknown')
            ->countBy();

        // Daily activity (last 30 days)
        $dailyActivity = $analyticsDb->table('auction_events')
            ->where('auction_item_id', $auction->id)
            ->selectRaw("DATE(created_at) as day, event, count(*) as cnt")
            ->groupByRaw('DATE(created_at), event')
            ->orderBy('day')
            ->get()
            ->groupBy('day');

        return view('admin.auctions.auction-stats', compact(
            'auction', 'totals', 'funnel', 'errors', 'imageClicks', 'tabSwitches', 'dailyActivity'
        ));
    }

    // ── Auction Settings (Global) ─────────────────────────────────

    public function settings()
    {
        $settings = AuctionSetting::current();
        return view('admin.auctions.settings', compact('settings'));
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'require_email_verification' => 'nullable|boolean',
            'bid_fee_enabled'            => 'nullable|boolean',
            'bid_fee_amount'             => 'nullable|numeric|min:0',
            'delivery_enabled'           => 'nullable|boolean',
            'delivery_price'             => 'nullable|numeric|min:0',
            'delivery_radius_km'         => 'nullable|integer|min:1',
            'hours_to_pay'               => 'nullable|integer|min:1|max:720',
            'reminder_1_hours'           => 'nullable|integer|min:1',
            'reminder_2_hours'           => 'nullable|integer|min:1',
        ]);

        $settings = AuctionSetting::current();
        $settings->update([
            'require_email_verification' => $request->boolean('require_email_verification'),
            'bid_fee_enabled'            => $request->boolean('bid_fee_enabled'),
            'bid_fee_amount'             => $request->input('bid_fee_amount', $settings->bid_fee_amount) ?? 0,
            'delivery_enabled'           => $request->boolean('delivery_enabled'),
            'delivery_price'             => $request->input('delivery_price', $settings->delivery_price) ?? 0,
            'delivery_radius_km'         => $request->input('delivery_radius_km', $settings->delivery_radius_km) ?? 15,
            'hours_to_pay'               => $request->input('hours_to_pay', $settings->hours_to_pay) ?? 48,
            'reminder_1_hours'           => $request->input('reminder_1_hours', $settings->reminder_1_hours) ?? 12,
            'reminder_2_hours'           => $request->input('reminder_2_hours', $settings->reminder_2_hours) ?? 24,
        ]);

        return redirect()->route('admin.auctions.settings')
                         ->with('success', 'Auction settings saved successfully!');
    }

    // ── Participants & Payment History ────────────────────────────

    public function participants(AuctionItem $auction)
    {
        // All unique bidders with their bids
        $bidders = \App\Models\AuctionBid::with('user:id,name,email,phone')
            ->where('auction_item_id', $auction->id)
            ->get()
            ->groupBy('user_id');

        // Deposits for this auction
        $deposits = \App\Models\AuctionBidDeposit::with(['user:id,name,email', 'order'])
            ->where('auction_item_id', $auction->id)
            ->get()
            ->keyBy('user_id');

        // Winner's payment order (if auction ended)
        $winnerOrder = null;
        if ($auction->winner_id && $auction->order_id) {
            $winnerOrder = \App\Models\Order::find($auction->order_id);
        }

        // Build participant rows
        $participants = $bidders->map(function ($bids, $userId) use ($deposits, $auction, $winnerOrder) {
            $user       = $bids->first()->user;
            $deposit    = $deposits->get($userId);
            $isWinner   = $auction->winner_id && $auction->winner_id == $userId;
            $topBid     = $bids->max('amount');

            return (object) [
                'user'              => $user,
                'bid_count'         => $bids->count(),
                'highest_bid'       => $topBid,
                'is_winner'         => $isWinner,
                'deposit'           => $deposit,
                'deposit_paid'      => $deposit && $deposit->paid_at,
                'deposit_refunded'  => $deposit && $deposit->refunded_at,
                'deposit_amount'    => $deposit?->amount,
                'deposit_order'     => $deposit?->order,
                'winner_order'      => $isWinner ? $winnerOrder : null,
                'bids'              => $bids->sortByDesc('amount'),
            ];
        })->sortByDesc(fn($p) => $p->highest_bid)->values();

        return view('admin.auctions.participants', compact('auction', 'participants'));
    }

    // ── Deposit Refunds (API for admin React/JSON consumers) ──────────────────

    public function depositRefundsList(Request $request)
    {
        $query = \App\Models\AuctionDepositRefund::with([
            'user:id,name,email',
            'user.paymentAccounts',
            'auction:id,title',
            'deposit:id,amount,paid_at',
        ])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('user', fn($q) => $q->where('name', 'LIKE', "%$s%")->orWhere('email', 'LIKE', "%$s%"))
                  ->orWhereHas('auction', fn($q) => $q->where('title', 'LIKE', "%$s%"));
        }

        return response()->json($query->paginate($request->paginate ?? 20));
    }

    public function updateDepositRefund(Request $request, int $id)
    {
        $request->validate([
            'status'      => 'required|in:approved,rejected',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $refund = \App\Models\AuctionDepositRefund::findOrFail($id);

        $refund->update([
            'status'       => $request->status,
            'admin_notes'  => $request->admin_notes,
            'processed_at' => now(),
        ]);

        // Mark deposit as refunded when approved
        if ($request->status === 'approved') {
            $refund->deposit()->update(['refunded_at' => now()]);
        }

        return response()->json([
            'message' => $request->status === 'approved'
                ? 'Refund approved. Please process the bank transfer manually.'
                : 'Refund request rejected.',
            'refund' => $refund->fresh(),
        ]);
    }

    // ── Blade pages for admin web routes ─────────────────────────────────────

    public function depositRefundsPage(Request $request)
    {
        $query = \App\Models\AuctionDepositRefund::with([
            'user.payment_account',
            'user.wallet',
            'auction:id,title',
        ])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('refund_method')) {
            $query->where('refund_method', $request->refund_method);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->whereHas('user', fn($u) => $u->where('name', 'LIKE', "%$s%")->orWhere('email', 'LIKE', "%$s%"))
                  ->orWhereHas('auction', fn($a) => $a->where('title', 'LIKE', "%$s%"));
            });
        }

        $refunds = $query->paginate(20);

        return view('admin.auctions.deposit-refunds', compact('refunds'));
    }

    public function processDepositRefund(Request $request, \App\Models\AuctionDepositRefund $refund)
    {
        $request->validate([
            'status'      => 'required|in:approved,rejected',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $refund->update([
            'status'       => $request->status,
            'admin_notes'  => $request->admin_notes,
            'processed_at' => now(),
        ]);

        if ($request->status === 'approved') {
            $refund->deposit()->update(['refunded_at' => now()]);

            // If the customer requested a wallet refund, credit it automatically
            if ($refund->refund_method === 'wallet') {
                try {
                    $this->creditWallet(
                        $refund->user_id,
                        (float) $refund->refund_amount,
                        'Auction deposit refund — ' . ($refund->auction->title ?? "Auction #{$refund->auction_item_id}")
                    );
                    $refund->update(['wallet_credited_at' => now()]);
                    $msg = '✅ Wallet credited with $' . number_format($refund->refund_amount, 2) . ' for ' . ($refund->user->name ?? 'the customer') . '.';
                } catch (\Exception $e) {
                    return redirect()->back()->with('error', 'Refund approved but wallet credit failed: ' . $e->getMessage());
                }
            } else {
                $msg = 'Refund approved. Please process the bank transfer of $' . number_format($refund->refund_amount, 2) . ' manually.';
            }
        } else {
            $msg = 'Refund request has been rejected.';
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Manually credit a deposit refund directly to the user's wallet.
     * Can be used on any refund regardless of refund_method.
     */
    public function creditRefundToWallet(Request $request, \App\Models\AuctionDepositRefund $refund)
    {
        if ($refund->wallet_credited_at) {
            return redirect()->back()->with('error', 'Wallet has already been credited for this refund.');
        }

        try {
            DB::beginTransaction();

            $this->creditWallet(
                $refund->user_id,
                (float) $refund->refund_amount,
                'Auction deposit refund — ' . ($refund->auction->title ?? "Auction #{$refund->auction_item_id}")
            );

            $refund->update([
                'wallet_credited_at' => now(),
                'status'             => 'approved',      // auto-approve if still pending
                'refund_method'      => 'wallet',        // mark as wallet regardless
                'processed_at'       => $refund->processed_at ?? now(),
            ]);

            // Mark the original deposit as refunded
            $refund->deposit()->update(['refunded_at' => now()]);

            // Fetch wallet + last 5 transactions for verification
            $wallet = \App\Models\Wallet::where('consumer_id', $refund->user_id)
                ->with(['transactions' => fn($q) => $q->latest()->limit(5)])
                ->first();

            DB::commit();

            return redirect()->back()
                ->with('success', '✅ Wallet credited with $' . number_format($refund->refund_amount, 2) . ' for ' . ($refund->user->name ?? 'user') . '.')
                ->with('wallet_verification', [
                    'refund_id'   => $refund->id,
                    'user_name'   => $refund->user->name ?? '—',
                    'new_balance' => $wallet?->balance ?? 0,
                    'transactions'=> $wallet?->transactions->map(fn($t) => [
                        'type'        => $t->type,
                        'amount'      => $t->amount,
                        'details'     => $t->detail ?? $t->remark ?? '—',
                        'created_at'  => $t->created_at?->format('d M Y H:i'),
                    ])->toArray() ?? [],
                ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Wallet credit failed: ' . $e->getMessage());
        }
    }


    // ── Won Auctions: all auctions with a winner ──────────────────────────────

    public function wonAuctions(Request $request)
    {
        $paidStatuses = ['paid', 'complete', 'completed', 'success', 'approved', 'captured'];

        $query = AuctionItem::with([
                'winner:id,name,email,phone',
                'order' => fn($q) => $q->withoutGlobalScope(\App\Models\Concerns\ExcludeTempLaybyScope::class),
            ])
            ->where('status', 'ended')
            ->whereNotNull('winner_id')
            ->latest('ends_at');

        if ($request->filled('payment')) {
            if ($request->payment === 'paid') {
                $query->where(function ($q) use ($paidStatuses) {
                    $q->where(function ($inner) use ($paidStatuses) {
                        $inner->whereNotNull('order_id')
                              ->whereHas('order', fn($oq) =>
                                  $oq->withoutGlobalScope(\App\Models\Concerns\ExcludeTempLaybyScope::class)
                                      ->whereRaw("LOWER(payment_status) IN (" . implode(',', array_fill(0, count($paidStatuses), '?')) . ")", $paidStatuses)
                              );
                    })->orWhereIn('fulfillment_status', ['confirmed','ready_for_collection','out_for_delivery','collected','delivered']);
                });
            } elseif ($request->payment === 'unpaid') {
                $query->where(function ($q) use ($paidStatuses) {
                    $q->where(function ($inner) use ($paidStatuses) {
                        $inner->whereNull('order_id')
                              ->orWhereHas('order', fn($oq) =>
                                  $oq->withoutGlobalScope(\App\Models\Concerns\ExcludeTempLaybyScope::class)
                                      ->whereRaw("LOWER(payment_status) NOT IN (" . implode(',', array_fill(0, count($paidStatuses), '?')) . ")", $paidStatuses)
                              );
                    });
                    $q->where(function ($inner) {
                        $inner->whereNull('fulfillment_status')
                              ->orWhereNotIn('fulfillment_status', ['confirmed','ready_for_collection','out_for_delivery','collected','delivered']);
                    });
                });
            }
        }

        if ($request->filled('fulfillment')) {
            $query->where('fulfillment_status', $request->fulfillment);
        }
        if ($request->filled('branch')) {
            $query->where('branch', $request->branch);
        }
        if ($request->filled('from')) {
            $query->where('ends_at', '>=', $request->from . ' 00:00:00');
        }
        if ($request->filled('to')) {
            $query->where('ends_at', '<=', $request->to . ' 23:59:59');
        }
        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('title', 'ILIKE', $s)
                  ->orWhere('id', 'LIKE', trim($s, '%'))
                  ->orWhereHas('winner', fn($uq) => $uq->where('name', 'ILIKE', $s)->orWhere('email', 'ILIKE', $s));
            });
        }

        $items = $query->paginate(25)->withQueryString();

        $items->getCollection()->transform(function ($item) use ($paidStatuses) {
            $item->is_paid = false;
            $item->payment_amount = 0;

            if ($item->order) {
                $item->is_paid = in_array(strtolower($item->order->payment_status ?? ''), $paidStatuses);
                $item->payment_amount = $item->order->total ?? $item->order->amount ?? 0;
            }

            if (in_array($item->fulfillment_status, ['confirmed','ready_for_collection','out_for_delivery','collected','delivered'])) {
                $item->is_paid = true;
            }

            $deposit = AuctionBidDeposit::where('auction_item_id', $item->id)
                ->where('user_id', $item->winner_id)
                ->whereNotNull('paid_at')
                ->first();
            $item->deposit_paid = $deposit ? $deposit->amount : 0;

            $item->is_banned = AuctionBan::where('user_id', $item->winner_id)
                ->where('auction_item_id', $item->id)
                ->whereNull('lifted_at')
                ->exists();

            return $item;
        });

        $stats = [
            'total_won' => AuctionItem::where('status', 'ended')->whereNotNull('winner_id')->count(),
            'total_paid' => AuctionItem::where('status', 'ended')->whereNotNull('winner_id')
                ->where(function ($q) use ($paidStatuses) {
                    $q->whereIn('fulfillment_status', ['confirmed','ready_for_collection','out_for_delivery','collected','delivered'])
                      ->orWhere(function ($inner) use ($paidStatuses) {
                          $inner->whereNotNull('order_id')
                                ->whereHas('order', fn($oq) =>
                                    $oq->withoutGlobalScope(\App\Models\Concerns\ExcludeTempLaybyScope::class)
                                        ->whereRaw("LOWER(payment_status) IN (" . implode(',', array_fill(0, count($paidStatuses), '?')) . ")", $paidStatuses)
                                );
                      });
                })->count(),
            'total_unpaid' => 0,
            'total_revenue' => AuctionItem::where('status', 'ended')->whereNotNull('winner_id')->sum('winner_bid'),
        ];
        $stats['total_unpaid'] = $stats['total_won'] - $stats['total_paid'];

        return view('admin.auctions.won', compact('items', 'stats'));
    }

    // ── Fulfilled Items: won auctions with payment ────────────────────────────

    public function fulfilledItems(Request $request)
    {
        $paidStatuses = ['paid', 'complete', 'completed', 'success', 'approved', 'captured'];
        $placeholders = implode(',', array_fill(0, count($paidStatuses), '?'));

        $query = AuctionItem::with([
                'winner:id,name,email',
                'order' => fn($q) => $q->withoutGlobalScope(\App\Models\Concerns\ExcludeTempLaybyScope::class),
            ])
            ->whereNotNull('winner_id')
            ->where('status', 'ended')
            ->where(function ($q) use ($paidStatuses, $placeholders) {
                // Has a paid order
                $q->where(function ($inner) use ($paidStatuses, $placeholders) {
                    $inner->whereNotNull('order_id')
                          ->whereHas('order', fn($oq) =>
                              $oq->withoutGlobalScope(\App\Models\Concerns\ExcludeTempLaybyScope::class)
                                  ->whereRaw("LOWER(payment_status) IN ({$placeholders})", $paidStatuses)
                          );
                })
                // OR has fulfillment status indicating payment was confirmed (e.g. cash payment, deposit covered)
                ->orWhereIn('fulfillment_status', ['confirmed','ready_for_collection','out_for_delivery','collected','delivered']);
            })
            ->latest('ends_at');

        if ($request->filled('fulfillment_status')) {
            $query->where('fulfillment_status', $request->fulfillment_status);
        }
        if ($request->filled('method')) {
            $query->where('fulfillment_method', $request->method);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('title', 'ILIKE', "%$s%")
                  ->orWhereHas('winner', fn($u) => $u->where('name', 'ILIKE', "%$s%")->orWhere('email', 'ILIKE', "%$s%"));
            });
        }

        $items = $query->paginate(25);

        return view('admin.auctions.fulfilled', compact('items'));
    }

    public function markReady(Request $request, AuctionItem $auction)
    {
        $request->validate([
            'action' => 'required|in:ready_for_collection,out_for_delivery',
            'collection_location' => 'nullable|string|max:255',
        ]);

        $action = $request->action;
        $auction->update([
            'fulfillment_status' => $action,
            'fulfilled_at'       => now(),
        ]);

        // Fire the existing order notification — this handles the email + QR code automatically
        if ($auction->order) {
            $order = \App\Models\Order::withTempLaybyOrders()->with(['consumer', 'products', 'order_status'])->find($auction->order_id);
            if ($order) {
                // Map action to the order status slug
                $statusSlug = $action === 'ready_for_collection' ? 'ready-for-collection' : 'out-for-delivery';
                $orderStatus = \App\Models\OrderStatus::where('slug', $statusSlug)->first();
                if ($orderStatus) {
                    $order->update(['order_status_id' => $orderStatus->id]);
                    $order->refresh()->load(['order_status', 'consumer', 'products']);
                }

                // Send the existing notification (handles QR code + email template automatically)
                if ($order->consumer) {
                    $order->consumer->notify(
                        new \App\Notifications\UpdateOrderStatusNotification($order)
                    );
                }
            }
        }

        $label = $action === 'ready_for_collection' ? 'Ready for Collection' : 'Out for Delivery';
        return redirect()->back()->with('success', "✓ \"{$auction->title}\" marked as {$label}. Notification sent to winner.");
    }

    // ── Mark auction as paid (office / cash payment) ──────────────────────────

    public function markPaid(Request $request, AuctionItem $auction)
    {
        $request->validate([
            'fulfillment_method' => 'required|in:collection,delivery',
            'delivery_address'   => 'required_if:fulfillment_method,delivery|nullable|string|max:500',
        ]);

        if (!$auction->winner_id) {
            return back()->with('error', 'This auction has no winner yet.');
        }

        $winner = User::find($auction->winner_id);
        if (!$winner) {
            return back()->with('error', 'Winner user not found.');
        }

        $fulfillmentMethod = $request->fulfillment_method;
        $deliveryAddress   = $request->delivery_address;

        // Calculate amounts
        $deposit = \App\Models\AuctionBidDeposit::where('user_id', $winner->id)
            ->where('auction_item_id', $auction->id)
            ->whereNotNull('paid_at')
            ->first();

        $auctionSetting = AuctionSetting::current();
        $deliveryCost   = $fulfillmentMethod === 'delivery' ? (float) ($auctionSetting->delivery_price ?? 0) : 0;
        $depositPaid    = $deposit ? (float) $deposit->amount : 0;
        $totalDue       = (float) $auction->winner_bid + $deliveryCost;
        $amountDue      = max(0, $totalDue - $depositPaid);

        DB::transaction(function () use ($auction, $winner, $fulfillmentMethod, $deliveryAddress, $deliveryCost, $depositPaid, $amountDue) {
            // Save fulfillment info on the auction
            $auction->update([
                'fulfillment_method' => $fulfillmentMethod,
                'delivery_cost'      => $deliveryCost,
                'delivery_address'   => $deliveryAddress,
                'fulfillment_status' => 'confirmed',
                'fulfilled_at'       => now(),
            ]);
            $auction->refresh(); // ensure order_id is up-to-date

            // If an order already exists, just mark it completed
            if ($auction->order_id) {
                $order = \App\Models\Order::find($auction->order_id);
                if ($order) {
                    $order->update([
                        'payment_status' => 'completed',
                        'grand_total'    => $amountDue,
                        'total'          => $amountDue,
                        'amount'         => $amountDue,
                    ]);
                    $winner->notify(new \App\Notifications\AuctionPaymentConfirmedNotification($auction->fresh(), $order->fresh()));
                    return;
                }
            }

            // No order yet — create one for this cash payment
            $repo        = app(\App\Repositories\Eloquents\OrderRepository::class);
            $orderNumber = $repo->nextOrderNumber();
            $orderStatus = OrderStatus::where('slug', 'completed')->first()
                        ?? OrderStatus::first();

            $noteDeposit = $depositPaid > 0
                ? ' | Deposit applied: $' . number_format($depositPaid, 2) . ' | Balance paid: $' . number_format($amountDue, 2)
                : '';
            $noteDelivery = $deliveryCost > 0
                ? ' | Delivery: $' . number_format($deliveryCost, 2)
                : '';

            $order = \App\Models\Order::create([
                'consumer_id'     => $winner->id,
                'order_number'    => $orderNumber,
                'amount'          => $amountDue,
                'total'           => $amountDue,
                'grand_total'     => $amountDue,
                'payment_method'  => 'cod',
                'payment_status'  => 'completed',
                'order_status_id' => optional($orderStatus)->id ?? 1,
                'note'            => 'AUC_WIN: ' . $auction->title . ' (Lot #' . $auction->id . ')'
                                   . $noteDelivery . $noteDeposit . ' | PAID IN OFFICE',
                'currency'        => config('app.currency', 'USD'),
                'currency_symbol' => config('app.currency_symbol', '$'),
                'exchange_rate'   => 1,
                'status'          => 1,
            ]);

            $auction->update(['order_id' => $order->id]);

            $winner->notify(new \App\Notifications\AuctionPaymentConfirmedNotification($auction->fresh(), $order));
        });

        return redirect()->back()->with('success', "✓ Payment recorded for \"{$auction->title}\". Receipt sent to {$winner->email}.");
    }
    // ── Update fulfillment/order status from the fulfilled items page ──────────

    public function updateStatus(Request $request, AuctionItem $auction)
    {
        $request->validate([
            'fulfillment_status' => 'nullable|in:confirmed,ready_for_collection,out_for_delivery,collected,delivered',
            'order_status_id'    => 'nullable|integer|exists:order_status,id',
        ]);

        $notified = false;

        // Update fulfillment status on the auction
        if ($request->filled('fulfillment_status')) {
            $auction->update(['fulfillment_status' => $request->fulfillment_status]);
        }

        // Update order status (bypass scope — auction orders use AUC_WIN: prefix)
        if ($request->filled('order_status_id') && $auction->order_id) {
            \App\Models\Order::withoutGlobalScope(\App\Models\Concerns\ExcludeTempLaybyScope::class)
                ->where('id', $auction->order_id)
                ->update(['order_status_id' => $request->order_status_id]);

            // Fire notification so the winner receives an email about the status change
            $order = \App\Models\Order::withoutGlobalScope(\App\Models\Concerns\ExcludeTempLaybyScope::class)
                ->with(['consumer', 'products', 'order_status'])
                ->find($auction->order_id);

            if ($order && $order->consumer) {
                $order->consumer->notify(new \App\Notifications\UpdateOrderStatusNotification($order));
                $notified = true;
                $notifiedEmail = $order->consumer->email;
            }
        }

        $msg = "✓ Status updated for \"{$auction->title}\".";
        if ($notified) {
            $msg .= " 📧 Email notification sent to {$notifiedEmail}.";
        }

        return redirect()->back()->with('success', $msg);
    }

    // ── Unpaid Won Auctions ───────────────────────────────────────────────────

    public function unpaidItems(Request $request)
    {
        $setting  = AuctionSetting::current();
        $payHours = (int) ($setting->hours_to_pay ?? 48);
        $paidStatuses = ['paid','complete','completed','success','approved','captured'];
        $fulfilledStatuses = ['confirmed','ready_for_collection','out_for_delivery','collected','delivered'];

        // Get all ended auctions with a winner that have NOT been paid
        $query = AuctionItem::with(['winner:id,name,email'])
            ->where('status', 'ended')
            ->whereNotNull('winner_id')
            // Exclude paid auctions (either via order payment OR fulfillment status)
            ->where(function ($q) use ($paidStatuses, $fulfilledStatuses) {
                // Must NOT have a paid order
                $q->where(function ($inner) use ($paidStatuses) {
                    $inner->whereNull('order_id')
                          ->orWhereHas('order', function ($oq) use ($paidStatuses) {
                              $oq->withoutGlobalScope(\App\Models\Concerns\ExcludeTempLaybyScope::class)
                                  ->whereRaw("LOWER(payment_status) NOT IN (" . implode(',', array_fill(0, count($paidStatuses), '?')) . ")", $paidStatuses);
                          });
                });
                // AND must NOT be in a fulfilled state
                $q->where(function ($inner) use ($fulfilledStatuses) {
                    $inner->whereNull('fulfillment_status')
                          ->orWhereNotIn('fulfillment_status', $fulfilledStatuses);
                });
            });

        // Search
        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('title', 'LIKE', $s)
                  ->orWhereHas('winner', fn($uq) => $uq->where('name', 'LIKE', $s)->orWhere('email', 'LIKE', $s));
            });
        }

        // Filter by reminder status
        if ($request->filled('status')) {
            match ($request->status) {
                'pending'        => $query->whereNull('reminder_1_sent_at'),
                'reminder1_sent' => $query->whereNotNull('reminder_1_sent_at')->whereNull('reminder_2_sent_at'),
                'reminder2_sent' => $query->whereNotNull('reminder_2_sent_at'),
                'overdue' => $query->where(function ($q) use ($payHours) {
                    $q->whereNotNull('payment_deadline')->where('payment_deadline', '<', now())
                      ->orWhere(fn($q2) => $q2->whereNull('payment_deadline')->where('ends_at', '<', now()->subHours($payHours)));
                }),
                default => null,
            };
        }

        $items = $query->latest('ends_at')->paginate(20);

        return view('admin.auctions.unpaid', compact('items', 'setting'));
    }

    public function sendReminder(Request $request, AuctionItem $auction)
    {
        if (!$auction->winner) {
            return redirect()->back()->with('error', 'No winner found for this auction.');
        }

        $setting  = AuctionSetting::current();
        // Determine which reminder to use (if 1 already sent use 2, else use 1)
        $number = $auction->reminder_1_sent_at ? 2 : 1;

        // Set payment_deadline if not yet set
        if (!$auction->payment_deadline && $auction->ends_at) {
            $auction->update(['payment_deadline' => $auction->ends_at->addHours($setting->hours_to_pay ?? 48)]);
            $auction = $auction->fresh();
        }

        $auction->winner->notify(new \App\Notifications\AuctionPaymentReminderNotification($auction, $number));

        // Update the sent timestamp
        $col = $number === 1 ? 'reminder_1_sent_at' : 'reminder_2_sent_at';
        $auction->update([$col => now()]);

        return redirect()->back()->with('success', "✓ Reminder #{$number} sent to {$auction->winner->email}.");
    }

    // ── Auction Bans ──────────────────────────────────────────────────────────

    public function bansPage(Request $request)
    {
        $query = \App\Models\AuctionBan::with(['user:id,name,email', 'auctionItem', 'liftedBy:id,name'])
            ->latest('banned_at');

        if ($request->status === 'active') $query->whereNull('lifted_at');
        if ($request->status === 'lifted')  $query->whereNotNull('lifted_at');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->whereHas('user', fn($q) => $q->where('name', 'LIKE', $s)->orWhere('email', 'LIKE', $s));
        }

        $bans = $query->paginate(20);
        return view('admin.auctions.bans', compact('bans'));
    }

    public function liftBan(Request $request, \App\Models\AuctionBan $ban)
    {
        $request->validate(['lift_reason' => 'required|string|max:1000']);

        $ban->update([
            'lifted_at'   => now(),
            'lifted_by'   => auth()->id(),
            'lift_reason' => $request->lift_reason,
        ]);

        $userName = $ban->user->name ?? 'user';
        return redirect()->back()->with('success', "✓ Ban lifted for {$userName}. They can now bid again.");
    }

    // ── Deposits listing page ─────────────────────────────────────────────────

    public function deposits(Request $request)
    {
        $query = \App\Models\AuctionBidDeposit::with(['user:id,name,email', 'auctionItem:id,title'])
            ->whereNotNull('paid_at');

        // Filters
        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->whereHas('user', fn($q) => $q->where('name', 'ILIKE', $s)->orWhere('email', 'ILIKE', $s));
        }
        if ($request->filled('auction_id')) {
            $query->where('auction_item_id', $request->auction_id);
        }
        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }
        if ($request->filled('status')) {
            if ($request->status === 'refunded') {
                $query->whereNotNull('refunded_at');
            } elseif ($request->status === 'paid') {
                $query->whereNull('refunded_at');
            }
        }
        if ($request->filled('from')) {
            $query->whereDate('paid_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('paid_at', '<=', $request->to);
        }

        $deposits = $query->orderByDesc('paid_at')->paginate(40)->withQueryString();

        // Stats (global, unfiltered)
        $stats = [
            'total'        => \App\Models\AuctionBidDeposit::whereNotNull('paid_at')->count(),
            'total_amount' => (float) \App\Models\AuctionBidDeposit::whereNotNull('paid_at')->sum('amount'),
            'admin_manual' => \App\Models\AuctionBidDeposit::whereNotNull('paid_at')->where('payment_method', 'admin_manual')->count(),
            'refunded'     => \App\Models\AuctionBidDeposit::whereNotNull('paid_at')->whereNotNull('refunded_at')->count(),
        ];

        // Auction dropdown options
        $auctionOptions = AuctionItem::orderByDesc('created_at')->get(['id', 'title']);

        return view('admin.auctions.deposits', compact('deposits', 'stats', 'auctionOptions'));
    }

    // ── Active Bidders List ───────────────────────────────────────────────────
    // Lists users who have paid a deposit on any auction, OR who have been
    // manually approved to bid (auction_approved = true).

    public function bidders(Request $request)
    {
        $query = User::query()
            ->where(function ($q) {
                $q->where('auction_approved', true)
                  ->orWhereHas('auctionDeposits', fn($d) => $d->whereNotNull('paid_at'));
            })
            ->withCount(['auctionDeposits as paid_deposit_count' => fn($q) => $q->whereNotNull('paid_at')])
            ->select(['id', 'name', 'email', 'phone', 'auction_approved', 'status', 'created_at']);

        // Tab filter
        $tab = $request->get('tab', 'all');
        if ($tab === 'approved') {
            $query->where('auction_approved', true);
        } elseif ($tab === 'depositors') {
            $query->whereHas('auctionDeposits', fn($d) => $d->whereNotNull('paid_at'));
        } elseif ($tab === 'not_approved') {
            $query->where('auction_approved', false);
        }

        // Search
        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'ILIKE', $s)->orWhere('email', 'ILIKE', $s));
        }

        $users = $query->orderBy('name')->paginate(30)->withQueryString();

        $counts = [
            'all'          => User::where(fn($q) => $q->where('auction_approved', true)->orWhereHas('auctionDeposits', fn($d) => $d->whereNotNull('paid_at')))->count(),
            'approved'     => User::where('auction_approved', true)->count(),
            'depositors'   => User::whereHas('auctionDeposits', fn($d) => $d->whereNotNull('paid_at'))->count(),
            'not_approved' => User::whereHas('auctionDeposits', fn($d) => $d->whereNotNull('paid_at'))->where('auction_approved', false)->count(),
        ];

        // For the "Add Deposit" modal
        $auctions = AuctionItem::whereIn('status', ['active', 'draft'])
            ->orderBy('starts_at')
            ->get(['id', 'title', 'status', 'starts_at', 'ends_at']);

        $defaultDepositAmount = (float) AuctionSetting::current()->bid_fee_amount;

        return view('admin.auctions.bidders', compact('users', 'tab', 'counts', 'auctions', 'defaultDepositAmount'));
    }

    // ── Toggle auction_approved on a user ─────────────────────────────────────

    public function toggleBidderApproval(Request $request, User $user)
    {
        $user->update(['auction_approved' => !$user->auction_approved]);

        $status = $user->fresh()->auction_approved ? 'approved' : 'revoked';
        return redirect()->back()->with('success', "✓ Bidding approval {$status} for {$user->name}.");
    }

    // ── Reorder auctions (drag-and-drop) ─────────────────────────────────────
    // Accepts JSON: [{id: 1, sort_order: 0}, {id: 2, sort_order: 1}, ...]

    public function reorderAuctions(Request $request)
    {
        $request->validate([
            'order'             => 'required|array',
            'order.*.id'        => 'required|integer|exists:auction_items,id',
            'order.*.sort_order'=> 'required|integer|min:0',
        ]);

        foreach ($request->order as $item) {
            AuctionItem::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true]);
    }

    // ── Admin: manually credit a deposit for a user on an auction ─────────────
    // Creates an AuctionBidDeposit marked as paid immediately (no payment gateway).

    public function addDeposit(Request $request)
    {
        $request->validate([
            'user_id'         => 'required|integer|exists:users,id',
            'auction_item_id' => 'required|integer|exists:auction_items,id',
            'amount'          => 'required|numeric|min:0.01',
            'notes'           => 'nullable|string|max:500',
        ]);

        // Check for an existing unpaid or paid deposit for this user+auction
        $existing = \App\Models\AuctionBidDeposit::where('user_id', $request->user_id)
            ->where('auction_item_id', $request->auction_item_id)
            ->first();

        if ($existing && $existing->paid_at) {
            return redirect()->back()->with('error', 'This user already has a paid deposit for this auction.');
        }

        if ($existing) {
            // Update the existing unpaid deposit and mark it paid
            $existing->update([
                'amount'         => $request->amount,
                'payment_method' => 'admin_manual',
                'paid_at'        => now(),
            ]);
            $deposit = $existing;
        } else {
            $deposit = \App\Models\AuctionBidDeposit::create([
                'user_id'         => $request->user_id,
                'auction_item_id' => $request->auction_item_id,
                'amount'          => $request->amount,
                'payment_method'  => 'admin_manual',
                'paid_at'         => now(),
            ]);
        }

        $user    = User::find($request->user_id);
        $auction = AuctionItem::find($request->auction_item_id);

        return redirect()->back()->with('success',
            "✅ Deposit of \${$deposit->amount} recorded for {$user->name} on \"{$auction->title}\". They can now bid on this auction."
        );
    }

    // ── AJAX: search users for deposit modal ─────────────────────────────────

    public function searchUsersForDeposit(Request $request)
    {
        $term = '%' . $request->get('q', '') . '%';
        $users = User::where(fn($q) => $q->where('name', 'ILIKE', $term)->orWhere('email', 'ILIKE', $term))
            ->select('id', 'name', 'email')
            ->limit(15)
            ->get();
        return response()->json($users);
    }
}
