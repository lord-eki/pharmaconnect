<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class FixMissingDeliveriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:fix-missing-deliveries 
                            {--dry-run : Run without making changes}
                            {--status=* : Only fix orders with specific status(es)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create missing delivery records for existing orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $statuses = $this->option('status') ?: ['pending', 'confirmed', 'processing', 'shipped'];

        $this->info('Checking for orders without deliveries...');
        $this->newLine();

        // Find orders without deliveries
        $ordersQuery = Order::whereDoesntHave('delivery')
            ->whereIn('status', $statuses)
            ->with(['prescription.patient', 'supplier']);

        $ordersCount = $ordersQuery->count();

        if ($ordersCount === 0) {
            $this->info('✅ All orders have deliveries. Nothing to fix!');
            return Command::SUCCESS;
        }

        $this->warn("Found {$ordersCount} orders without deliveries.");
        $this->newLine();

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $orders = $ordersQuery->get();
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        $progressBar = $this->output->createProgressBar($ordersCount);
        $progressBar->start();

        foreach ($orders as $order) {
            try {
                if (!$isDryRun) {
                    $order->createDelivery();
                    $successCount++;
                } else {
                    // Just validate that we can create delivery
                    if (!$order->prescription || !$order->prescription->patient || !$order->supplier) {
                        throw new \Exception('Missing required relationships');
                    }
                    $successCount++;
                }
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'error' => $e->getMessage(),
                ];
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display results
        if ($isDryRun) {
            $this->info("📊 DRY RUN RESULTS:");
            $this->info("   Would create deliveries for: {$successCount} orders");
        } else {
            $this->info("✅ RESULTS:");
            $this->info("   Successfully created deliveries: {$successCount}");
        }

        if ($errorCount > 0) {
            $this->error("   Failed: {$errorCount}");
            $this->newLine();
            
            $this->error("❌ ERRORS:");
            foreach ($errors as $error) {
                $this->error("   Order #{$error['order_number']} (ID: {$error['order_id']}): {$error['error']}");
            }
        }

        $this->newLine();

        // Show sample of what was created
        if (!$isDryRun && $successCount > 0) {
            $this->info('📦 Sample of created deliveries:');
            $sampleOrders = Order::whereIn('id', $orders->take(5)->pluck('id'))
                ->with('delivery')
                ->get();

            $this->table(
                ['Order #', 'Delivery #', 'Status', 'Recipient', 'Fee'],
                $sampleOrders->map(function ($order) {
                    return [
                        $order->order_number,
                        $order->delivery?->delivery_number ?? 'N/A',
                        $order->delivery?->status ?? 'N/A',
                        $order->delivery?->recipient_name ?? 'N/A',
                        'KES ' . number_format($order->delivery?->delivery_fee ?? 0, 2),
                    ];
                })
            );
        }

        return $successCount > 0 ? Command::SUCCESS : Command::FAILURE;
    }
}