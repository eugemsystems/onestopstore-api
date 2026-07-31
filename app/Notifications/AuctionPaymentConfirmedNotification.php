<?php

namespace App\Notifications;

use App\Models\AuctionBidDeposit;
use App\Models\AuctionItem;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AuctionPaymentConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private ?string $qrCodePngData = null;

    public function __construct(
        private readonly AuctionItem $auction,
        private readonly Order $order,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $auction  = $this->auction;
        $order    = $this->order;
        $symbol   = $order->currency_symbol ?? config('app.currency_symbol', '$');

        // Amounts
        $winnerBid    = (float) $auction->winner_bid;
        $deliveryCost = (float) ($auction->delivery_cost ?? 0);

        // Find deposit paid by this winner for this auction
        $deposit     = AuctionBidDeposit::where('user_id', $auction->winner_id)
            ->where('auction_item_id', $auction->id)
            ->whereNotNull('paid_at')
            ->first();
        $depositPaid = $deposit ? (float) $deposit->amount : 0;

        // "Balance Paid" = the total amount settled:
        // winnerBid + delivery, minus the deposit that was already paid
        // This should match what appears on the order's grand_total
        // But if grand_total > 0 we trust it; otherwise compute it ourselves
        $orderTotal = (float) $order->grand_total;
        $amountPaid = $orderTotal > 0 
            ? $orderTotal 
            : max(0, $winnerBid + $deliveryCost - $depositPaid);

        $frontendUrl = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')), '/');
        $wonItemsUrl = $frontendUrl . '/en/account/auctions/wins';

        // ── Generate QR code (same pattern as UpdateOrderStatusNotification) ──
        $qrUrl = null;
        $qrNote = 'Show this QR code when collecting your auction item';

        try {
            $qrData = json_encode([
                'type'          => 'auction_collection',
                'order_number'  => $order->order_number,
                'order_id'      => $order->id,
                'auction_id'    => $auction->id,
                'auction_title' => $auction->title,
                'auto_collect'  => $auction->fulfillment_method !== 'delivery',
                'customer_name' => $notifiable->name ?? 'Customer',
                'timestamp'     => now()->timestamp,
            ], JSON_UNESCAPED_SLASHES);

            $qrCodePng = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                ->size(300)
                ->margin(1)
                ->errorCorrection('H')
                ->generate($qrData);

            $this->qrCodePngData = (string) $qrCodePng;

            $qrCodeService = app(\App\Services\OrderQRCodeService::class);
            $qrUrl = $qrCodeService->generateAndSave($order->order_number, $qrData, 'auction_payment', $this->qrCodePngData);

            if (!$qrUrl) {
                $qrUrl = 'cid:order_qr_code';
            }
        } catch (\Throwable $e) {
            // QR generation is non-critical — log and continue without it
            \Illuminate\Support\Facades\Log::warning('Auction QR code generation failed: ' . $e->getMessage());
        }

        $mailMessage = (new MailMessage)
            ->subject("✅ Payment Confirmed — {$auction->title}")
            ->view('emails.auction-payment-receipt', [
                'winner'       => $notifiable,
                'auction'      => $auction,
                'order'        => $order,
                'symbol'       => $symbol,
                'winnerBid'    => $winnerBid,
                'deliveryCost' => $deliveryCost,
                'depositPaid'  => $depositPaid,
                'amountPaid'   => $amountPaid,
                'wonItemsUrl'  => $wonItemsUrl,
                'qr_url'       => $qrUrl,
                'qr_note'      => $qrNote,
            ]);

        // Embed QR code as CID attachment for email clients that block external images
        if ($this->qrCodePngData) {
            $qrData = $this->qrCodePngData;
            $mailMessage->withSymfonyMessage(function ($message) use ($qrData) {
                $message->embed($qrData, 'order_qr_code', 'image/png');
            });
        }

        return $mailMessage;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'Auction Payment Confirmed',
            'message' => "Your payment for \"{$this->auction->title}\" (Order #{$this->order->order_number}) has been confirmed.",
            'type'    => 'auction_payment',
        ];
    }
}
