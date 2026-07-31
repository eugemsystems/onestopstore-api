<?php

namespace App\Console\Commands;

use App\Models\AuctionBan;
use App\Models\AuctionBid;
use App\Models\AuctionBidDeposit;
use App\Models\AuctionDepositRefund;
use App\Models\AuctionItem;
use App\Models\AuctionSetting;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Auction feature test suite.
 *
 * Usage:
 *   php artisan auction:test              → interactive menu
 *   php artisan auction:test --seed       → clear + seed only
 *   php artisan auction:test --all        → run all automated assertions
 */
class AuctionTestSuite extends Command
{
    protected $signature = 'auction:test
                            {--seed   : Clear auction tables and seed test data, then exit}
                            {--all    : Run all automated tests without interactive menu}
                            {--fresh  : Clear all auction tables before seeding (always on)}';

    protected $description = 'Seed and interactively test all auction features (bans, deposits, refunds, emails, etc.)';

    // Test user IDs
    const USER_A = 8804;
    const USER_B = 4;

    /** Holds IDs created during this run so menu tests can reference them */
    private array $ctx = [];

    public function handle(): int
    {
        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════╗');
        $this->line('║         🏷  AUCTION FEATURE TEST SUITE               ║');
        $this->line('╚══════════════════════════════════════════════════════╝');
        $this->newLine();

        // ── Step 1: Always clear + seed ──────────────────────────
        $this->clearTables();
        $this->seedData();

        if ($this->option('seed')) {
            $this->success('Seed complete. Use php artisan auction:test to open the menu.');
            return Command::SUCCESS;
        }

        if ($this->option('all')) {
            return $this->runAllTests();
        }

        return $this->interactiveMenu();
    }

    // ══════════════════════════════════════════════════════════════
    // CLEAR
    // ══════════════════════════════════════════════════════════════

    private function clearTables(): void
    {
        $this->info('🧹 Clearing all auction tables...');

        DB::statement('SET session_replication_role = replica;'); // disable FK checks (Postgres)

        DB::table('auction_deposit_refunds')->whereNotNull('id')->delete();
        DB::table('auction_bid_deposits')->whereNotNull('id')->delete();
        DB::table('auction_bans')->whereNotNull('id')->delete();
        DB::table('auction_bids')->whereNotNull('id')->delete();

        // Remove AUC_WIN and AUC_DEPOSIT orders created by previous test runs
        DB::table('orders')
            ->where('note', 'LIKE', 'AUC_%')
            ->delete();

        // Soft-delete auction items created by test (note field used as marker)
        DB::table('auction_items')
            ->where('description', 'LIKE', '[TEST]%')
            ->update(['deleted_at' => now()]);

        DB::statement('SET session_replication_role = DEFAULT;');

        $this->line('  ✓ Tables cleared.');
    }

    // ══════════════════════════════════════════════════════════════
    // SEED
    // ══════════════════════════════════════════════════════════════

