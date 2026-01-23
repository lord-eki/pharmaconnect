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

class SendDeliveryNoteEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120; 
    public $backoff = [10, 30, 60]; 

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Delivery $delivery,
        public User $user
    ) {
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(DeliveryNoteService $deliveryNoteService): void
    {
        try {
            Log::info('Sending delivery note email', [
                'delivery_id' => $this->delivery->id,
                'delivery_number' => $this->delivery->delivery_number,
                'user_id' => $this->user->id,
            ]);

            $success = $deliveryNoteService->sendDeliveryNoteEmail($this->delivery);

            if ($success) {
                Notification::make()
                    ->title('Email Sent Successfully')
                    ->body("Delivery note for {$this->delivery->delivery_number} has been sent to the patient")
                    ->success()
                    ->sendToDatabase($this->user);

                Log::info('Delivery note email sent successfully', [
                    'delivery_id' => $this->delivery->id,
                    'delivery_number' => $this->delivery->delivery_number,
                ]);
            } else {
                throw new \Exception('Failed to send email through mail service');
            }
        } catch (\Exception $e) {
            Log::error('Failed to send delivery note email', [
                'delivery_id' => $this->delivery->id,
                'delivery_number' => $this->delivery->delivery_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Send failure notification to user
            Notification::make()
                ->title('Email Send Failed')
                ->body("Failed to send delivery note for {$this->delivery->delivery_number}: {$e->getMessage()}")
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
        Log::error('Delivery note email job failed after all retries', [
            'delivery_id' => $this->delivery->id,
            'delivery_number' => $this->delivery->delivery_number,
            'error' => $exception->getMessage(),
        ]);

        Notification::make()
            ->title('Email Send Failed')
            ->body("Failed to send delivery note for {$this->delivery->delivery_number} after multiple attempts")
            ->danger()
            ->sendToDatabase($this->user);
    }
}
