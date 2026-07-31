<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Jobs\SyncOrderToCrm;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncOrdersToCrm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:sync-to-crm
                            {date : The date to sync orders from (Y-m-d format, e.g., 2025-12-21)}
                            {--status=* : Specific status(es) to sync. Leave empty to sync all non-pending statuses}
                            {--force : Skip confirmation prompt}
                            {--dry-run : Show what would be synced without actually syncing}
                            {--skip-crm-check : Skip checking if order exists in CRM (faster but may create duplicates)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync orders from a specific date to CRM (excluding pending and cancelled orders)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->argument('date');
        $statusFilter = $this->option('status');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');
        $skipCrmCheck = $this->option('skip-crm-check');

        // Validate date format
        try {
            $targetDate = Carbon::createFromFormat('Y-m-d', $date);
        } catch (\Exception $e) {
            $this->error("Invalid date format. Please use Y-m-d format (e.g., 2025-12-21)");
            return 1;
        }

        $this->info("=== Order CRM Sync Command ===");
        $this->info("Target Date: {$targetDate->format('Y-m-d')}");

        if ($dryRun) {
            $this->warn("DRY RUN MODE - No orders will be synced");
        }

        if (!empty($statusFilter)) {
            $this->info("Status Filter: " . implode(', ', $statusFilter));
        }

        if ($skipCrmCheck) {
            $this->warn("Skipping CRM duplicate check (may create duplicates!)");
        }

        $this->line("");

        // Query orders from the target date
        $query = Order::whereNull('parent_id')
            ->whereDate('created_at', $targetDate->format('Y-m-d'))
            ->where('is_gift_order', false)
            ->with(['order_status']);

        $orders = $query->get();

        $this->info("Found " . $orders->count() . " parent orders from {$targetDate->format('Y-m-d')}");

        // Filter orders
        $ordersToSync = $orders->filter(function($order) use ($statusFilter) {
            $statusName = strtolower(trim($order->order_status?->name ?? ''));
            $statusSlug = strtolower(trim($order->order_status?->slug ?? ''));

            // Always exclude pending orders (should never go to CRM)
            if ($statusName === 'pending' || $statusSlug === 'pending') {
                return false;
            }

            // Always exclude cancelled orders (should not be synced to CRM)
            if ($statusName === 'cancelled' || $statusSlug === 'cancelled') {
                return false;
            }

            // If specific statuses are requested, filter by them
            if (!empty($statusFilter)) {
                foreach ($statusFilter as $filterStatus) {
                    $filterStatus = strtolower(trim($filterStatus));
                    if ($statusName === $filterStatus || $statusSlug === $filterStatus) {
                        return true;
                    }
                }
                return false;
            }

            return true;
        });

        $this->info("Orders to sync (excluding pending and cancelled): " . $ordersToSync->count());
        $this->line("");

        if ($ordersToSync->isEmpty()) {
            $this->warn("No orders to sync. Exiting.");
            return 0;
        }

        // Display orders in a table
        $tableData = [];
        foreach ($ordersToSync as $order) {
            $tableData[] = [
                $order->order_number,
                $order->order_status?->name ?? 'N/A',
                $order->consumer?->name ?? 'N/A',
                $order->total,
                $order->created_at->format('Y-m-d H:i:s')
            ];
        }

        $this->table(
            ['Order Number', 'Status', 'Customer', 'Total', 'Created At'],
            $tableData
        );

        // Confirm before proceeding
        if (!$force) {
            if (!$this->confirm("Do you want to proceed with syncing these " . $ordersToSync->count() . " orders?", true)) {
                $this->warn("Sync cancelled by user.");
                return 0;
            }
        }

        $this->line("");
        $this->info("Starting sync...");
        $this->line("");

        $progressBar = $this->output->createProgressBar($ordersToSync->count());
        $progressBar->start();

        $successCount = 0;
        $failedCount = 0;
        $skippedCount = 0;
        $duplicateCount = 0;
        $failed = [];
        $duplicates = [];

        foreach ($ordersToSync as $order) {
            try {
                // Check if order has auto-cancelled note
                $orderNote = $order->note ?? '';
                if (str_contains($orderNote, '[AUTO_CANCELLED]')) {
                    $skippedCount++;
                    $progressBar->advance();
                    continue;
                }

                // Check if order already exists in CRM (unless skipping check)
                // Check by order ID (which is used as the primary key in CRM)
                if (!$skipCrmCheck) {
                    if ($this->orderExistsInCrm($order->id, $order->order_number)) {
                        $duplicates[] = [
                            'order_number' => $order->order_number,
                            'order_id' => $order->id,
                            'status' => $order->order_status?->name ?? 'N/A',
                        ];
                        $duplicateCount++;
                        $progressBar->advance();
                        continue;
                    }
                }

                // If dry-run, just count and continue
                if ($dryRun) {
                    $successCount++;
                    $progressBar->advance();
                    continue;
                }

                // Retrigger the order status to sync to CRM via OrderObserver
                // This is the proper way - it triggers the observer which handles all sync logic
                // including checking for pending status, gift orders, etc.
                $currentStatusId = $order->order_status_id;

                // Update the order with the same status (this triggers the observer)
                // The observer checks: parent_id is null, not a gift order, and dispatches SyncOrderToCrm
                $order->update(['order_status_id' => $currentStatusId]);

                $successCount++;

            } catch (\Exception $e) {
                $failed[] = [
                    'order_number' => $order->order_number,
                    'error' => $e->getMessage()
                ];
                $failedCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line("");
        $this->line("");

        // Display summary
        $this->info("=== Sync Summary ===");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total orders found', $orders->count()],
                ['Orders to sync', $ordersToSync->count()],
                ['Already in CRM (skipped)', $duplicateCount],
                ['Auto-cancelled (skipped)', $skippedCount],
                [$dryRun ? 'Would be dispatched' : 'Successfully dispatched', $successCount],
                ['Failed', $failedCount],
            ]
        );

        // Display duplicate orders if any
        if ($duplicateCount > 0) {
            $this->line("");
            $this->warn("Orders Already in CRM (Skipped):");
            $this->table(['Order ID', 'Order Number', 'Status'], $duplicates);
        }

        // Display failed orders if any
        if ($failedCount > 0) {
            $this->line("");
            $this->error("Failed Orders:");
            $this->table(['Order Number', 'Error'], $failed);
        }

        $this->line("");

        if ($dryRun) {
            $this->comment("DRY RUN COMPLETE - No orders were actually synced.");
            $this->comment("Run without --dry-run to perform the actual sync.");
        } else {
            $this->comment("Note: Order status updates have been triggered, which dispatch sync jobs via OrderObserver.");
            $this->comment("Jobs are queued and will be processed by your queue worker.");
            $this->comment("You can monitor the queue with: php artisan queue:work");
        }

        return 0;
    }

    /**
     * Check if an order exists in the CRM system
     *
     * @param int $orderId The order ID (not order number)
     * @param int $orderNumber The order number (for logging only)
     * @return bool
     */
    protected function orderExistsInCrm(int $orderId, int $orderNumber): bool
    {
        try {
            $crmBaseUrl = rtrim(config('services.crm.base_url'), '/');

            if (!$crmBaseUrl) {
                $this->warn("CRM base URL not configured. Skipping duplicate check.");
                return false;
            }

            // Use the API token for authentication (same token CRM uses to call API)
            $apiToken = env('CRM_API_TOKEN');

            if (!$apiToken) {
                $this->warn("CRM_API_TOKEN not configured. Skipping duplicate check.");
                return false;
            }

            // Check if order exists in CRM database by ORDER ID (not order number)
            // CRM stores orders using the API's order ID as the primary key
            $response = \Illuminate\Support\Facades\Http::timeout(5)
                ->withToken($apiToken)
                ->get("{$crmBaseUrl}/api/orders/check-by-id/{$orderId}");

            if ($response->successful()) {
                $data = $response->json();
                return (bool) ($data['exists'] ?? false);
            }

            // Fallback: If the endpoint doesn't exist, try the old method
            // Try to get the order by ID - if it returns 200, it exists
            $response = \Illuminate\Support\Facades\Http::timeout(5)
                ->withToken($apiToken)
                ->get("{$crmBaseUrl}/api/orders/by-id/{$orderId}");

            return $response->successful();

        } catch (\Exception $e) {
            $this->warn("Failed to check CRM for order ID {$orderId} (#{$orderNumber}): " . $e->getMessage());
            return false; // Assume it doesn't exist if we can't check
        }
    }
}
