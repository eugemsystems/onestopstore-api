<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryShipment;

class SyncInventoryShipmentTracking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:sync-tracking {--dry-run : Run without making changes} {--order= : Sync specific order number}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync existing inventory shipments with order_products tracking columns';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $specificOrder = $this->option('order');

        $this->info('Starting inventory shipment tracking sync...');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Query inventory shipments that have order numbers or order IDs
        $query = InventoryShipment::whereNotNull('order')
            ->where('order', '>', 0);

        if ($specificOrder) {
            $query->where('order', $specificOrder);
            $this->info("Filtering by order: {$specificOrder}");
        }

        $shipments = $query->orderBy('created_at', 'asc')->get();

        if ($shipments->isEmpty()) {
            $this->warn('No inventory shipments with order information found.');
            return 0;
        }

        $this->info("Found {$shipments->count()} inventory shipment(s) to process");
        $this->newLine();

        $stats = [
            'processed' => 0,
            'updated' => 0,
            'skipped_no_match' => 0,
            'skipped_already_linked' => 0,
            'errors' => 0,
        ];

        $progressBar = $this->output->createProgressBar($shipments->count());
        $progressBar->start();

        foreach ($shipments as $shipment) {
            $stats['processed']++;

            try {
                $order = null;

                // The 'order' field might be an order ID (integer) or order number (string)
                // Try to find order by both methods
                if (is_numeric($shipment->order)) {
                    // Could be order ID or order number
                    $order = DB::table('orders')
                        ->where(function($q) use ($shipment) {
                            $q->where('id', $shipment->order)
                              ->orWhere('order_number', $shipment->order);
                        })
                        ->whereNull('deleted_at')
                        ->first();
                } else {
                    // Definitely an order number (string)
                    $order = DB::table('orders')
                        ->where('order_number', $shipment->order)
                        ->whereNull('deleted_at')
                        ->first();
                }

                if (!$order) {
                    $stats['skipped_no_match']++;
                    $progressBar->advance();
                    continue;
                }

                // Try to find matching order_product by title (product name)
                // Extract product name from shipment title (before " - " if variation exists)
                $productNameParts = explode(' - ', $shipment->title);
                $productName = trim($productNameParts[0]);

                // Find matching order_products
                $orderProducts = DB::table('order_products as op')
                    ->join('products as p', 'op.product_id', '=', 'p.id')
                    ->where('op.order_id', $order->id)
                    ->whereNull('op.deleted_at')
                    ->where(function($q) use ($productName, $shipment) {
                        // Match by product name
                        $q->whereRaw('LOWER(p.name) = ?', [strtolower($productName)])
                          // Or match by full title including variation
                          ->orWhere(function($q2) use ($shipment) {
                              $q2->whereRaw('LOWER(CONCAT(p.name, \' - \', op.variation_display_name)) = ?', [strtolower($shipment->title)]);
                          });
                    })
                    ->select('op.id', 'op.added_to_inventory', 'op.inventory_shipment_id', 'p.name', 'op.variation_display_name')
                    ->get();

                if ($orderProducts->isEmpty()) {
                    $stats['skipped_no_match']++;
                    $progressBar->advance();
                    continue;
                }

                // Update order_products (prefer items not already linked)
                foreach ($orderProducts as $orderProduct) {
                    // Skip if already linked to another shipment
                    if ($orderProduct->added_to_inventory && $orderProduct->inventory_shipment_id) {
                        $stats['skipped_already_linked']++;
                        continue;
                    }

                    if (!$isDryRun) {
                        DB::table('order_products')
                            ->where('id', $orderProduct->id)
                            ->update([
                                'added_to_inventory' => true,
                                'inventory_shipment_id' => $shipment->id,
                                'added_to_inventory_at' => $shipment->created_at,
                                'updated_at' => now(),
                            ]);
                    }

                    $stats['updated']++;

                    // Only update the first matching product per shipment
                    break;
                }

            } catch (\Exception $e) {
                $stats['errors']++;
                $this->error("\nError processing shipment ID {$shipment->id}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display results
        $this->info('Sync completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $stats['processed']],
                ['Updated', $stats['updated']],
                ['Skipped (No Match)', $stats['skipped_no_match']],
                ['Skipped (Already Linked)', $stats['skipped_already_linked']],
                ['Errors', $stats['errors']],
            ]
        );

        if ($isDryRun) {
            $this->warn('This was a DRY RUN - no changes were made');
            $this->info('Run without --dry-run to apply changes');
        }

        return 0;
    }
}
