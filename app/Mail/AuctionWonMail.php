<?php

namespace App\Mail;

use App\Models\AuctionItem;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuctionWonMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries   = 3;
    public $timeout = 60;
    public $backoff = 10;

    public function __construct(
        public AuctionItem $auction,
        public User $winner
    ) {
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                env('MAIL_NOREPLY_ADDRESS', 'no-reply@raines.africa'),
                env('MAIL_NOREPLY_NAME', 'Raines Africa')
            ),
            subject: '🏆 Congratulations! You Won the Auction — ' . $this->auction->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auction-won',
            with: [
                'auction' => $this->auction,
                'winner'  => $this->winner,
                'payUrl'  => config('app.frontend_url', env('NEXT_PUBLIC_APP_URL', 'http://localhost:3000'))
                             . '/en/account/auctions/' . $this->auction->id . '/pay',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
