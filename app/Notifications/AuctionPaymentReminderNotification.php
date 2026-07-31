<?php

namespace App\Notifications;

use App\Models\AuctionBidDeposit;
use App\Models\AuctionItem;
use App\Models\AuctionSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AuctionPaymentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly AuctionItem $auction,
        private readonly int $reminderNumber, // 1 or 2
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $auction  = $this->auction;
        $setting  = AuctionSetting::current();
        $symbol   = config('app.currency_symbol', '$');

        // ── Calculate amounts (same logic as markPaid) ──────────────
        $deposit = AuctionBidDeposit::where('user_id', $notifiable->id)
            ->where('auction_item_id', $auction->id)
            ->whereNotNull('paid_at')
            ->first();

        $winnerBid    = (float) $auction->winner_bid;
        $deliveryCost = (float) ($auction->delivery_cost ?? 0);
        $depositPaid  = $deposit ? (float) $deposit->amount : 0;
        $totalDue     = $winnerBid + $deliveryCost;
        $balanceDue   = max(0, $totalDue - $depositPaid);

        // ── Deadline + urgency ───────────────────────────────────────
        $deadline   = $auction->payment_deadline;
        $hoursLeft  = $deadline ? (int) max(0, floor(now()->diffInHours($deadline, false))) : null;
        $isOverdue  = $deadline && now()->isAfter($deadline);

        $urgency = match(true) {
            $isOverdue => 'overdue',
            $hoursLeft !== null && $hoursLeft <= 4  => 'critical',
            $hoursLeft !== null && $hoursLeft <= 12 => 'urgent',
            default => 'normal',
        };

        $frontendUrl = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')), '/');
        $payUrl      = $frontendUrl . '/en/account/auctions/wins';

        return (new MailMessage)
            ->subject($isOverdue
                ? "🚨 OVERDUE: Payment of {$symbol}" . number_format($balanceDue, 2) . " still outstanding — \"{$auction->title}\""
                : "⏰ Reminder #{$this->reminderNumber}: {$symbol}" . number_format($balanceDue, 2) . " payment due — \"{$auction->title}\""
            )
            ->view('emails.auction-payment-reminder', [
                'winner'         => $notifiable,
                'auction'        => $auction,
                'symbol'         => $symbol,
                'winnerBid'      => $winnerBid,
                'deliveryCost'   => $deliveryCost,
                'depositPaid'    => $depositPaid,
                'totalDue'       => $totalDue,
                'balanceDue'     => $balanceDue,
                'deadline'       => $deadline,
                'hoursLeft'      => $hoursLeft,
                'isOverdue'      => $isOverdue,
                'urgency'        => $urgency,
                'reminderNumber' => $this->reminderNumber,
                'payUrl'         => $payUrl,
                'hoursToPay'     => $setting->hours_to_pay ?? 48,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'    => "Payment Reminder #{$this->reminderNumber} — {$this->auction->title}",
            'message'  => "Your payment for \"{$this->auction->title}\" is still outstanding. Please pay by the deadline to avoid account restrictions.",
            'type'     => 'auction_payment_reminder',
        ];
    }
}
