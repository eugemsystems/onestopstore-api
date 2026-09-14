<?php

namespace App\Console\Commands;

use App\Mail\CartReminderMail;
use App\Models\Cart;
use App\Models\CartReminder;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCartReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cart:send-reminders {--dry-run : Preview without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder emails to users with abandoned carts';

    private $firstReminderHours = 12;
    private $spacingHours = 24;
    private $maxCount = 3;
    private $enabledSince = null;
    private $isDryRun = false;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🛒 Processing Abandoned Cart Reminders...');
        $this->newLine();

        $this->isDryRun = $this->option('dry-run');

        if ($this->isDryRun) {
            $this->warn('⚠️  DRY RUN MODE - No emails will be sent, no records will be created');
            $this->newLine();
        }

        // Load settings
        $this->loadSettings();

        // Display configuration
        $this->info('⚙️  Configuration:');
        $this->info("   First Reminder: {$this->firstReminderHours} hours");
        $this->info("   Spacing:        {$this->spacingHours} hours");
        $this->info("   Max Reminders:  {$this->maxCount}");
        $this->info("   Enabled since:  {$this->enabledSince}");
        $this->newLine();

        // A cart "session" only counts if it STARTED on/after $enabledSince.
        // Without this, any pre-existing carts rows (e.g. years of backlog
        // that predate this feature, or predate the order-placement cart-clear
        // fix) would look "abandoned right now" on the very first run and
        // trigger a mass one-off blast to every user who ever left an item in
        // their cart — including rows tied to orders placed long ago. This
        // guard also naturally suppresses reminder #2/#3 for any such stale
        // session, since its cart_started_at will never satisfy this bound.
        $rows = DB::table('carts')
            ->select('consumer_id', DB::raw('MIN(created_at) as cart_started_at'), DB::raw('COUNT(*) as item_count'))
            ->whereNull('deleted_at')
            ->groupBy('consumer_id')
            ->havingRaw('MIN(created_at) <= ?', [now()->subHours($this->firstReminderHours)])
            ->havingRaw('MIN(created_at) >= ?', [$this->enabledSince])
            ->get();

        if ($rows->isEmpty()) {
            $this->info('ℹ️  No abandoned carts eligible for a reminder.');
            return 0;
        }

        $this->info("Found {$rows->count()} abandoned cart(s) to evaluate.");
        $this->newLine();

        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $cartStartedAt = $row->cart_started_at;

            $sentCount = CartReminder::where('user_id', $row->consumer_id)
                ->where('cart_started_at', $cartStartedAt)
                ->where('status', 'sent')
                ->count();

            if ($sentCount >= $this->maxCount) {
                $skipped++;
                continue;
            }

            $lastReminder = CartReminder::where('user_id', $row->consumer_id)
                ->where('cart_started_at', $cartStartedAt)
                ->where('status', 'sent')
                ->orderByDesc('sent_at')
                ->first();

            if ($lastReminder && $lastReminder->sent_at && $lastReminder->sent_at->gt(now()->subHours($this->spacingHours))) {
                $skipped++;
                continue;
            }

            $reminderNumber = $sentCount + 1;

            $user = User::find($row->consumer_id);

            if (!$user || !$user->email) {
                $this->warn("  ⚠  User #{$row->consumer_id}: user or email missing, skipping.");
                $skipped++;
                continue;
            }

            $action = $this->isDryRun ? '[DRY RUN]' : '';
            $this->line("  {$action} Sending reminder #{$reminderNumber} to {$user->email} — {$row->item_count} item(s)");

            if ($this->isDryRun) {
                continue;
            }

            $reminder = CartReminder::create([
                'user_id' => $row->consumer_id,
                'reminder_number' => $reminderNumber,
                'cart_started_at' => $cartStartedAt,
                'email' => $user->email,
                'status' => 'pending',
                'item_count' => $row->item_count,
            ]);

            try {
                $items = $this->buildItemsForEmail($row->consumer_id);

                Mail::to($user->email)
                    ->send(new CartReminderMail($user, $reminderNumber, (int) $row->item_count, $items));

                $reminder->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

                $sent++;
                $this->info("  ✓ Reminder #{$reminderNumber} sent to {$user->email}");
            } catch (\Throwable $e) {
                $reminder->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                Log::error('Failed to send cart reminder', [
                    'user_id' => $row->consumer_id,
                    'reminder_number' => $reminderNumber,
                    'error' => $e->getMessage(),
                ]);

                $failed++;
                $this->error("  ✗ Failed for {$user->email}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info('✅ Processing Complete!');
        $this->info("📧 Sent: {$sent}");
        $this->info("❌ Failed: {$failed}");
        $this->info("⏭️  Skipped: {$skipped}");

        return 0;
    }

    /**
     * Build a plain-array snapshot of a user's current cart items (image,
     * title, price) for the reminder email — kept as plain data rather than
     * passing Eloquent models into the queued Mailable.
     */
    private function buildItemsForEmail(int $consumerId): array
    {
        return Cart::where('consumer_id', $consumerId)
            ->whereNull('deleted_at')
            ->with(['product.product_thumbnail', 'variation'])
            ->get()
            ->map(function (Cart $cart) {
                $product = $cart->product;
                $variation = $cart->variation;

                $price = $variation->price ?? $product->price ?? 0;
                if ($variation && $variation->sale_price) {
                    $price = $variation->sale_price;
                } elseif ($product && $product->sale_price) {
                    $price = $product->sale_price;
                }

                return [
                    'name' => $product->name ?? 'Product',
                    'image_url' => $product?->product_thumbnail?->image_url,
                    'price' => (float) $price,
                    'quantity' => (int) $cart->quantity,
                ];
            })
            ->all();
    }

    /**
     * Load settings from database
     */
    private function loadSettings()
    {
        $setting = Setting::first();
        $values = $setting ? $setting->values : [];

        $this->firstReminderHours = $values['cart_reminder_first_hours'] ?? 12;
        $this->spacingHours = $values['cart_reminder_spacing_hours'] ?? 24;
        $this->maxCount = $values['cart_reminder_max_count'] ?? 3;

        // Defensive default: if this setting is ever missing, fail safe to
        // "nothing is eligible yet" (now()) rather than silently reverting to
        // no cutoff at all, which is exactly the bug this guards against.
        $this->enabledSince = $values['cart_reminder_enabled_since'] ?? now()->toDateTimeString();
    }
}
