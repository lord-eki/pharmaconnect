<?php

namespace App\Mail;

use App\Models\InsuranceClaim;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
            subject: "Insurance Claim Submission - {$this->claim->claim_number}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.insurance-claim-form-email',
            with: [
                'claim' => $this->claim,
                'provider' => $this->claim->insuranceProvider,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        try {
            // Load all necessary relationships
            $this->claim->load([
                'patient',
                'prescription.physician',
                'prescription.items.medicine',
                'prescription.orders.supplier',
                'insuranceProvider',
            ]);

            $provider = $this->claim->insuranceProvider;

            
            
            // Get branding data from the provider
            $branding = $provider->getBrandingData();

            // Prepare data for PDF
            $data = [
                'claim' => $this->claim,
                'branding' => $branding,
            ];

            // Generate PDF
            $pdf = Pdf::loadView('pdf.insurance-claim', $data)
                ->setPaper('a4')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                ]);

            // Return as attachment
            return [
                Attachment::fromData(fn () => $pdf->output(), "claim_{$this->claim->claim_number}.pdf")
                    ->withMime('application/pdf'),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate insurance claim PDF', [
                'claim_id' => $this->claim->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }
}