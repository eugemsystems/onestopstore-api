<?php

namespace App\Console\Commands;

use App\Mail\PendingOrderReminder;
use App\Models\Order;
use App\Models\OrderReminderEmail;
use App\Models\OrderStatus;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessPendingOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:process-pending
                            {--dry-run : Show what would happen without sending emails or cancelling orders}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending orders: send reminder emails and auto-cancel old orders';

    private $firstReminderHours = 12;
    private $secondReminderHours = 24;
    private $autoCancelHours = 72;
    private $isDryRun = false;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Processing Pending Orders...');
        $this->newLine();

        $this->isDryRun = $this->option('dry-run');

        if ($this->isDryRun) {
            $this->warn('⚠️  DRY RUN MODE - No emails will be sent, no orders will be cancelled');
            $this->newLine();
        }

        // Load settings
        $this->loadSettings();

        // Display configuration
        $this->info("⚙️  Configuration:");
        $this->info("   First Reminder:  {$this->firstReminderHours} hours");
        $this->info("   Second Reminder: {$this->secondReminderHours} hours");
        $this->info("   Auto-Cancel:     {$this->autoCancelHours} hours");
        $this->newLine();

        // Get pending status
        $pendingStatus = OrderStatus::where('slug', 'pending')->first();
        if (!$pendingStatus) {
            $this->error('❌ Pending status not found!');
            return 1;
        }

        // Process in order: first reminders, second reminders, then cancellations
        $firstCount = $this->processFirstReminders($pendingStatus->id);
        $secondCount = $this->processSecondReminders($pendingStatus->id);
        $cancelledCount = $this->processCancellations($pendingStatus->id);

        // Summary
        $this->newLine();
        $this->info('✅ Processing Complete!');
        $this->info("📧 First reminders sent: {$firstCount}");
        $this->info("📧 Second reminders sent: {$secondCount}");
        $this->info("❌ Orders cancelled: {$cancelledCount}");

        return 0;
    }

    /**
     * Load settings from database
     */
    private function loadSettings()
    {
        $setting = Setting::first();
        $values = $setting ? $setting->values : [];

        $this->firstReminderHours = $values['pending_order_first_reminder_hours'] ?? 12;
        $this->secondReminderHours = $values['pending_order_second_reminder_hours'] ?? 24;
        $this->autoCancelHours = $values['pending_order_auto_cancel_hours'] ?? 72;
    }

    /**
     * Process first reminders
     */
    private function processFirstReminders($pendingStatusId)
    {
        $cutoffTime = now()->subHours($this->firstReminderHours);
        $secondReminderCutoff = now()->subHours($this->secondReminderHours);

        // Find orders needing first reminder
        $orders = Order::where('order_status_id', $pendingStatusId)
            ->where('created_at', '<=', $cutoffTime)
            ->where('created_at', '>', $secondReminderCutoff)
            ->whereDoesntHave('reminderEmails', function($q) {
                $q->where('reminder_type', 'first');
            })
            ->with('consumer')
            ->get();

        if ($orders->isEmpty()) {
            $this->info('ℹ️  No orders needing first reminder');
            return 0;
        }

        $this->info("📧 Sending first reminders to {$orders->count()} order(s)...");

        $successCount = 0;
        foreach ($orders as $order) {
            if ($this->sendReminder($order, 'first')) {
                $successCount++;
            }
        }

        return $successCount;
    }

    /**
     * Process second reminders
     */
    private function processSecondReminders($pendingStatusId)
    {
        $cutoffTime = now()->subHours($this->secondReminderHours);
        $cancelCutoff = now()->subHours($this->autoCancelHours);

        // Find orders needing second reminder
        $orders = Order::where('order_status_id', $pendingStatusId)
            ->where('created_at', '<=', $cutoffTime)
            ->where('created_at', '>', $cancelCutoff)
            ->whereDoesntHave('reminderEmails', function($q) {
                $q->where('reminder_type', 'second');
            })
            ->with('consumer')
            ->get();

        if ($orders->isEmpty()) {
            $this->info('ℹ️  No orders needing second reminder');
            return 0;
        }

        $this->info("📧 Sending second reminders to {$orders->count()} order(s)...");

        $successCount = 0;
        foreach ($orders as $order) {
            if ($this->sendReminder($order, 'second')) {
                $successCount++;
            }
        }

        return $successCount;
    }

    /**
     * Process cancellations
     */
    private function processCancellations($pendingStatusId)
    {
        $cutoffTime = now()->subHours($this->autoCancelHours);

        // Find orders to cancel
        $orders = Order::where('order_status_id', $pendingStatusId)
            ->where('created_at', '<=', $cutoffTime)
            ->with('consumer')
            ->get();

        if ($orders->isEmpty()) {
            $this->info('ℹ️  No orders to cancel');
            return 0;
        }

        $this->warn("❌ Cancelling {$orders->count()} order(s)...");

        // Get cancelled status
        $cancelledStatus = OrderStatus::where('slug', 'cancelled')->first();
        if (!$cancelledStatus) {
            $this->error('❌ Cancelled status not found!');
            return 0;
        }

        $successCount = 0;
        foreach ($orders as $order) {
            if ($this->cancelOrder($order, $cancelledStatus->id)) {
                $successCount++;
            }
        }

        return $successCount;
    }

    /**
     * Send reminder email
     */
    private function sendReminder($order, $type)
    {
        if (!$order->consumer || !$order->consumer->email) {
            $this->warn("⚠️  Order #{$order->order_number}: No customer email");
            return false;
        }

        try {
            // In dry-run mode, just show what would happen without creating records
            if ($this->isDryRun) {
                $this->info("  ✓ Order #{$order->order_number} → {$order->consumer->email} ({$type})");
                return true;
            }

            // Create reminder record (only in live mode)
            $reminder = OrderReminderEmail::create([
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $order->consumer_id,
                'email' => $order->consumer->email,
                'reminder_type' => $type,
                'status' => 'pending',
            ]);

            // Send email
            Mail::to($order->consumer->email)
                ->send(new PendingOrderReminder($order, $type));

            // Update status
            $reminder->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);


            $this->info("  ✓ Order #{$order->order_number} → {$order->consumer->email} ({$type})");
            return true;

        } catch (\Exception $e) {
            if (isset($reminder)) {
                $reminder->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }

            Log::error("Failed to send reminder", [
                'order_id' => $order->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            $this->error("  ✗ Order #{$order->order_number}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Cancel order and send notification
     */
    private function cancelOrder($order, $cancelledStatusId)
    {
        try {
            if (!$this->isDryRun) {
                // Add marker to note field to indicate auto-cancellation
                $currentNote = $order->note ?? '';
                $autoMarker = '[AUTO_CANCELLED]';
                $newNote = trim($autoMarker . ' ' . $currentNote);

                // Update order status AND note together
                $order->update([
                    'order_status_id' => $cancelledStatusId,
                    'note' => $newNote
                ]);
            }

            // Send cancellation email
            $emailSent = $this->sendReminder($order, 'cancellation');


            return true;

        } catch (\Exception $e) {
            Log::error("Failed to cancel order", [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            $this->error("  ✗ Failed to cancel Order #{$order->order_number}: {$e->getMessage()}");
            return false;
        }
    }
}
