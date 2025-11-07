<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Delivery;
use App\Models\Rider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class OrderFulfillmentService
{
    protected RiderAssignmentService $riderService;
    protected DeliveryTrackingService $trackingService;
    protected PaymentService $paymentService;
    protected CommissionService $commissionService;

    public function __construct(
        RiderAssignmentService $riderService,
        DeliveryTrackingService $trackingService,
        PaymentService $paymentService,
        CommissionService $commissionService
    ) {
        $this->riderService = $riderService;
        $this->trackingService = $trackingService;
        $this->paymentService = $paymentService;
        $this->commissionService = $commissionService;
    }

    /**
     * Handle order confirmation - triggered by supplier
     */
    public function handleOrderConfirmation(Order $order, array $data = []): array
    {
        try {
            DB::beginTransaction();

            $results = [
                'order_confirmed' => false,
                'delivery_created' => false,
                'rider_assigned' => false,
                'errors' => [],
            ];

            // 1. Confirm order and deduct stock
            $order->update([
                'status' => 'confirmed',
                'expected_delivery' => $data['expected_delivery'] ?? now()->addDays(2),
            ]);

            // Deduct stock
            foreach ($order->items as $item) {
                $supplierMedicine = \App\Models\SupplierMedicine::where('supplier_id', $order->supplier_id)
                    ->where('medicine_id', $item->medicine_id)
                    ->first();

                if ($supplierMedicine) {
                    $supplierMedicine->decrement('stock_quantity', $item->quantity);
                    $supplierMedicine->update(['last_updated' => now()]);
                }
            }

            $results['order_confirmed'] = true;

            // 2. Create delivery record
            $delivery = $this->riderService->createDeliveryForOrder($order);
            $results['delivery_created'] = true;

            // // 3. Automatically assign rider
            // $rider = $this->riderService->assignRider($delivery);
            
            // if ($rider) {
            //     $results['rider_assigned'] = true;
            //     $results['rider'] = [
            //         'id' => $rider->id,
            //         'name' => $rider->full_name,
            //         'phone' => $rider->phone,
            //         'vehicle' => $rider->vehicle_type,
            //     ];
            // } else {
            //     $results['errors'][] = 'No available rider found - manual assignment required';
            // }

            DB::commit();

            // Log::info('Order confirmation processed', [
            //     'order_id' => $order->id,
            //     'delivery_id' => $delivery->id,
            //     'rider_id' => $rider?->id,
            // ]);

            // Send notifications
            // $this->sendConfirmationNotifications($order, $delivery, $rider);

            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error processing order confirmation', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle order processing - triggered by supplier
     */
    public function handleOrderProcessing(Order $order): bool
    {
        try {
            $order->update(['status' => 'processing']);

            // If no delivery exists, create one
            if (!$order->delivery) {
                $delivery = $this->riderService->createDeliveryForOrder($order);
                
                // Try to assign rider
                // $this->riderService->assignRider($delivery);
            }

            Log::info('Order marked as processing', ['order_id' => $order->id]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error marking order as processing', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Handle pickup - triggered by rider or operations
     */
    public function handlePickup(Delivery $delivery, array $data = []): bool
    {
        try {
            DB::beginTransaction();

            // Update delivery status
            $this->riderService->updateDeliveryStatus($delivery, 'picked_up', $data);

            // Update order status
            $delivery->order->update(['status' => 'shipped']);

            // Record initial GPS location
            if (isset($data['latitude']) && isset($data['longitude'])) {
                $this->trackingService->recordLocation(
                    $delivery,
                    $data['latitude'],
                    $data['longitude'],
                    $data['accuracy'] ?? null,
                    $data['speed'] ?? null,
                    $data['heading'] ?? null
                );
            }

            DB::commit();

            Log::info('Pickup completed', ['delivery_id' => $delivery->id]);

            // Send notifications
            $this->sendPickupNotifications($delivery);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error handling pickup', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Handle delivery completion - triggered by rider
     */
    public function handleDeliveryCompletion(Delivery $delivery, array $data): array
    {
        try {
            DB::beginTransaction();

            $results = [
                'delivery_completed' => false,
                'payments_processed' => false,
                'commission_created' => false,
                'errors' => [],
            ];

            // 1. Update delivery status
            $this->riderService->updateDeliveryStatus($delivery, 'delivered', [
                'proof_of_delivery' => $data['proof_of_delivery'] ?? null,
            ]);

            // Record final GPS location
            if (isset($data['latitude']) && isset($data['longitude'])) {
                $this->trackingService->recordLocation(
                    $delivery,
                    $data['latitude'],
                    $data['longitude']
                );
            }

            $results['delivery_completed'] = true;

            // 2. Update order status
            $order = $delivery->order;
            $order->update([
                'status' => 'delivered',
                'delivered_at' => now(),
            ]);

            // 3. Process payments (payables & receivables)
            try {
                $paymentResults = $this->paymentService->processOrderPayments($order);
                $results['payments_processed'] = true;
                $results['payments'] = $paymentResults;
            } catch (\Exception $e) {
                $results['errors'][] = 'Payment processing failed: ' . $e->getMessage();
                Log::error('Payment processing failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // 4. Calculate and create commission
            try {
                $commission = $this->commissionService->calculateCommissionForOrder($order);
                
                if ($commission) {
                    $results['commission_created'] = true;
                    $results['commission'] = [
                        'id' => $commission->id,
                        'physician_id' => $commission->physician_id,
                        'amount' => $commission->commission_amount,
                        'rate' => $commission->commission_rate,
                    ];
                } else {
                    $results['errors'][] = 'Commission creation failed';
                }
            } catch (\Exception $e) {
                $results['errors'][] = 'Commission calculation failed: ' . $e->getMessage();
                Log::error('Commission calculation failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // 5. Update prescription status
            $order->prescription->markFulfilled();

            // 6. Update rider statistics
            if ($delivery->rider) {
                $delivery->rider->incrementDeliveries();
                $delivery->rider->update(['is_available' => true]);
            }

            DB::commit();

            Log::info('Delivery completion processed', [
                'delivery_id' => $delivery->id,
                'order_id' => $order->id,
                'commission_created' => $results['commission_created'],
            ]);

            // Send notifications
            $this->sendDeliveryNotifications($delivery, $order);

            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error handling delivery completion', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle delivery failure
     */
    public function handleDeliveryFailure(Delivery $delivery, string $reason): bool
    {
        try {
            DB::beginTransaction();

            // Update delivery status
            $this->riderService->updateDeliveryStatus($delivery, 'failed', [
                'failure_reason' => $reason,
            ]);

            // Mark rider as available
            if ($delivery->rider) {
                $delivery->rider->update(['is_available' => true]);
            }

            // Update order - keep it as shipped or processing for retry
            $delivery->order->update([
                'status' => 'processing',
                'notes' => ($delivery->order->notes ?? '') . "\n\nDelivery failed: " . $reason,
            ]);

            DB::commit();

            Log::info('Delivery failure handled', [
                'delivery_id' => $delivery->id,
                'reason' => $reason,
            ]);

            // Send notifications
            $this->sendFailureNotifications($delivery, $reason);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error handling delivery failure', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Reassign rider to delivery
     */
    public function reassignRider(Delivery $delivery, ?int $riderId = null): ?Rider
    {
        try {
            DB::beginTransaction();

            // Mark current rider as available
            if ($delivery->rider) {
                $delivery->rider->update(['is_available' => true]);
            }

            // Assign new rider
            if ($riderId) {
                // Manual assignment
                $rider = Rider::findOrFail($riderId);
                
                if (!$rider->is_available) {
                    throw new \Exception('Selected rider is not available');
                }

                $delivery->update([
                    'rider_id' => $rider->id,
                    'status' => 'assigned',
                ]);

                $rider->update(['is_available' => false]);
            } 

            DB::commit();

            Log::info('Rider reassigned', [
                'delivery_id' => $delivery->id,
                'new_rider_id' => $rider?->id,
            ]);

            return $rider;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error reassigning rider', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get complete fulfillment status
     */
    public function getFulfillmentStatus(Order $order): array
    {
        $delivery = $order->delivery;
        $commission = $order->commission;

        return [
            'order' => [
                'id' => $order->id,
                'number' => $order->order_number,
                'status' => $order->status,
                'total_amount' => $order->total_amount,
                'delivered_at' => $order->delivered_at?->toIso8601String(),
            ],
            'delivery' => $delivery ? [
                'id' => $delivery->id,
                'number' => $delivery->delivery_number,
                'status' => $delivery->status,
                'rider' => $delivery->rider ? [
                    'name' => $delivery->rider->full_name,
                    'phone' => $delivery->rider->phone,
                ] : null,
                'current_location' => $this->trackingService->getCurrentLocation($delivery),
                'eta' => $this->trackingService->calculateETA($delivery),
            ] : null,
            'payment' => $this->paymentService->getOrderPaymentSummary($order),
            'commission' => $commission ? [
                'id' => $commission->id,
                'physician' => $commission->physician->full_name,
                'amount' => $commission->commission_amount,
                'rate' => $commission->commission_rate,
                'status' => $commission->status,
            ] : null,
        ];
    }

    /**
     * Send confirmation notifications
     */
    protected function sendConfirmationNotifications(Order $order, Delivery $delivery, ?Rider $rider): void
    {
        // Notify physician
        // Notify patient
        // Notify rider
        // Log for operations
    }

    /**
     * Send pickup notifications
     */
    protected function sendPickupNotifications(Delivery $delivery): void
    {
        // Notify patient that order is on the way
        // Notify physician
    }

    /**
     * Send delivery notifications
     */
    protected function sendDeliveryNotifications(Delivery $delivery, Order $order): void
    {
        // Notify physician about successful delivery and commission
        // Notify patient
        // Notify operations
    }

    /**
     * Send failure notifications
     */
    protected function sendFailureNotifications(Delivery $delivery, string $reason): void
    {
        // Notify operations for immediate action
        // Notify physician
        // Notify patient
    }
}