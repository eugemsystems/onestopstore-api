<?php

namespace App\Mail;

use App\Models\WithdrawRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WithdrawalApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $withdrawal;
    public $vendor;
    public $store;
    public $paymentDetails;

    /**
     * Create a new message instance.
     */
    public function __construct(WithdrawRequest $withdrawal)
    {
        $this->withdrawal = $withdrawal;
        $this->vendor = $withdrawal->user;
        $this->store = $withdrawal->user->store;
        $this->paymentDetails = is_array($withdrawal->payment_details)
            ? $withdrawal->payment_details
            : json_decode($withdrawal->payment_details, true);
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
            subject: 'Withdrawal Request Approved - $' . number_format($this->withdrawal->amount, 2),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.withdrawal-approved',
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
