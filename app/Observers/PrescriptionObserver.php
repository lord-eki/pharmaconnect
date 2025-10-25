<?php

namespace App\Observers;

use App\Jobs\GenerateQuotationJob;
use App\Models\Prescription;
use App\Jobs\CheckDrugInteractionsJob;
use Illuminate\Support\Facades\Log;

class PrescriptionObserver
{
    /**
     * Handle the Prescription "created" event.
     */
    public function created(Prescription $prescription): void
    {
        CheckDrugInteractionsJob::dispatch($prescription)
        ->onQueue('drug-interactions')
        ->delay(now()->addSeconds(5));

        GenerateQuotationJob::dispatch($prescription)
        ->onQueue('quotations')
        ->delay(now()->addSeconds(5));

        Log::info('Prescription created with ID: '.$prescription->id);
    }

    /**
     * Handle the Prescription "updated" event.
     */
    public function updated(Prescription $prescription): void
    {
          // Check if items relationship was modified (if using pivot/junction table)
        // Or check specific fields that require reprocessing
        
        // Option 1: Always reprocess on update
        $this->reprocessPrescription($prescription);
        
        // Option 2: Only reprocess if specific fields changed
        // if ($prescription->wasChanged(['patient_id', 'insurance_covered'])) {
        //     $this->reprocessPrescription($prescription);
        // }
    }

        /**
     * Reprocess prescription checks and quotations
     */
    protected function reprocessPrescription(Prescription $prescription): void
    {
        CheckDrugInteractionsJob::dispatch($prescription)
            ->onQueue('drug-interactions');

        GenerateQuotationJob::dispatch($prescription)
            ->onQueue('quotations');

        Log::info('Reprocessing background jobs for prescription', [
            'prescription_id' => $prescription->id,
        ]);
    }


    /**
     * Handle the Prescription "deleted" event.
     */
    public function deleted(Prescription $prescription): void
    {
         Log::info('Prescription deleted', [
            'prescription_id' => $prescription->id,
        ]);
    }

    /**
     * Handle the Prescription "restored" event.
     */
    public function restored(Prescription $prescription): void
    {
        //
    }

    /**
     * Handle the Prescription "force deleted" event.
     */
    public function forceDeleted(Prescription $prescription): void
    {
        //
    }
}
