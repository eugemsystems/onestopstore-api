<?php

namespace App\Console\Commands;

use App\Models\AuctionItem;
use App\Models\AuctionSetting;
use App\Notifications\AuctionPaymentReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAuctionPaymentReminders extends Command
{
    protected $signature   = 'auction:send-reminders';
    protected $description = 'Send payment reminder emails to winners who have not yet paid for their won auction items.';

    public function handle(): int
    {
        $setting = AuctionSetting::current();
        $r1Hours = (int) ($setting->reminder_1_hours ?? 12);
        $r2Hours = (int) ($setting->reminder_2_hours ?? 24);
        $payHours = (int) ($setting->hours_to_pay ?? 48);

        // Find all ended auctions with a winner but no confirmed payment
        $unpaid = AuctionItem::where('status', 'ended')
            ->whereNotNull('winner_id')
            ->whereNull('fulfillment_status')
            ->orWhere(function ($q) {
                $q->where('status', 'ended')
                  ->whereNotNull('winner_id')
                  ->whereNotIn('fulfillment_status', ['confirmed', 'ready_for_collection', 'out_for_delivery', 'collected', 'delivered']);
            })
            ->with('winner')
            ->get()
            ->filter(fn($a) => $a->winner !== null)
            // Exclude items that were already paid (have a confirmed order)
            ->filter(function ($a) {
                if (!$a->order_id) return true;
                $order = \App\Models\Order::withoutGlobalScope(\App\Models\Concerns\ExcludeTempLaybyScope::class)
                    ->find($a->order_id);
                if (!$order) return true;
                return !in_array(strtolower((string) $order->payment_status), ['paid','complete','completed','success','approved','captured']);
            });

        $sent1 = 0;
        $sent2 = 0;

        foreach ($unpaid as $auction) {
            $wonAt    = $auction->ends_at;
            $deadline = $auction->payment_deadline;
            if (!$wonAt) continue;

            // Set payment_deadline if not yet set
            if (!$deadline) {
                $deadline = $wonAt->copy()->addHours($payHours);
                $auction->update(['payment_deadline' => $deadline]);
                $auction->payment_deadline = $deadline;
            }

            $hoursElapsed = $wonAt->diffInHours(now(), false);

            // Reminder 1
            if ($hoursElapsed >= $r1Hours && !$auction->reminder_1_sent_at) {
                try {
                    $auction->winner->notify(new AuctionPaymentReminderNotification($auction, 1));
                    $auction->update(['reminder_1_sent_at' => now()]);
                    $sent1++;
                    Log::info("Auction reminder 1 sent for auction #{$auction->id} to {$auction->winner->email}");
                } catch (\Throwable $e) {
                    Log::error("Auction reminder 1 failed for #{$auction->id}: " . $e->getMessage());
                }
            }

            // Reminder 2
            if ($hoursElapsed >= $r2Hours && !$auction->reminder_2_sent_at) {
                try {
                    $auction->winner->notify(new AuctionPaymentReminderNotification($auction->fresh(), 2));
                    $auction->update(['reminder_2_sent_at' => now()]);
                    $sent2++;
                    Log::info("Auction reminder 2 sent for auction #{$auction->id} to {$auction->winner->email}");
                } catch (\Throwable $e) {
                    Log::error("Auction reminder 2 failed for #{$auction->id}: " . $e->getMessage());
                }
            }
        }

        $this->info("Reminders sent — Reminder 1: {$sent1}, Reminder 2: {$sent2}");
        return Command::SUCCESS;
    }
}
