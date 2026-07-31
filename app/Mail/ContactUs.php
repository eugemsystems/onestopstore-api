<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactUs extends Mailable
{
    use Queueable, SerializesModels;

    public $contact;

    /**
     * Create a new message instance.
     */
    public function __construct($contact)
    {
        $this->contact = $contact;
    }

    /**
     * Get the message envelope.
     * Uses admin@ so clients can reply to their enquiry.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                env('MAIL_FROM_ADDRESS', 'admin@raines.africa'),
                env('MAIL_FROM_NAME', 'Raines Africa')
            ),
            subject: 'Contact Enquiry: ' . $this->contact->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-us',
            with: [
                'contact' => $this->contact,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
