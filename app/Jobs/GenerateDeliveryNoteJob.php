<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Delivery;
use App\Models\User;
use App\Services\DeliveryNoteService;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

class GenerateDeliveryNoteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; 
    public $backoff = [30, 60, 120]; 

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Delivery $delivery,
        public User $user,
        public bool $sendEmail = false
    ) {
        $this->onQueue('documents'); 
    }

    /**
     * Execute the job.
     */
    public function handle(DeliveryNoteService $deliveryNoteService): void
    {
        try {
            Log::info('Generating delivery note', [
                'delivery_id' => $this->delivery->id,
                'delivery_number' => $this->delivery->delivery_number,
                'send_email' => $this->sendEmail,
            ]);

            if ($this->sendEmail) {
                $result = $deliveryNoteService->generateAndSendDeliveryNote(
                    $this->delivery,
                    $this->user
                );

                if ($result['success']) {
                    // Send success notification to user
                    Notification::make()
                        ->title('Delivery Note Generated')
                        ->body("Delivery note for {$this->delivery->delivery_number} has been ".
                              ($result['email_sent'] ? 'generated and emailed' : 'generated'))
                        ->success()
                        ->sendToDatabase($this->user);

                    Log::info('Delivery note generated successfully', [
                        'delivery_id' => $this->delivery->id,
                        'document_id' => $result['document']->id,
                        'email_sent' => $result['email_sent'],
                    ]);
                } else {
                    throw new \Exception($result['error']);
                }
            } else {
                $document = $deliveryNoteService->generateDeliveryNote(
                    $this->delivery,
                    $this->user
                );

                Notification::make()
                    ->title('Delivery Note Generated')
                    ->body("Delivery note for {$this->delivery->delivery_number} has been generated")
                    ->success()
                    ->sendToDatabase($this->user);

                Log::info('Delivery note generated successfully', [
                    'delivery_id' => $this->delivery->id,
                    'document_id' => $document->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate delivery note', [
                'delivery_id' => $this->delivery->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Send failure notification to user
            Notification::make()
                ->title('Delivery Note Generation Failed')
                ->body("Failed to generate delivery note for {$this->delivery->delivery_number}: {$e->getMessage()}")
                ->danger()
                ->sendToDatabase($this->user);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Delivery note generation job failed after all retries', [
            'delivery_id' => $this->delivery->id,
            'error' => $exception->getMessage(),
        ]);

        Notification::make()
            ->title('Delivery Note Generation Failed')
            ->body("Failed to generate delivery note for {$this->delivery->delivery_number} after multiple attempts")
            ->danger()
            ->sendToDatabase($this->user);
    }
}
