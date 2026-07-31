<?php

namespace App\Console\Commands;

use App\Models\AuctionItem;
use App\Models\AuctionBid;
use App\Models\AuctionBidDeposit;
use App\Models\User;
use App\Mail\AuctionWonMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CloseExpiredAuctions extends Command
{
    protected $signature   = 'auctions:close-expired';
    protected $description = 'End any active auctions whose end time has passed, set winner, and send winner email';

    public function handle(): int
    {
        $expired = AuctionItem::where('status', 'active')
            ->where('ends_at', '<=', now())
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No expired auctions found.');
            return Command::SUCCESS;
        }

        foreach ($expired as $auction) {
            $winningBid = AuctionBid::where('auction_item_id', $auction->id)
                ->orderByDesc('amount')
                ->first();

            $auction->update([
                'status'     => 'ended',
                'winner_id'  => $winningBid?->user_id,
                'winner_bid' => $winningBid?->amount,
            ]);

            $this->line("  ✓ Ended: [{$auction->id}] {$auction->title}");

            // Send winner email
            if ($winningBid) {
                try {
                    $winner = User::find($winningBid->user_id);
                    if ($winner) {
                        Mail::to($winner->email)->queue(new AuctionWonMail($auction->fresh(), $winner));
                        $this->line("    📧 Winner email queued → {$winner->email}");
                    }
                } catch (\Throwable $e) {
                    Log::error("AuctionWonMail failed for auction #{$auction->id}: " . $e->getMessage());
                    $this->error("    ⚠ Failed to queue email: " . $e->getMessage());
                }
            } else {
                $this->line("    ℹ No bids — no winner.");
            }

            // ── Refund deposits to losing bidders ────────────────────────────
            $this->refundLosingDeposits($auction, $winningBid?->user_id);
        }

        $this->newLine();
        $this->info("✅ Processed {$expired->count()} auction(s).");

        return Command::SUCCESS;
    }

    /**
     * Refund the bid deposit to every bidder who did NOT win.
     * The winner keeps their deposit held (it is deducted from pay()).
     */
    private function refundLosingDeposits(AuctionItem $auction, ?int $winnerId): void
    {
        $deposits = AuctionBidDeposit::where('auction_item_id', $auction->id)
            ->whereNotNull('paid_at')
            ->whereNull('refunded_at')  // not yet refunded
            ->when($winnerId, fn ($q) => $q->where('user_id', '!=', $winnerId))
            ->with('user.wallet')
            ->get();

        foreach ($deposits as $deposit) {
            try {
                DB::transaction(function () use ($deposit, $auction) {
                    $wallet = $deposit->user?->wallet;
                    if (!$wallet) {
                        // Create wallet if it doesn't exist
                        $wallet = $deposit->user->wallet()->create(['balance' => 0]);
                    }

                    $wallet->increment('balance', (float) $deposit->amount);

                    // Log the transaction
                    $wallet->transactions()->create([
                        'amount'  => (float) $deposit->amount,
                        'type'    => 'credit',
                        'detail'  => 'Bid deposit refund — ' . $auction->title . ' (Lot #' . $auction->id . ')',
                        'status'  => 'approved',
                    ]);

                    $deposit->update(['refunded_at' => now()]);
                });

                $this->line("    💵 Deposit refunded \${$deposit->amount} → {$deposit->user?->email}");
            } catch (\Throwable $e) {
                Log::error("Deposit refund failed for deposit #{$deposit->id}: " . $e->getMessage());
                $this->error("    ⚠ Refund failed for deposit #{$deposit->id}: " . $e->getMessage());
            }
        }
    }
}
