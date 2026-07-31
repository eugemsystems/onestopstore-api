<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CancelOldPendingOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cancel-old-pending
                            {--hours= : Override the default hours (72)}
                            {--dry-run : Show what would be cancelled without actually cancelling}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically cancel pending orders older than specified hours (default: 72 hours)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Checking for old pending orders...');
        $this->newLine();

        // Get the hours from option or settings or default to 72
        $hours = $this->option('hours');

        if (!$hours) {
            // Try to get from settings
            $setting = Setting::first();
            $values = $setting ? $setting->values : [];
            $hours = $values['pending_order_auto_cancel_hours'] ?? 72;
        }

        $isDryRun = $this->option('dry-run');

        $this->info("⏱️  Cancellation threshold: {$hours} hours");
        $this->info("🔧 Mode: " . ($isDryRun ? 'DRY RUN (no changes will be made)' : 'LIVE'));
        $this->newLine();

        // Get the pending status ID
        $pendingStatus = OrderStatus::where('slug', 'pending')->first();
        if (!$pendingStatus) {
            $this->error('❌ Pending status not found in database!');
            return 1;
        }

        // Get the cancelled status ID
        $cancelledStatus = OrderStatus::where('slug', 'cancelled')->first();
        if (!$cancelledStatus) {
            $this->error('❌ Cancelled status not found in database!');
            return 1;
        }

        // Find orders older than specified hours that are still pending
        $cutoffTime = now()->subHours($hours);

        $oldPendingOrders = Order::where('order_status_id', $pendingStatus->id)
            ->where('created_at', '<=', $cutoffTime)
            ->with(['consumer', 'orderStatus'])
            ->get();

        if ($oldPendingOrders->isEmpty()) {
            $this->info('✅ No old pending orders found. All clear!');
            return 0;
        }

        $this->warn("⚠️  Found {$oldPendingOrders->count()} order(s) to cancel:");
        $this->newLine();

        // Create progress bar
        $bar = $this->output->createProgressBar($oldPendingOrders->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');

        $cancelledCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($oldPendingOrders as $order) {
            $orderAge = $order->created_at->diffInHours(now());
            $customerName = $order->consumer ? $order->consumer->name : 'Guest';

            $bar->setMessage("Processing Order #{$order->order_number} ({$customerName})");

            if (!$isDryRun) {
                try {
                    // Update order status to cancelled
                    $order->order_status_id = $cancelledStatus->id;
                    $order->save();


                    $cancelledCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = [
                        'order' => $order->order_number,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to cancel order', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                // Dry run - just count
                $cancelledCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Display results in a table
        $this->table(
            ['Order #', 'Customer', 'Created', 'Age (hours)', 'Total Amount'],
            $oldPendingOrders->map(function ($order) {
                return [
                    $order->order_number,
                    $order->consumer ? $order->consumer->name : 'Guest',
                    $order->created_at->format('Y-m-d H:i'),
                    $order->created_at->diffInHours(now()),
                    $order->currency_symbol . number_format($order->total, 2),
                ];
            })->toArray()
        );

        $this->newLine();

        // Summary
        if ($isDryRun) {
            $this->warn('🔍 DRY RUN COMPLETE - No changes were made');
            $this->info("📊 Would have cancelled: {$cancelledCount} order(s)");
        } else {
            $this->info('✅ CANCELLATION COMPLETE');
            $this->info("✓ Successfully cancelled: {$cancelledCount} order(s)");

            if ($failedCount > 0) {
                $this->error("✗ Failed to cancel: {$failedCount} order(s)");
                $this->newLine();
                $this->error('Failed orders:');
                foreach ($errors as $error) {
                    $this->error("  - Order #{$error['order']}: {$error['error']}");
                }
            }
        }

        return 0;
    }
}

