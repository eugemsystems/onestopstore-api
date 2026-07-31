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

class PendingOrderReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $order;
    public $reminderType;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, string $reminderType = 'first')
    {
        $this->order = $order;
        $this->reminderType = $reminderType;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match($this->reminderType) {
            'first' => 'Reminder: Complete Your Order #' . $this->order->order_number,
            'second' => 'Final Reminder: Your Order #' . $this->order->order_number . ' is Waiting',
            'cancellation' => 'Your Order #' . $this->order->order_number . ' Has Been Cancelled',
            default => 'Order Reminder',
        };

        return new Envelope(
            from: new Address(
                env('MAIL_NOREPLY_ADDRESS', 'no-reply@raines.africa'),
                env('MAIL_NOREPLY_NAME', 'Raines Africa')
            ),
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.pending-reminder',
            with: [
                'order' => $this->order,
                'reminderType' => $this->reminderType,
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

