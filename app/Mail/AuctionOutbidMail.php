<?php

namespace App\Mail;

use App\Models\AuctionItem;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuctionOutbidMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User        $outbidUser,
        public AuctionItem $auction,
        public float       $newBidAmount,
        public float       $userMaxAutoBid = 0,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->outbidUser->email, $this->outbidUser->name)],
            subject: '🔨 You\'ve been outbid on "' . $this->auction->title . '"',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auction-outbid',
        );
    }
}