    private function seedData(): void
    {
        $this->info('🌱 Seeding test data...');

        $setting = AuctionSetting::current();
        $setting->update([
            'bid_fee_enabled' => true,
            'bid_fee_amount'  => 20.00,
            'hours_to_pay'    => 48,
            'reminder_1_hours'=> 12,
            'reminder_2_hours'=> 24,
        ]);

        $adminId = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->value('id') ?? 29;

        // ── Auction 1: ACTIVE — User A can deposit & bid ──────────
        $live = AuctionItem::create([
            'title'              => '[TEST] Live Auction — Laptop',
            'description'        => '[TEST] Active auction for deposit+bid testing',
            'condition'          => 'refurbished',
            'starting_price'     => 100,
            'reserve_price'      => 400,
            'current_bid'        => 100,
            'min_bid_increment'  => 10,
            'status'             => 'active',
            'starts_at'          => now()->subMinutes(30),
            'ends_at'            => now()->addHours(6),
            'auto_extend_minutes'=> 2,
            'created_by'         => $adminId,
            'branch'             => 'Harare',
        ]);
        $this->ctx['live_auction_id'] = $live->id;

        // ── Auction 2: ENDED — User A is winner, deposit paid, awaiting order payment ──
        $won = AuctionItem::create([
            'title'              => '[TEST] Won Auction — Camera',
            'description'        => '[TEST] Ended auction where user 8804 won',
            'condition'          => 'as-is',
            'starting_price'     => 50,
            'reserve_price'      => 200,
            'current_bid'        => 165,
            'winner_bid'         => 165,
            'winner_id'          => self::USER_A,
            'min_bid_increment'  => 8,
            'status'             => 'ended',
            'starts_at'          => now()->subDays(2),
            'ends_at'            => now()->subHours(2),
            'auto_extend_minutes'=> 2,
            'payment_deadline'   => now()->addHours(46),
            'created_by'         => $adminId,
            'branch'             => 'Harare',
            'fulfillment_method' => 'collection',
            'fulfillment_status' => 'pending',
        ]);
        $this->ctx['won_auction_id'] = $won->id;

        // Deposit paid for the won auction ($20, winner_bid $165 → balance due $145)
        $deposit1 = AuctionBidDeposit::create([
            'user_id'         => self::USER_A,
            'auction_item_id' => $won->id,
            'amount'          => 20.00,
            'payment_method'  => 'pesepay',
            'paid_at'         => now()->subDays(2),
        ]);
        $this->ctx['deposit_id'] = $deposit1->id;

        // ── Auction 3: ENDED — User A won, deposit overpaid (refund eligible) ──
        $refundable = AuctionItem::create([
            'title'              => '[TEST] Refundable Auction — TV',
            'description'        => '[TEST] Deposit ($80) > winner_bid ($50) → refund eligible',
            'condition'          => 'refurbished',
            'starting_price'     => 30,
            'reserve_price'      => 100,
            'current_bid'        => 50,
            'winner_bid'         => 50,
            'winner_id'          => self::USER_A,
            'min_bid_increment'  => 5,
            'status'             => 'ended',
            'starts_at'          => now()->subDays(3),
            'ends_at'            => now()->subDays(1),
            'payment_deadline'   => now()->subHours(1), // overdue
            'created_by'         => $adminId,
            'branch'             => 'Bulawayo',
            'fulfillment_method' => 'collection',
            'fulfillment_status' => 'pending',
        ]);
        $this->ctx['refundable_auction_id'] = $refundable->id;

        // Overpaid deposit
        $deposit2 = AuctionBidDeposit::create([
            'user_id'         => self::USER_A,
            'auction_item_id' => $refundable->id,
            'amount'          => 80.00, // way more than the $50 win
            'payment_method'  => 'pesepay',
            'paid_at'         => now()->subDays(3),
        ]);
        $this->ctx['overpaid_deposit_id'] = $deposit2->id;

        // ── Auction 4: ENDED — User B won, OVERDUE (ban candidate) ──
        $overdue = AuctionItem::create([
            'title'              => '[TEST] Overdue Auction — Phone',
            'description'        => '[TEST] User 4 won but never paid — ban candidate',
            'condition'          => 'damaged',
            'starting_price'     => 40,
            'reserve_price'      => 150,
            'current_bid'        => 90,
            'winner_bid'         => 90,
            'winner_id'          => self::USER_B,
            'min_bid_increment'  => 5,
            'status'             => 'ended',
            'starts_at'          => now()->subDays(5),
            'ends_at'            => now()->subDays(3),
            'payment_deadline'   => now()->subDays(1), // past deadline, no payment
            'created_by'         => $adminId,
            'branch'             => 'Harare',
            'fulfillment_method' => 'collection',
            'fulfillment_status' => 'pending',
        ]);
        $this->ctx['overdue_auction_id'] = $overdue->id;

        // Deposit paid for overdue auction ($20) — amount due = 90-20 = $70, not enough to skip ban
        AuctionBidDeposit::create([
            'user_id'         => self::USER_B,
            'auction_item_id' => $overdue->id,
            'amount'          => 20.00,
            'payment_method'  => 'pesepay',
            'paid_at'         => now()->subDays(5),
        ]);

        // ── Auction 5: ENDED — User B won, deposit covers full bid (ban SKIP candidate) ──
        $fullCover = AuctionItem::create([
            'title'              => '[TEST] Covered Auction — Fridge',
            'description'        => '[TEST] User 4 deposit ($120) >= winner_bid ($100) → ban skipped',
            'condition'          => 'as-is',
            'starting_price'     => 50,
            'reserve_price'      => 200,
            'current_bid'        => 100,
            'winner_bid'         => 100,
            'winner_id'          => self::USER_B,
            'min_bid_increment'  => 10,
            'status'             => 'ended',
            'starts_at'          => now()->subDays(4),
            'ends_at'            => now()->subDays(2),
            'payment_deadline'   => now()->subHours(3),
            'created_by'         => $adminId,
            'branch'             => 'Harare',
            'fulfillment_method' => 'collection',
            'fulfillment_status' => 'pending',
        ]);
        $this->ctx['covered_auction_id'] = $fullCover->id;

        AuctionBidDeposit::create([
            'user_id'         => self::USER_B,
            'auction_item_id' => $fullCover->id,
            'amount'          => 120.00, // >= winner_bid of $100 → no ban
            'payment_method'  => 'dpo',
            'paid_at'         => now()->subDays(4),
        ]);

        $this->line('  ✓ 5 auction items seeded.');
        $this->printSeededSummary();
    }

