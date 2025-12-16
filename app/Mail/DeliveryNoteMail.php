<?php

namespace App\Mail;

use App\Models\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class DeliveryNoteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $delivery;
    public $pdfPath;

    /**
     * Create a new message instance.
     */
    public function __construct(Delivery $delivery, string $pdfPath)
    {
        $this->delivery = $delivery;
        $this->pdfPath = $pdfPath;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Delivery Confirmation - {$this->delivery->delivery_number}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.delivery-note',
            with: [
                'delivery' => $this->delivery,
                'order' => $this->delivery->order,
                'patient' => $this->delivery->order->patient,
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
        if (!file_exists($this->pdfPath)) {
            return [];
        }

        return [
            Attachment::fromPath($this->pdfPath)
                ->as("Delivery_Note_{$this->delivery->delivery_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}