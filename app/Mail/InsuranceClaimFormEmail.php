<?php

/**
 * Create app/Mail/InsuranceClaimFormMail.php
 */

namespace App\Mail;

use App\Models\InsuranceClaim;
use App\Services\InsuranceClaimPDFService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InsuranceClaimFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public InsuranceClaim $claim,
        public ?string $customMessage = null
    ) {}

    public function build()
    {
        $pdf = InsuranceClaimPDFService::generate($this->claim);
        
        return $this->subject("Insurance Claim Form - {$this->claim->claim_number}")
            ->view('emails.insurance-claim-form')
            ->with([
                'claim' => $this->claim,
                'customMessage' => $this->customMessage,
            ])
            ->attachData(
                $pdf->output(),
                "claim-{$this->claim->claim_number}.pdf",
                ['mime' => 'application/pdf']
            );
    }
}

/**
 * Create app/Mail/NewInsuranceClaimNotification.php
 */

namespace App\Mail;

use App\Models\InsuranceClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewInsuranceClaimNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public InsuranceClaim $claim) {}

    public function build()
    {
        return $this->subject("New Insurance Claim Received - {$this->claim->claim_number}")
            ->view('emails.new-insurance-claim')
            ->with(['claim' => $this->claim]);
    }
}

/**
 * Create app/Notifications/NewInsuranceClaimNotification.php
 */

namespace App\Notifications;

use App\Models\InsuranceClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewInsuranceClaimNotification extends Notification
{
    use Queueable;

    public function __construct(public InsuranceClaim $claim) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Insurance Claim - {$this->claim->claim_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A new insurance claim has been submitted for your review.")
            ->line("**Claim Number:** {$this->claim->claim_number}")
            ->line("**Patient:** {$this->claim->patient->first_name} {$this->claim->patient->last_name}")
            ->line("**Policy Number:** {$this->claim->policy_number}")
            ->line("**Claimed Amount:** KES " . number_format($this->claim->claimed_amount, 2))
            ->line("**Submission Date:** {$this->claim->submitted_at->format('F d, Y')}")
            ->action('Review Claim', url("/admin/insurance-claims/{$this->claim->id}"))
            ->line('Please review and process this claim at your earliest convenience.');
    }

    public function toArray($notifiable): array
    {
        
        return [
            'claim_id' => $this->claim->id,
            'claim_number' => $this->claim->claim_number,
            'patient_name' => "{$this->claim->patient->first_name} {$this->claim->patient->last_name}",
            'claimed_amount' => $this->claim->claimed_amount,
            'submitted_at' => $this->claim->submitted_at,
        ];
    }
} 