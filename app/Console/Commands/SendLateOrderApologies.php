<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\LateDeliveryApologyMail;
use App\Models\Order;
use App\Models\OrderApologyEmail;
use App\Models\OrderApologySetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendLateOrderApologies extends Command
{
    protected $signature = 'orders:send-late-apologies {--dry-run : List eligible orders without sending}';
    protected $description = 'Send apology emails for paid orders with overdue items (respects cooldown setting)';

    private const EXCLUDED_ORDER_SLUGS = [
        'cancelled', 'delivered', 'collected',
        'ready-for-collection', 'ready_for_collection', 'ready for collection',
        'ready-for-delivery', 'ready_for_delivery',
    ];

    private const PAID_STATUSES = [
        'Success', 'COMPLETED', 'COMPLETE', 'CASH_ON_DELIVERY', 'Credit',
    ];

    private const EXCLUDED_ITEM_STATUSES = [
        'cancelled', 'out of stock', 'delivered', 'collected', 'ready for collection',
        'out_of_stock',
    ];

    public function handle(): int
    {
        $settings = OrderApologySetting::current();
        $dryRun   = $this->option('dry-run');

        $orders = Order::with([
                'consumer:id,name,email',
                'order_status:id,name,slug',
                'shipping_address',
                'products',
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
            ->get();

        $sent   = 0;
        $skipped = 0;

        foreach ($orders as $order) {
            $apologyRecord = $order->apologyEmail;

            // Skip if still in cooldown
            if ($apologyRecord && !$apologyRecord->canSendNow()) {
                $skipped++;
                continue;
            }

            // Build overdue items as plain arrays
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
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line("  Would send to: {$order->consumer->email} — Order #{$order->order_number} ({$order->consumer->name}) — " . count($overdueItems) . ' overdue item(s)');
                $sent++;
                continue;
            }

            $order->unsetRelation('products');
            $order->unsetRelation('apologyEmail');

            $emailNumber = ($apologyRecord->sent_count ?? 0) + 1;

            try {
                Mail::to($order->consumer->email)
                    ->send(new LateDeliveryApologyMail($order, $overdueItems, $emailNumber));
            } catch (\Throwable $e) {
                \Log::error('SendLateOrderApologies: mail failed', [
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);
                $this->warn("  Failed for order #{$order->order_number}: {$e->getMessage()}");
                continue;
            }

            OrderApologyEmail::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'sent_count'   => $emailNumber,
                    'last_sent_at' => now(),
                    'next_send_at' => now()->addDays($settings->cooldown_days),
                ]
            );

            $this->line("  Sent to: {$order->consumer->email} — Order #{$order->order_number}");
            $sent++;
        }

        $label = $dryRun ? 'Would send' : 'Sent';
        $this->info("{$label}: {$sent} | Skipped (cooldown): {$skipped}");

        return self::SUCCESS;
    }
}
