<?php

namespace App\Mail;

use App\Models\InsuranceClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InsuranceClaimFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public InsuranceClaim $claim;

    /**
     * Create a new message instance.
     */
    public function __construct(InsuranceClaim $claim)
    {
        $this->claim = $claim;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Insurance Claim: ' . $this->claim->claim_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.insurance-claim-notification',
            with: [
                'claim' => $this->claim,
                'prescription' => $this->claim->prescription,
                'patient' => $this->claim->patient,
                'provider' => $this->claim->insuranceProvider,
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