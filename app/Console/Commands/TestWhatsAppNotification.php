<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Notifications\UpdateOrderStatusNotification;
use Illuminate\Console\Command;

class TestWhatsAppNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:whatsapp-notification {order_number?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test WhatsApp order status notification';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orderNumber = $this->argument('order_number');

        // Get order
        if ($orderNumber) {
            $order = Order::with(['consumer', 'order_status', 'products'])
                ->where('order_number', $orderNumber)
                ->first();
        } else {
            $order = Order::with(['consumer', 'order_status', 'products'])->first();
        }

        if (!$order) {
            $this->error('❌ No orders found in database');
            return 1;
        }

        $this->info("📦 Testing with Order: #{$order->order_number}");
        $this->info("👤 Consumer: {$order->consumer->name}");
        $this->info("📱 Phone: {$order->consumer->phone}");
        $this->info("📊 Status: {$order->order_status->name}");
        $this->newLine();

        // Check if WhatsApp is enabled
        $whatsappEnabled = config('services.msg91.whatsapp_enabled');
        $this->info("WhatsApp Enabled: " . ($whatsappEnabled ? "✅ YES" : "❌ NO"));

        if (!$whatsappEnabled) {
            $this->warn("⚠️  Enable WhatsApp in .env: MSG91_WHATSAPP_ENABLED=true");
            return 1;
        }

        // Check MSG91 config
        $this->newLine();
        $this->info("MSG91 Configuration:");
        $this->info("- Auth Key: " . (config('services.msg91.auth_key') ? "✅ Set" : "❌ Missing"));
        $this->info("- Sender ID: " . (config('services.msg91.whatsapp_sender_id') ?: "❌ Missing"));
        $this->info("- Namespace: " . (config('services.msg91.namespace') ?: "❌ Missing"));

        // Check if consumer has phone
        if (!$order->consumer->phone) {
            $this->error("❌ Consumer has no phone number");
            return 1;
        }

        // Create notification instance
        $notification = new UpdateOrderStatusNotification($order);

        // Get WhatsApp data that would be sent
        $whatsappData = $notification->toWhatsApp($order->consumer);

        $this->newLine();
        $this->info("📤 WhatsApp Message Data:");
        $this->line("Template: " . ($whatsappData['template_id'] ?? 'N/A'));
        $this->line("Variables: " . json_encode($whatsappData['variables'] ?? [], JSON_PRETTY_PRINT));
        $this->line("Options: " . json_encode($whatsappData['options'] ?? [], JSON_PRETTY_PRINT));

        // Ask for confirmation
        if (!$this->confirm('Do you want to send this WhatsApp notification?', true)) {
            $this->info('Cancelled.');
            return 0;
        }

        // Test sending
        $this->newLine();
        $this->info("🚀 Sending test notification...");

        try {
            $order->consumer->notify($notification);
            $this->info("✅ Notification queued successfully!");
            $this->newLine();
            $this->info("📝 Process queue with: php artisan queue:work");
            $this->info("📋 Check logs: tail -f storage/logs/laravel.log");
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->line($e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}

