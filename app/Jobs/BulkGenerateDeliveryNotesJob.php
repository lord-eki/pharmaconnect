<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Delivery;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use App\Jobs\GenerateDeliveryNoteJob;

class BulkGenerateDeliveryNotesJob implements ShouldQueue
{
   use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1; // Don't retry bulk jobs
    public $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $deliveryIds,
        public User $user,
        public bool $sendEmails = false
    ) {
        $this->onQueue('documents');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $deliveries = Delivery::whereIn('id', $this->deliveryIds)
                ->where('status', 'delivered')
                ->get();

            if ($deliveries->isEmpty()) {
                Notification::make()
                    ->title('No Eligible Deliveries')
                    ->body('No delivered deliveries found for bulk generation')
                    ->warning()
                    ->sendToDatabase($this->user);
                return;
            }

            // Create individual jobs for each delivery
            $jobs = $deliveries->map(function ($delivery) {
                return new GenerateDeliveryNoteJob(
                    $delivery,
                    $this->user,
                    $this->sendEmails
                );
            })->toArray();

            // Dispatch as a batch
            $batch = Bus::batch($jobs)
                ->name("Bulk Delivery Notes - {$this->user->name}")
                ->onQueue('documents')
                ->then(function (Batch $batch) {
                    // All jobs completed successfully
                    Notification::make()
                        ->title('Bulk Generation Complete')
                        ->body("Successfully generated {$batch->totalJobs} delivery notes")
                        ->success()
                        ->sendToDatabase($this->user);

                    Log::info('Bulk delivery note generation completed', [
                        'batch_id' => $batch->id,
                        'total_jobs' => $batch->totalJobs,
                        'user_id' => $this->user->id,
                    ]);
                })
                ->catch(function (Batch $batch, \Throwable $e) {
                    // First batch job failure
                    Log::error('Bulk delivery note generation batch failed', [
                        'batch_id' => $batch->id,
                        'error' => $e->getMessage(),
                    ]);
                })
                ->finally(function (Batch $batch) {
                    // Batch has finished executing
                    $failed = $batch->failedJobs;
                    $successful = $batch->totalJobs - $failed;

                    if ($failed > 0) {
                        Notification::make()
                            ->title('Bulk Generation Completed with Errors')
                            ->body("{$successful} succeeded, {$failed} failed. Check logs for details.")
                            ->warning()
                            ->sendToDatabase($this->user);
                    }

                    Log::info('Bulk delivery note generation finished', [
                        'batch_id' => $batch->id,
                        'successful' => $successful,
                        'failed' => $failed,
                    ]);
                })
                ->allowFailures()
                ->dispatch();

            // Notify user that batch has started
            Notification::make()
                ->title('Bulk Generation Started')
                ->body("Processing {$deliveries->count()} delivery notes in the background")
                ->info()
                ->sendToDatabase($this->user);

            Log::info('Bulk delivery note generation batch dispatched', [
                'batch_id' => $batch->id,
                'delivery_count' => $deliveries->count(),
                'user_id' => $this->user->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to dispatch bulk delivery note generation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('Bulk Generation Failed')
                ->body("Failed to start bulk generation: {$e->getMessage()}")
                ->danger()
                ->sendToDatabase($this->user);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk delivery note generation job failed', [
            'user_id' => $this->user->id,
            'delivery_count' => count($this->deliveryIds),
            'error' => $exception->getMessage(),
        ]);

        Notification::make()
            ->title('Bulk Generation Failed')
            ->body('Failed to start bulk delivery note generation')
            ->danger()
            ->sendToDatabase($this->user);
    }
}
