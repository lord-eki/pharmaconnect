<?php

namespace App\Jobs;

use App\Models\Prescription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class GenerateQuotationJob implements ShouldQueue
{
   use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public $timeout = 300;
    public $tries = 3;
    public $backoff = [60, 180, 300];

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
        Log::info('Starting quotation generation', [
            'prescription_id' => $this->prescription->id,
        ]);

        try {
            $quotation = $this->prescription->generateQuotation();

            // Notify physician when quotation is ready
            Notification::make()
                ->title('Quotation Generated')
                ->body("Quotation for prescription #{$this->prescription->id} is ready. Total: KES " . number_format($quotation->total_amount ?? 0, 2))
                ->success()
                ->sendToDatabase($this->prescription->physician);

            Log::info('Quotation generation completed', [
                'prescription_id' => $this->prescription->id,
                'quotation_id' => $quotation->id ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Quotation generation failed', [
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
        Log::error('Quotation generation permanently failed', [
            'prescription_id' => $this->prescription->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Notify physician of failure
        Notification::make()
            ->title('Quotation Generation Failed')
            ->body("Unable to generate quotation for prescription #{$this->prescription->id}. Please try again or contact support.")
            ->danger()
            ->sendToDatabase($this->prescription->physician);
    }
}
