<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CartReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public int $reminderNumber;
    public int $itemCount;
    public array $items;

    /**
     * Create a new message instance.
     *
     * @param array<int, array{name:string,image_url:?string,price:float,quantity:int}> $items
     */
    public function __construct(User $user, int $reminderNumber, int $itemCount, array $items = [])
    {
        $this->user = $user;
        $this->reminderNumber = $reminderNumber;
        $this->itemCount = $itemCount;
        $this->items = $items;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->reminderNumber) {
            1 => 'You left something in your cart 🛒',
            2 => 'Still thinking it over? Your cart is waiting',
            3 => 'Last reminder — your cart is about to expire',
            default => 'You have items waiting in your cart',
        };

        return new Envelope(
            from: new Address(
                env('MAIL_NOREPLY_ADDRESS', 'no-reply@onestopstore.co.zw'),
                env('MAIL_NOREPLY_NAME', 'One Stop Store')
            ),
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $cartUrl = config('app.frontend_url', 'https://onestopstore.co.zw') . '/' . app()->getLocale() . '/cart';
        $currencySymbol = \App\Models\Currency::where('system_reserve', 1)->value('symbol') ?? '$';

        return new Content(
            view: 'emails.cart.reminder',
            with: [
                'user' => $this->user,
                'reminderNumber' => $this->reminderNumber,
                'itemCount' => $this->itemCount,
                'items' => $this->items,
                'currencySymbol' => $currencySymbol,
                'cartUrl' => $cartUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
