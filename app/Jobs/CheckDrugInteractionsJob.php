<?php

namespace App\Jobs;

use App\Models\Prescription;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckDrugInteractionsJob implements ShouldQueue
{
   use Queueable, InteractsWithQueue, SerializesModels, Dispatchable;

    public $timeout = 300;
    public $tries = 3;
    public $backoff = [60, 180, 300]; // Exponential backoff

    /**
     * Create a new job instance.
     */
    public function __construct(public Prescription $prescription)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting drug interaction check', [
            'prescription_id' => $this->prescription->id,
        ]);

        try {
            $interactions = $this->prescription->checkDrugInteractions();

            // Notify physician if critical interactions found
            if ($interactions && count($interactions) > 0) {
                Notification::make()
                    ->title('Drug Interactions Detected')
                    ->body("Found {count($interactions)} potential drug interaction(s) for prescription #{$this->prescription->id}")
                    ->warning()
                    ->sendToDatabase($this->prescription->physician);
            }

            Log::info('Drug interaction check completed', [
                'prescription_id' => $this->prescription->id,
                'interactions_found' => count($interactions ?? []),
            ]);
        } catch (\Exception $e) {
            Log::error('Drug interaction check failed', [
                'prescription_id' => $this->prescription->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Drug interaction check permanently failed', [
            'prescription_id' => $this->prescription->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Notify physician of failure
        Notification::make()
            ->title('Drug Interaction Check Failed')
            ->body("Unable to check drug interactions for prescription #{$this->prescription->id}. Please review manually.")
            ->danger()
            ->sendToDatabase($this->prescription->physician);
    }

}
