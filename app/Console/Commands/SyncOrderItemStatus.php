<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncOrderItemStatus extends Command
{
    protected $signature = 'orders:sync-item-status
                            {--order-id= : Specific order ID to sync}
                            {--dry-run : Run without making changes}';

    protected $description = 'Synchronize order_products item_status with their parent order status';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $orderId = $this->option('order-id');

        $this->info('Starting order_products item_status synchronization...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $query = Order::with('order_status');

        if ($orderId) {
            $query->where('id', $orderId);
            $this->info("Processing order ID: {$orderId}");
        }

        $orders = $query->get();
        $this->info("Found {$orders->count()} orders to process\n");

        $totalItemsUpdated = 0;
        $progressBar = $this->output->createProgressBar($orders->count());

        foreach ($orders as $order) {
            try {
                $orderStatusSlug = $order->order_status
                    ? strtolower(trim($order->order_status->slug ?? $order->order_status->name ?? ''))
                    : 'pending';

                $expectedItemStatus = $this->mapOrderStatusToItemStatus($orderStatusSlug);

                if (!$dryRun) {
                    $updated = DB::table('order_products')
                        ->where('order_id', $order->id)
                        ->whereNull('deleted_at')
                        ->update([
                            'item_status' => $expectedItemStatus,
                            'updated_at' => now()
                        ]);

                    $totalItemsUpdated += $updated;
                } else {
                    $count = DB::table('order_products')
                        ->where('order_id', $order->id)
                        ->whereNull('deleted_at')
                        ->count();

                    $this->line("\nOrder #{$order->order_number}: Would update {$count} items to '{$expectedItemStatus}'");
                }

                $progressBar->advance();

            } catch (\Exception $e) {
                $this->error("\nError processing order {$order->id}: " . $e->getMessage());
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("=================================");
        $this->info("SUMMARY");
        $this->info("=================================");
        $this->info("Total Orders Processed: {$orders->count()}");
        $this->info("Total Items Updated: {$totalItemsUpdated}");

        if ($dryRun) {
            $this->warn("\nThis was a DRY RUN. Run without --dry-run to apply changes.");
        } else {
            $this->info("\n✓ Synchronization complete!");
        }

        return 0;
    }

    protected function mapOrderStatusToItemStatus($orderStatusSlug)
    {
        $s = strtolower(trim((string) ($orderStatusSlug ?? '')));
        $map = [
            'pending' => 'pending',
            'processing' => 'processing',
            'warehouse packing' => 'processing',
            'warehouse-packing' => 'processing',
            'from supplier' => 'processing',
            'from-supplier' => 'processing',
            'stuck' => 'processing',
            'shipped' => 'shipped',
            'in transit to zim' => 'shipped',
            'in-transit-to-zim' => 'shipped',
            'dropped at the deport' => 'shipped',
            'dropped-at-the-deport' => 'shipped',
            'out for delivery' => 'shipped',
            'out-for-delivery' => 'shipped',
            'ready for collection' => 'shipped',
            'ready-for-collection' => 'shipped',
            'delivered' => 'delivered',
            'collected' => 'delivered',
            'cancelled' => 'cancelled',
            'canceled' => 'cancelled',
        ];

        return $map[$s] ?? 'pending';
    }
}