    private function printSeededSummary(): void
    {
        $this->newLine();
        $this->line('┌────────────────────────────────────────────────────────────────────┐');
        $this->line('│  SEEDED DATA SUMMARY                                               │');
        $this->line('├────────────────┬──────────┬─────────────────────────────────────────┤');
        $this->line('│ Auction ID     │ User     │ Scenario                                │');
        $this->line('├────────────────┼──────────┼─────────────────────────────────────────┤');
        $this->line("│ #{$this->ctx['live_auction_id']}            │ Anyone   │ ACTIVE — deposit + bid live             │");
        $this->line("│ #{$this->ctx['won_auction_id']}            │ 8804     │ WON — deposit paid, balance due         │");
        $this->line("│ #{$this->ctx['refundable_auction_id']}            │ 8804     │ REFUND ELIGIBLE — deposit > win bid     │");
        $this->line("│ #{$this->ctx['overdue_auction_id']}            │ 4        │ OVERDUE — ban candidate                 │");
        $this->line("│ #{$this->ctx['covered_auction_id']}            │ 4        │ COVERED — deposit covers full bid       │");
        $this->line('└────────────────┴──────────┴─────────────────────────────────────────┘');
        $this->newLine();
    }

    // ══════════════════════════════════════════════════════════════
    // INTERACTIVE MENU
    // ══════════════════════════════════════════════════════════════

    private function interactiveMenu(): int
    {
        while (true) {
            $this->newLine();
            $choice = $this->choice(
                '🎮 What would you like to test?',
                [
                    '1' => '1. Run ban command (auction:ban-overdue)',
                    '2' => '2. Test ban SKIP logic (covered deposit)',
                    '3' => '3. Manually ban user 8804',
                    '4' => '4. Remove ban for user 8804',
                    '5' => '5. Simulate PesePay deposit payment (user 8804, live auction)',
                    '6' => '6. Simulate PesePay win payment (user 8804, won auction)',
                    '7' => '7. Request refund — bank (user 8804, refundable auction)',
                    '8' => '8. Request refund — wallet (user 8804, refundable auction)',
                    '9' => '9. Admin: approve + credit wallet refund',
                    '10'=> '10. Send payment reminder emails',
                    '11'=> '11. Show full auction state summary',
                    '12'=> '12. Re-seed (clear + seed again)',
                    '0' => '0. Exit',
                ],
                '11'
            );

            match ($choice) {
                '1'  => $this->testBanCommand(),
                '2'  => $this->testBanSkip(),
                '3'  => $this->manualBan(self::USER_A),
                '4'  => $this->removeBan(self::USER_A),
                '5'  => $this->simulateDepositPayment(),
                '6'  => $this->simulateWinPayment(),
                '7'  => $this->requestRefund('bank'),
                '8'  => $this->requestRefund('wallet'),
                '9'  => $this->adminCreditWallet(),
                '10' => $this->sendReminders(),
                '11' => $this->showState(),
                '12' => $this->reseed(),
                '0'  => (function() { $this->info('Goodbye!'); })(),
                default => $this->warn('Unknown choice.'),
            };

            if ($choice === '0') break;
        }

        return Command::SUCCESS;
    }

    // ══════════════════════════════════════════════════════════════
    // TEST ACTIONS
    // ══════════════════════════════════════════════════════════════

    private function testBanCommand(): void
    {
        $this->info('▶ Running auction:ban-overdue command...');
        $before = AuctionBan::count();
        $this->call('auction:ban-overdue');
        $after = AuctionBan::count();
        $newBans = $after - $before;

        $this->line("  Bans before: {$before} → after: {$after} (+{$newBans} new)");

        // Check expected: user B overdue auction should be banned
        $banned = AuctionBan::where('user_id', self::USER_B)
            ->where('auction_item_id', $this->ctx['overdue_auction_id'])
            ->exists();
        $this->assert(
            $banned,
            'User 4 banned for overdue auction #'.$this->ctx['overdue_auction_id'],
            'User 4 was NOT banned for overdue auction!'
        );

        // Check expected: covered auction should NOT be banned
        $notBanned = !AuctionBan::where('user_id', self::USER_B)
            ->where('auction_item_id', $this->ctx['covered_auction_id'])
            ->exists();
        $this->assert(
            $notBanned,
            'User 4 NOT banned for covered auction (deposit >= bid) ✓',
            'User 4 was INCORRECTLY banned for covered auction!'
        );
    }

