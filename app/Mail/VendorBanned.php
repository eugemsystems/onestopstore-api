<?php

namespace App\Mail;

use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VendorBanned extends Mailable
{
    use Queueable, SerializesModels;

    public $store;
    public $vendorName;
    public $storeName;
    public $banReason;

    /**
     * Create a new message instance.
     */
    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->vendorName = $store->vendor->name ?? 'Vendor';
        $this->storeName = $store->store_name;
        $this->banReason = $store->ban_reason;
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
            subject: 'Important: Your Vendor Account Has Been Suspended - Raines Africa',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.vendor-banned',
            text: 'emails.vendor-banned-text',
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

