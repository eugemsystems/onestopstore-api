<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketReplyNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $message;
    public $repliedBy;

    /**
     * Create a new message instance.
     */
    public function __construct(Ticket $ticket, TicketMessage $message)
    {
        $this->ticket = $ticket;
        $this->message = $message;
        $this->repliedBy = $message->user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // When admin replies → notify customer using admin@ so they can write back.
        // When customer replies → notify admin internally using no-reply@.
        $isAdminReply = $this->message->isFromAdmin();

        $fromAddress = $isAdminReply
            ? env('MAIL_FROM_ADDRESS', 'admin@raines.africa')
            : env('MAIL_NOREPLY_ADDRESS', 'no-reply@raines.africa');

        $fromName = $isAdminReply
            ? env('MAIL_FROM_NAME', 'Raines Africa')
            : env('MAIL_NOREPLY_NAME', 'Raines Africa');

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: "New Reply on Ticket #{$this->ticket->ticket_number}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-reply',
            with: [
                'ticketNumber' => $this->ticket->ticket_number,
                'ticketSubject' => $this->ticket->subject,
                'messageContent' => $this->message->message,
                'repliedByName' => $this->repliedBy->name,
                'repliedAt' => $this->message->created_at,
                'ticketUrl' => $this->getTicketUrl(),
                'isFromAdmin' => $this->message->isFromAdmin(),
            ],
        );
    }

    /**
     * Get the ticket URL based on who is receiving the email
     */
    private function getTicketUrl(): string
    {
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
        $adminUrl = config('app.url', env('APP_URL', 'http://localhost:8000'));

        // If reply is from admin, send customer to frontend
        if ($this->message->isFromAdmin()) {
            return rtrim($frontendUrl, '/') . '/en/account/tickets/' . $this->ticket->id;
        }

        // If reply is from customer, send admin to admin panel
        return rtrim($adminUrl, '/') . '/admin/tickets/' . $this->ticket->id;
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

