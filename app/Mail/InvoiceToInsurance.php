<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class InvoiceToInsurance extends Mailable
{
    use Queueable, SerializesModels;

    public $emailData;
    public $pdfPath;

    /**
     * Create a new message instance.
     */
    public function __construct(array $emailData, string $pdfPath)
    {
        $this->emailData = $emailData;
        $this->pdfPath = $pdfPath;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $invoice = $this->emailData['invoice'];
        
        return new Envelope(
            subject: "Medical Invoice - {$invoice->invoice_number}",
            replyTo: ['billing@pharmaconnect.com'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-to-insurance',
            with: [
                'invoice' => $this->emailData['invoice'],
                'insuranceProvider' => $this->emailData['insuranceProvider'],
                'patient' => $this->emailData['patient'],
                'additionalMessage' => $this->emailData['additionalMessage'] ?? null,
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
        $invoice = $this->emailData['invoice'];
        $fileName = "Invoice_{$invoice->invoice_number}.pdf";
        
        return [
            Attachment::fromPath($this->pdfPath)
                ->as($fileName)
                ->withMime('application/pdf'),
        ];
    }
}