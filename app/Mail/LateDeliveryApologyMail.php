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

class LateDeliveryApologyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public Order $order;
    /** @var array<int, array{name:string, variation:string|null, eta:string|null, days_overdue:int}> */
    public array $overdueItems;
    public int $emailNumber;

    public function __construct(Order $order, array $overdueItems, int $emailNumber = 1)
    {
        $this->order = $order;
        $this->overdueItems = $overdueItems;
        $this->emailNumber = $emailNumber;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.noreply_address', 'no-reply@raines.africa'),
                config('mail.noreply_name', 'Raines Africa')
            ),
            subject: 'We sincerely apologise for your order delay — Order #' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.late-delivery-apology',
            with: [
                'order'        => $this->order,
                'overdueItems' => $this->overdueItems,
                'emailNumber'  => $this->emailNumber,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
