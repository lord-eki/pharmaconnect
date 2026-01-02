<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkSendOrdersToSupplierJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;

    public function __construct(
        public array $orderIds,
        public ?string $notes,
        public int $userId
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        Log::info('Starting bulk send to supplier', [
            'order_count' => count($this->orderIds),
            'user_id' => $this->userId,
        ]);

        // Process in chunks for better performance
        collect($this->orderIds)->chunk(50)->each(function ($chunk) use (&$successCount, &$failedCount, &$errors) {
            DB::transaction(function () use ($chunk, &$successCount, &$failedCount, &$errors) {
                $orders = Order::whereIn('id', $chunk)
                    ->where('status', 'pending_review')
                    ->with(['supplier.user', 'prescription'])
                    ->get();

                foreach ($orders as $order) {
                    try {
                        $order->sendToSupplier($this->notes);
                        $successCount++;

                        Log::info('Order sent to supplier in bulk', [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                        ]);
                    } catch (\Exception $e) {
                        $failedCount++;
                        $errors[] = [
                            'order_number' => $order->order_number,
                            'error' => $e->getMessage(),
                        ];

                        Log::error('Failed to send order to supplier in bulk', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
        });

        // Notify user of completion
        if ($user) {
            $this->notifyUser($user, $successCount, $failedCount, $errors);
        }

        Log::info('Bulk send to supplier completed', [
            'success_count' => $successCount,
            'failed_count' => $failedCount,
        ]);
    }

    protected function notifyUser(User $user, int $successCount, int $failedCount, array $errors): void
    {
        $notification = \Filament\Notifications\Notification::make();

        if ($failedCount === 0) {
            $notification
                ->title('Orders Sent Successfully')
                ->body("All {$successCount} orders have been sent to suppliers.")
                ->success();
        } elseif ($successCount === 0) {
            $notification
                ->title('Failed to Send Orders')
                ->body("Failed to send {$failedCount} orders. Please check the logs.")
                ->danger();
        } else {
            $notification
                ->title('Partially Completed')
                ->body("Sent {$successCount} orders successfully, {$failedCount} failed. Check logs for details.")
                ->warning();
        }

        $notification->sendToDatabase($user);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk send to supplier job failed', [
            'order_ids' => $this->orderIds,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $user = User::find($this->userId);
        if ($user) {
            \Filament\Notifications\Notification::make()
                ->title('Bulk Send Failed')
                ->body('An error occurred while sending orders to suppliers. Please try again.')
                ->danger()
                ->sendToDatabase($user);
        }
    }
}
