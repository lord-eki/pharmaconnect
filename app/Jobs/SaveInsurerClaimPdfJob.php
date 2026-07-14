<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\InsuranceClaim;
use Illuminate\Support\Facades\Log;

class SaveInsurerClaimPdfJob implements ShouldQueue
{
    use Queueable;



    /**
     * Create a new job instance.
     */
    public function __construct(public InsuranceClaim $claim,
    public array $branding , public string $path)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

      // Generate PDF
        $pdf = Pdf::loadView('pdf.insurance-claim', [
            'claim' => $this->claim,
            'branding' => $this->branding,
        ])
            ->setPaper('a4')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

       

        Storage::disk('public')->put($this->path ,$pdf->output());



        // Update claim record with PDF path
        $claim->update([
            'pdf_path' => $this->path,
            'pdf_generated_at' => now(),
        ]);

        
        \Log::info('Insurance claim PDF generated and saved', [
            'claim_id' => $this->claim->id,
            'claim_number' => $this->claim->claim_number,
            'path' => $this->path,
            'size' => Storage::disk('public')->size($this->path),
        ]);

    }
}
