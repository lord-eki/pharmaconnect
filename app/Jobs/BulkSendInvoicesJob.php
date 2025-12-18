<?php

namespace App\Jobs;

use App\Services\InvoiceService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BulkSendInvoicesJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    public function __construct(
        public array $invoiceIds,
        public array $options = [],
        public ?string $userId = null
    ) {}

    public function handle(InvoiceService $invoiceService): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        try {
            $results = $invoiceService->bulkSendInvoices($this->invoiceIds, $this->options);
            
            Log::info('Bulk invoice sending completed', [
                'success_count' => count($results['success']),
                'failed_count' => count($results['failed']),
                'user_id' => $this->userId,
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk invoice sending failed', [
                'invoice_ids' => $this->invoiceIds,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
}
