<?php



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
            ->view('emails.insurance-claim-form-email')
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







