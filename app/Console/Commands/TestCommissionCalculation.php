<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\CommissionHistory;
use App\Http\Traits\CommissionTrait;
use App\Enums\PaymentStatus;

class TestCommissionCalculation extends Command
{
    use CommissionTrait;

    protected $signature = 'commission:test {orderId?}';
    protected $description = 'Test commission calculation on an order';

    public function handle()
    {
        $this->info('=== COMMISSION CALCULATION TEST ===');
        $this->newLine();

        $orderId = $this->argument('orderId');

        if ($orderId) {
            $order = Order::with(['orderStatus', 'products.categories', 'products.store'])
                ->find($orderId);
        } else {
            // Find most recent order with completed payment
            $order = Order::with(['orderStatus', 'products.categories', 'products.store'])
                ->where('payment_status', PaymentStatus::COMPLETED)
                ->whereHas('orderStatus', function($q) {
                    $q->whereIn('name', ['delivered', 'collected']);
                })
                ->latest()
                ->first();

            if (!$order) {
                $this->warn('No orders found with COMPLETED payment and DELIVERED/COLLECTED status');
                $order = Order::with(['orderStatus', 'products'])->latest()->first();
            }
        }

        if (!$order) {
            $this->error('No orders found in database!');
            return 1;
        }

        $this->info("Order: {$order->order_number}");
        $this->info("Payment Status: {$order->payment_status}");
        $this->info("Order Status: " . ($order->orderStatus ? $order->orderStatus->name : 'NULL'));
        $this->info("Products: " . $order->products->count());

        // Check products with store_id
        $productsWithStore = $order->products->filter(fn($p) => !is_null($p->store_id));
        $this->info("Products with Store ID: " . $productsWithStore->count());

        if ($productsWithStore->count() > 0) {
            $this->newLine();
            $this->info('Products by Store:');
            foreach ($productsWithStore->groupBy('store_id') as $storeId => $products) {
                $this->line("  Store ID {$storeId}: {$products->count()} product(s)");
            }
        }

        // Check existing commission
        $existing = CommissionHistory::where('order_id', $order->id)->count();
        $this->info("Existing Commissions: {$existing}");

        $this->newLine();
        if (!$this->confirm('Proceed with commission calculation?', true)) {
            return 0;
        }

        $this->info('--- CALCULATING COMMISSION ---');
        $this->newLine();

        try {
            $this->adminVendorCommission($order);
            $this->info('✅ Commission calculation completed!');
        } catch (\Exception $e) {
            $this->error('❌ Commission calculation failed!');
            $this->error($e->getMessage());
            $this->newLine();
            $this->error($e->getTraceAsString());
            return 1;
        }

        // Check results
        $commissions = CommissionHistory::where('order_id', $order->id)->get();
        $this->newLine();
        $this->info("Commission Records: " . $commissions->count());

        foreach ($commissions as $comm) {
            $this->line("  Store ID {$comm->store_id}: Admin={$comm->admin_commission}, Vendor={$comm->vendor_commission}");
        }

        $this->newLine();
        $this->info('Check logs at: storage/logs/laravel.log');

        return 0;
    }
}

