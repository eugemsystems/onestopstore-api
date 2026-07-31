<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\LateDeliveryApologyMail;
use App\Models\Order;
use App\Models\OrderApologyEmail;
use App\Models\OrderApologySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AdminLateOrdersController extends Controller
{
    // Order statuses that mean the order is finished — exclude from list
    private const EXCLUDED_ORDER_SLUGS = [
        'cancelled', 'delivered', 'collected',
        'ready-for-collection', 'ready_for_collection', 'ready for collection',
        'ready-for-delivery', 'ready_for_delivery',
    ];

    // Payment statuses that count as "paid"
    private const PAID_STATUSES = [
        'Success', 'COMPLETED', 'COMPLETE', 'CASH_ON_DELIVERY', 'Credit',
    ];

    // Item-level statuses that mean the item is done — skip when checking overdue
    private const EXCLUDED_ITEM_STATUSES = [
        'cancelled', 'out of stock', 'delivered', 'collected', 'ready for collection',
        'out_of_stock',
    ];

    public function index(Request $request): View
    {
        $search = trim((string) $request->get('search', ''));

        $orders = Order::with([
                'consumer:id,name,email',
                'order_status:id,name,slug',
                'apologyEmail',
            ])
            ->whereIn('payment_status', self::PAID_STATUSES)
            ->whereHas('order_status', fn ($q) =>
                $q->whereNotIn('slug', self::EXCLUDED_ORDER_SLUGS)
            )
            ->whereHas('products', fn ($q) =>
                $q->whereNotNull('order_products.eta')
                  ->whereRaw("order_products.eta::date < CURRENT_DATE")
                  ->whereNotIn('order_products.item_status', self::EXCLUDED_ITEM_STATUSES)
                  ->whereNull('order_products.deleted_at')
            )
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('order_number', 'like', "%{$search}%")
                          ->orWhereHas('consumer', fn ($c) =>
                              $c->where('name', 'ilike', "%{$search}%")
                                ->orWhere('email', 'ilike', "%{$search}%")
                          );
                });
            })
            ->orderByRaw("
                CASE WHEN EXISTS (
                    SELECT 1 FROM order_apology_emails oae
                    WHERE oae.order_id = orders.id
                    AND oae.next_send_at > NOW()
                ) THEN 1 ELSE 0 END ASC
            ")
            ->latest('orders.created_at')
            ->paginate(30)
            ->withQueryString();

        // Attach per-order overdue item count (efficient: one extra query for the page)
        $orderIds = $orders->pluck('id');

        $overdueCounts = \DB::table('order_products')
            ->whereIn('order_id', $orderIds)
            ->whereNotNull('eta')
            ->whereRaw("eta::date < CURRENT_DATE")
            ->whereNotIn('item_status', self::EXCLUDED_ITEM_STATUSES)
            ->whereNull('deleted_at')
            ->selectRaw('order_id, COUNT(*) as cnt')
            ->groupBy('order_id')
            ->pluck('cnt', 'order_id');

        return view('admin.late-orders.index', compact('orders', 'overdueCounts', 'search'));
    }

    public function sendApology(Request $request, int $orderId): RedirectResponse
    {
        // Load order without apologyEmail to avoid serialization issues
        $order = Order::with([
            'consumer:id,name,email',
            'order_status:id,name,slug',
            'shipping_address',
            'products',
        ])->findOrFail($orderId);

        // Load apology record separately (not on the order model)
        $apologyRecord = OrderApologyEmail::where('order_id', $orderId)->first();

        // Guard: order must still be active
        if (in_array(strtolower($order->order_status->slug ?? ''), self::EXCLUDED_ORDER_SLUGS)) {
            return back()->with('error', 'This order is already in a terminal state.');
        }

        // Filter to only overdue, active items — convert to plain arrays immediately
        // so the mailable never holds live Eloquent models (avoids pivot data loss on queue)
        $overdueItems = $order->products
            ->filter(function ($product) {
                $status = strtolower($product->pivot->item_status ?? '');
                if (in_array($status, self::EXCLUDED_ITEM_STATUSES)) return false;
                $eta = $product->pivot->eta ?? null;
                if (!$eta) return false;
                return now()->toDateString() > $eta;
            })
            ->map(function ($product) {
                $eta = $product->pivot->eta ?? null;
                $daysOverdue = $eta
                    ? max(0, (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($eta)->startOfDay(), false) * -1)
                    : 0;
                return [
                    'name'         => $product->pivot->product_name ?? $product->name ?? 'Item',
                    'variation'    => $product->pivot->variation_display_name ?? null,
                    'eta'          => $eta,
                    'days_overdue' => $daysOverdue,
                ];
            })
            ->values()
            ->toArray();

        if (empty($overdueItems)) {
            return back()->with('error', 'No overdue items found for this order — nothing to apologise for.');
        }

        // Check cooldown
        if ($apologyRecord && !$apologyRecord->canSendNow()) {
            $days = $apologyRecord->daysUntilNextSend();
            return back()->with('error', "Apology email was sent recently. Next one can be sent in {$days} " . ($days === 1 ? 'day' : 'days') . '.');
        }

        // Unset products before queuing — pivot data doesn't survive SerializesModels
        // restore. The template only needs consumer/order_status/shipping_address.
        $order->unsetRelation('products');

        try {
            $emailNumber = ($apologyRecord->sent_count ?? 0) + 1;
            Mail::to($order->consumer->email)
                ->send(new LateDeliveryApologyMail($order, $overdueItems, $emailNumber));
        } catch (\Throwable $e) {
            \Log::error('LateDeliveryApologyMail failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
            return back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }

        // Record
        $cooldown = OrderApologySetting::current()->cooldown_days;
        OrderApologyEmail::updateOrCreate(
            ['order_id' => $order->id],
            [
                'sent_count'   => ($apologyRecord->sent_count ?? 0) + 1,
                'last_sent_at' => now(),
                'next_send_at' => now()->addDays($cooldown),
            ]
        );

        return back()->with('success', "Apology email sent to {$order->consumer->email} for order #{$order->order_number}.");
    }
}
