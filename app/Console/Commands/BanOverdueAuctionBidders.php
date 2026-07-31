<?php

namespace App\Console\Commands;

use App\Models\AuctionBan;
use App\Models\AuctionBidDeposit;
use App\Models\AuctionItem;
use App\Models\AuctionSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BanOverdueAuctionBidders extends Command
{
    protected $signature   = 'auction:ban-overdue';
    protected $description = 'Ban users who have not paid for their won auction items within the payment deadline.';

    public function handle(): int
    {
        $setting  = AuctionSetting::current();
        $payHours = (int) ($setting->hours_to_pay ?? 48);

        // Find all ended auctions with a winner, with a payment_deadline that has passed, and no confirmed payment
        $overdue = AuctionItem::where('status', 'ended')
            ->whereNotNull('winner_id')
            ->where(function ($q) use ($payHours) {
                // Either payment_deadline is set and past, or ends_at + hours_to_pay is past
                $q->where(function ($q2) {
                    $q2->whereNotNull('payment_deadline')
                       ->where('payment_deadline', '<', now());
                })->orWhere(function ($q2) use ($payHours) {
                    $q2->whereNull('payment_deadline')
                       ->where('ends_at', '<', now()->subHours($payHours));
                });
            })
            ->with('winner')
            ->get()
            ->filter(fn($a) => $a->winner !== null)
            // Exclude already paid
            ->filter(function ($a) {
                if (!$a->order_id) return true;
                $order = \App\Models\Order::withoutGlobalScope(\App\Models\Concerns\ExcludeTempLaybyScope::class)->find($a->order_id);
                if (!$order) return true;
                return !in_array(strtolower((string) $order->payment_status), ['paid','complete','completed','success','approved','captured']);
            })
            // Exclude already banned for this item
            ->filter(fn($a) => !AuctionBan::where('user_id', $a->winner_id)
                ->where('auction_item_id', $a->id)
                ->exists()
            )
            // Exclude users whose deposit already fully covers the outstanding amount
            ->filter(function ($a) {
                $winnerBid    = (float) ($a->winner_bid    ?? 0);
                $deliveryCost = (float) ($a->delivery_cost ?? 0);

                // Bid deposit already paid towards this auction
                $depositPaid = (float) \App\Models\AuctionBidDeposit::where('user_id', $a->winner_id)
                    ->where('auction_item_id', $a->id)
                    ->whereNotNull('paid_at')
                    ->value('amount');

                $amountDue = max(0, $winnerBid + $deliveryCost - $depositPaid);

                // Deposit covers the whole bid — nothing owed, no reason to ban
                if ($amountDue <= 0) {
                    Log::info("Auction ban SKIPPED for user #{$a->winner_id} — deposit of \${$depositPaid} fully covers bid \${$winnerBid} for auction #{$a->id}");
                    return false;
                }

                return true;
            });

        $banned = 0;

        foreach ($overdue as $auction) {
            try {
                AuctionBan::create([
                    'user_id'         => $auction->winner_id,
                    'auction_item_id' => $auction->id,
                    'banned_at'       => now(),
                ]);

                // Set payment_deadline if not yet set
                if (!$auction->payment_deadline) {
                    $auction->update([
                        'payment_deadline' => $auction->ends_at->copy()->addHours($payHours),
                    ]);
                }

                $banned++;
                Log::info("Auction ban applied to user #{$auction->winner_id} ({$auction->winner->email}) for auction #{$auction->id}");
            } catch (\Throwable $e) {
                Log::error("Auction ban failed for auction #{$auction->id}: " . $e->getMessage());
            }
        }

        $this->info("Bans applied: {$banned}");
        return Command::SUCCESS;
    }
}