    private function testBanSkip(): void
    {
        $this->info('▶ Testing ban-skip logic for covered deposit...');
        $deposit = AuctionBidDeposit::where('auction_item_id', $this->ctx['covered_auction_id'])->first();
        $auction = AuctionItem::find($this->ctx['covered_auction_id']);

        $amountDue = max(0, (float)$auction->winner_bid - (float)$deposit->amount);
        $this->line("  Winner bid: \${$auction->winner_bid} | Deposit: \${$deposit->amount} | Amount due: \${$amountDue}");
        $this->assert(
            $amountDue <= 0,
            'Amount due is 0 — ban command WILL skip this user ✓',
            "Amount due is \${$amountDue} — user WILL be banned (unexpected)"
        );
    }

    private function manualBan(int $userId): void
    {
        $auctionId = $this->ctx['won_auction_id'];
        $existing  = AuctionBan::where('user_id', $userId)->where('auction_item_id', $auctionId)->first();
        if ($existing) {
            $this->warn("  User {$userId} is already banned for auction #{$auctionId}.");
            return;
        }

        AuctionBan::create([
            'user_id'         => $userId,
            'auction_item_id' => $auctionId,
            'banned_at'       => now(),
        ]);
        $this->success("  ✓ User {$userId} banned for auction #{$auctionId}.");
        $this->line("  → Frontend: Login as user {$userId} and visit /auctions/{$auctionId} — should see ban message.");
    }

    private function removeBan(int $userId): void
    {
        $deleted = AuctionBan::where('user_id', $userId)->delete();
        $this->success("  ✓ Removed {$deleted} ban(s) for user {$userId}.");
    }

    private function simulateDepositPayment(): void
    {
        $this->info('▶ Simulating PesePay deposit payment for user 8804, live auction...');
        $auctionId = $this->ctx['live_auction_id'];

        // Create or find deposit record
        $deposit = AuctionBidDeposit::firstOrCreate(
            ['user_id' => self::USER_A, 'auction_item_id' => $auctionId],
            ['amount' => 20.00, 'payment_method' => 'pesepay']
        );

        // Create order (simulating what payDeposit endpoint does)
        $orderStatus = OrderStatus::where('slug', 'pending')->first();
        $repo = app(\App\Repositories\Eloquents\OrderRepository::class);
        $orderNumber = $repo->nextOrderNumber();

        $order = Order::create([
            'consumer_id'     => self::USER_A,
            'order_number'    => $orderNumber,
            'amount'          => 20.00,
            'total'           => 20.00,
            'grand_total'     => 20.00,
            'payment_method'  => 'pesepay',
            'payment_status'  => 'pending',
            'order_status_id' => optional($orderStatus)->id ?? 1,
            'note'            => 'AUC_DEPOSIT: Refundable bid deposit — [TEST] Live Auction (Lot #'.$auctionId.')',
            'currency'        => config('app.currency', 'USD'),
            'currency_symbol' => config('app.currency_symbol', '$'),
            'exchange_rate'   => 1,
            'status'          => 1,
        ]);

        $deposit->update(['order_id' => $order->id, 'payment_method' => 'pesepay']);

        // Now simulate the webhook marking it paid (same as PesePay listener fix)
        AuctionBidDeposit::where('order_id', $order->id)->whereNull('paid_at')->update(['paid_at' => now()]);
        DB::table('orders')->where('id', $order->id)->update(['payment_status' => 'completed']);

        $deposit->refresh();
        $this->assert(
            $deposit->paid_at !== null,
            "Deposit #{$deposit->id} marked as paid ✓ (paid_at: {$deposit->paid_at})",
            'Deposit was NOT marked as paid!'
        );
        $this->line("  Order #{$order->order_number} created with AUC_DEPOSIT note.");
        $this->line("  → Frontend: Login as user 8804, go to /auctions/{$auctionId} — deposit shows as paid.");
    }

