<?php

namespace App\Mail;

use App\Models\InvoiceQuotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceQuotationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $document;
    public $customMessage;
    public $senderName;

    /**
     * Create a new message instance.
     */
    public function __construct(InvoiceQuotation $document, string $customMessage, string $senderName = null)
    {
        $this->document = $document;
        $this->customMessage = $customMessage;
        $this->senderName = $senderName ?? 'Raines Africa';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                env('MAIL_FROM_ADDRESS', 'admin@raines.africa'),
                env('MAIL_FROM_NAME', 'Raines Africa')
            ),
            subject: $this->document->getDocumentTypeLabel() . ' #' . $this->document->document_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-quotation',
            with: [
                'document' => $this->document,
                'customMessage' => $this->customMessage,
                'senderName' => $this->senderName,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        // Increase limits for PDF generation
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        // Generate PDF
        $template = match($this->document->document_type) {
            'invoice' => 'invoice',
            'quotation' => 'quotation',
            'receipt' => 'receipt',
            'proforma' => 'proforma',
            'delivery_note' => 'delivery_note',
            default => 'invoice',
        };

        try {
            $pdf = Pdf::loadView("admin.invoices-quotations.templates.{$template}", [
                'document' => $this->document
            ]);
            $pdf->setPaper('a4', 'portrait');

            // Enable remote for images
            $pdf->setOption('isRemoteEnabled', true);
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isFontSubsettingEnabled', true);
            $pdf->setOption('chroot', '/');
            $pdf->setOption('enable_remote', true);

            // Disable debug for performance
            $pdf->setOption('debugCss', false);
            $pdf->setOption('debugLayout', false);

            // Set HTTP context for reliable image loading
            $context = stream_context_create([
                'http' => [
                    'timeout' => 60,
                    'method' => 'GET',
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'follow_location' => 1,
                    'max_redirects' => 5
                ],
                'https' => [
                    'timeout' => 60,
                    'method' => 'GET',
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'follow_location' => 1,
                    'max_redirects' => 5
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                    'SNI_enabled' => true
                ]
            ]);

            $pdf->setHttpContext($context);

            $filename = strtolower($this->document->document_number) . '.pdf';

            return [
                Attachment::fromData(fn () => $pdf->output(), $filename)
                    ->withMime('application/pdf'),
            ];
        } catch (\Exception $e) {
            \Log::error('PDF attachment generation failed', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage()
            ]);

            // Return empty array if PDF generation fails
            return [];
        }
    }
}
