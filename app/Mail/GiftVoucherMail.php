<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GiftVoucherMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $order;
    public $vouchers;
    public $customer;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 10;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, $vouchers)
    {
        $this->order = $order;
        // Ensure vouchers is always a collection
        $this->vouchers = is_array($vouchers) ? collect($vouchers) : $vouchers;
        $this->customer = $order->consumer;

        // Explicitly set the queue for this mailable
        $this->onQueue('emails');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                env('MAIL_NOREPLY_ADDRESS', 'no-reply@raines.africa'),
                env('MAIL_NOREPLY_NAME', 'Raines Africa')
            ),
            subject: 'Your Gift Vouchers - Order #' . $this->order->order_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.gift-voucher',
            with: [
                'order' => $this->order,
                'vouchers' => $this->vouchers,
                'customer' => $this->customer,
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