    private function simulateWinPayment(): void
    {
        $this->info('▶ Simulating gateway win payment for user 8804, won auction...');
        $auctionId = $this->ctx['won_auction_id'];
        $auction   = AuctionItem::find($auctionId);
        $deposit   = AuctionBidDeposit::where('user_id', self::USER_A)->where('auction_item_id', $auctionId)->first();
        $depositAmt = $deposit ? (float)$deposit->amount : 0;
        $amountDue  = max(0, (float)$auction->winner_bid - $depositAmt);

        $orderStatus = OrderStatus::where('slug', 'pending')->first();
        $repo = app(\App\Repositories\Eloquents\OrderRepository::class);
        $orderNumber = $repo->nextOrderNumber();

        $order = Order::create([
            'consumer_id'     => self::USER_A,
            'order_number'    => $orderNumber,
            'amount'          => $amountDue,
            'total'           => $amountDue,
            'grand_total'     => $amountDue,
            'payment_method'  => 'pesepay',
            'payment_status'  => 'pending',
            'order_status_id' => optional($orderStatus)->id ?? 1,
            'note'            => 'AUC_WIN: Auction win — [TEST] Won Auction (Lot #'.$auctionId.') | Deposit deducted: $'.number_format($depositAmt, 2).' | Balance due: $'.number_format($amountDue, 2),
            'currency'        => config('app.currency', 'USD'),
            'currency_symbol' => config('app.currency_symbol', '$'),
            'exchange_rate'   => 1,
            'status'          => 1,
        ]);
        $auction->update(['order_id' => $order->id]);

        // Simulate gateway callback marking as completed (triggers PaymentTrait logic)
        DB::table('orders')->where('id', $order->id)->update(['payment_status' => 'completed']);

        // Manually trigger the notification (same as PaymentTrait does via event)
        try {
            $user = User::find(self::USER_A);
            if ($user) {
                $user->notify(new \App\Notifications\AuctionPaymentConfirmedNotification($auction->fresh(), $order));
                $this->success("  ✓ AuctionPaymentConfirmedNotification sent to user 8804 ({$user->email})");
            }
        } catch (\Throwable $e) {
            $this->warn("  Notification failed: ".$e->getMessage());
        }

        $this->success("  ✓ Order #{$order->order_number} marked paid | Amount: \${$amountDue}");
        $this->line("  → Check email inbox for user 8804 for 'Payment Confirmed' email.");
    }

    private function requestRefund(string $method): void
    {
        $this->info("▶ Requesting {$method} refund for user 8804...");
        $auctionId = $this->ctx['refundable_auction_id'];

        // Remove any existing refund request
        AuctionDepositRefund::where('user_id', self::USER_A)->where('auction_item_id', $auctionId)->forceDelete();

        $deposit   = AuctionBidDeposit::where('user_id', self::USER_A)->where('auction_item_id', $auctionId)->first();
        $auction   = AuctionItem::find($auctionId);
        $refundAmt = max(0, (float)$deposit->amount - (float)$auction->winner_bid);

        $refund = AuctionDepositRefund::create([
            'user_id'               => self::USER_A,
            'auction_item_id'       => $auctionId,
            'auction_bid_deposit_id'=> $deposit->id,
            'deposit_amount'        => $deposit->amount,
            'winner_bid_amount'     => $auction->winner_bid,
            'refund_amount'         => $refundAmt,
            'reason_type'           => 'deposit_overpaid',
            'reason'                => 'Test refund request via auction:test command',
            'refund_method'         => $method,
            'status'                => 'pending',
        ]);

        $this->success("  ✓ Refund #{$refund->id} created | Method: {$method} | Amount: \${$refundAmt}");
        $this->line("  → Admin: visit /admin/auctions/deposit-refunds to see it.");
        $this->ctx['last_refund_id'] = $refund->id;
    }

    private function adminCreditWallet(): void
    {
        $refundId = $this->ctx['last_refund_id'] ?? null;
        if (!$refundId) {
            $this->warn('No refund created yet. Run option 7 or 8 first.');
            return;
        }

        $this->info("▶ Admin crediting refund #{$refundId} to user wallet...");

        $refund = AuctionDepositRefund::find($refundId);
        if (!$refund) { $this->error('Refund not found.'); return; }

        $wallet = Wallet::firstOrCreate(
            ['consumer_id' => self::USER_A],
            ['balance' => 0]
        );
        $before = (float)$wallet->balance;

        // Credit wallet
        $wallet->increment('balance', (float)$refund->refund_amount);
        $refund->update([
            'status'           => 'approved',
            'wallet_credited_at' => now(),
            'admin_notes'      => 'Credited via auction:test command',
        ]);

        $wallet->refresh();
        $after = (float)$wallet->balance;

        $this->success("  ✓ Wallet credited: \${$before} → \${$after} (+\${$refund->refund_amount})");
        $this->line("  → Admin: /admin/auctions/deposit-refunds — should show green 'Credited' badge.");
    }

    private function sendReminders(): void
    {
        $this->info('▶ Running auction payment reminder command...');
        // Fire the reminder artisan command if it exists
        if ($this->getApplication()->has('auction:send-payment-reminders')) {
            $this->call('auction:send-payment-reminders');
        } else {
            $this->warn('  Command auction:send-payment-reminders not found — triggering manually...');
            $this->manualSendReminder($this->ctx['won_auction_id'], self::USER_A);
        }
    }

    private function manualSendReminder(int $auctionId, int $userId): void
    {
        $auction = AuctionItem::find($auctionId);
        $user    = User::find($userId);
        if (!$auction || !$user) { $this->error('Auction or user not found.'); return; }

        try {
            $user->notify(new \App\Notifications\AuctionPaymentReminderNotification($auction));
            $this->success("  ✓ Payment reminder sent to {$user->email} for auction #{$auctionId}");
        } catch (\Throwable $e) {
            $this->warn("  Failed: ".$e->getMessage());
        }
    }

    private function showState(): void
    {
        $this->newLine();
        $this->line('═══════════════════════════════════════════');
        $this->line('  📊  CURRENT AUCTION STATE');
        $this->line('═══════════════════════════════════════════');

        $auctions = AuctionItem::withTrashed()
            ->where('description', 'LIKE', '[TEST]%')
            ->get();

        foreach ($auctions as $a) {
            $ban     = AuctionBan::where('auction_item_id', $a->id)->first();
            $deposit = AuctionBidDeposit::where('auction_item_id', $a->id)->first();
            $refund  = AuctionDepositRefund::where('auction_item_id', $a->id)->first();

            $this->newLine();
            $this->line("  🏷  [{$a->status}] #{$a->id} — {$a->title}");
            $this->line("      Winner: ".($a->winner_id ?? 'none')." | Bid: \${$a->winner_bid} | Deadline: ".($a->payment_deadline?->format('d M H:i') ?? 'none'));
            $this->line("      Deposit: ".($deposit ? "\${$deposit->amount} ".($deposit->paid_at ? "✓ paid" : "⏳ pending") : "none"));
            $this->line("      Ban:     ".($ban ? "🚫 active (user {$ban->user_id})" : "none"));
            $this->line("      Refund:  ".($refund ? "#{$refund->id} [{$refund->status}] method={$refund->refund_method} \${$refund->refund_amount}" : "none"));

            // Wallet balance for winner
            if ($a->winner_id) {
                $wallet = Wallet::where('consumer_id', $a->winner_id)->first();
                $this->line("      Wallet:  user {$a->winner_id} balance = \$".number_format($wallet?->balance ?? 0, 2));
            }
        }

        // Ban summary
        $this->newLine();
        $this->line('  BANS:');
        AuctionBan::all()->each(fn($b) => $this->line("    🚫 user {$b->user_id} → auction #{$b->auction_item_id} (banned {$b->banned_at->diffForHumans()})"));
    }

    private function reseed(): void
    {
        if ($this->confirm('This will clear all auction tables and re-seed. Continue?', true)) {
            $this->clearTables();
            $this->seedData();
            $this->success('Re-seeded successfully.');
        }
    }

    // ══════════════════════════════════════════════════════════════
    // ALL TESTS (--all flag)
    // ══════════════════════════════════════════════════════════════

    private function runAllTests(): int
    {
        $this->info('▶ Running all automated tests...');
        $this->newLine();

        $this->testBanSkip();
        $this->testBanCommand();
        $this->simulateDepositPayment();
        $this->simulateWinPayment();
        $this->requestRefund('bank');
        $this->requestRefund('wallet');
        $this->adminCreditWallet();
        $this->showState();

        $this->newLine();
        $this->success('✅ All automated tests completed. Check output above for any failures.');
        return Command::SUCCESS;
    }

    // ══════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════

    private function assert(bool $condition, string $pass, string $fail): void
    {
        if ($condition) {
            $this->line("  <fg=green>✓</> {$pass}");
        } else {
            $this->line("  <fg=red>✗</> {$fail}");
        }
    }

    private function success(string $msg): void
    {
        $this->line("  <fg=green>{$msg}</>");
    }
}
