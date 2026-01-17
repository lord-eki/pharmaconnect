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
     * Handle order confirmation - Delivery created in Order model boot
     */
    public function handleOrderConfirmation(Order $order, array $data = []): array
    {
        try {
            DB::beginTransaction();

            $results = [
                'order_confirmed' => false,
                'delivery_exists' => false,
                'all_orders_confirmed' => false,
                'errors' => [],
            ];

            // Confirm order and deduct stock
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

            // Check delivery status
            $prescription = $order->prescription;
            if ($prescription->delivery) {
                $results['delivery_exists'] = true;
                
                // Check if all orders confirmed
                $allConfirmed = $prescription->orders()
                    ->whereNotIn('status', ['confirmed', 'delivered'])
                    ->doesntExist();

                $results['all_orders_confirmed'] = $allConfirmed;

                if ($allConfirmed) {
                    Log::info('All prescription orders confirmed', [
                        'prescription_id' => $prescription->id,
                        'delivery_id' => $prescription->delivery->id,
                    ]);
                }
            }

            DB::commit();

            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error processing order confirmation', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle pickup of specific order within delivery
     */
    public function handleOrderPickup(Delivery $delivery, Order $order, array $data = []): bool
    {
        try {
            DB::beginTransaction();

            // Verify order belongs to this delivery
            if (!$delivery->orders->contains($order)) {
                throw new \Exception('Order does not belong to this delivery');
            }

            // Mark this specific order as picked up
            $delivery->markOrderPickedUp($order->id, $data['notes'] ?? null);

            // Record GPS location if provided
            if (isset($data['latitude']) && isset($data['longitude'])) {
                $this->trackingService->recordLocation(
                    $delivery,
                    $data['latitude'],
                    $data['longitude'],
                    $data['accuracy'] ?? null
                );
            }

            DB::commit();

            Log::info('Order picked up within prescription delivery', [
                'delivery_id' => $delivery->id,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'all_picked_up' => $delivery->allOrdersPickedUp(),
                'remaining_pickups' => $delivery->orders->where('pivot.pickup_status', '!=', 'picked_up')->count(),
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error handling order pickup', [
                'delivery_id' => $delivery->id,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Handle delivery completion - processes ALL orders
     */
    public function handleDeliveryCompletion(Delivery $delivery, array $data): array
    {
        try {
            DB::beginTransaction();

            $results = [
                'delivery_completed' => false,
                'payments_processed' => false,
                'commission_created' => false,
                'orders_processed' => 0,
                'errors' => [],
            ];

            foreach ($delivery->orders as $order) {
            $pivot = $delivery->orders->find($order->id)?->pivot;
            if ($pivot && $pivot->pickup_status !== 'picked_up') {
                $delivery->markOrderPickedUp($order->id, 'Auto-marked during delivery completion');
            }
        }

            // Update delivery status
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

            // Process payments and commissions for ALL orders
            $totalPaymentsProcessed = 0;
            $totalCommissionsCreated = 0;

            foreach ($delivery->orders as $order) {
                try {
                    // Process payments
                    $paymentResults = $this->paymentService->processOrderPayments($order);
                    if ($paymentResults) {
                        $totalPaymentsProcessed++;
                    }
                    
                    // Calculate commission
                    $commission = $this->commissionService->calculateCommissionForOrder($order);
                    if ($commission) {
                        $totalCommissionsCreated++;
                    }
                    
                    $results['orders_processed']++;
                    
                } catch (\Exception $e) {
                    $results['errors'][] = "Order {$order->order_number}: {$e->getMessage()}";
                    Log::error('Error processing order in delivery', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $results['payments_processed'] = ($totalPaymentsProcessed === $delivery->orders->count());
            $results['commission_created'] = ($totalCommissionsCreated === $delivery->orders->count());

            // Update prescription status
            $delivery->prescription->markFulfilled();

            // Update rider
            if ($delivery->rider) {
                $delivery->rider->incrementDeliveries();
                $delivery->rider->update(['is_available' => true]);
            }

            DB::commit();

            Log::info('Prescription delivery completed', [
                'delivery_id' => $delivery->id,
                'delivery_number' => $delivery->delivery_number,
                'prescription_id' => $delivery->prescription_id,
                'prescription_number' => $delivery->prescription->prescription_number,
                'orders_processed' => $results['orders_processed'],
                'total_orders' => $delivery->orders->count(),
                'payments_processed' => $totalPaymentsProcessed,
                'commissions_created' => $totalCommissionsCreated,
            ]);

            // Send notifications
            $this->sendDeliveryNotifications($delivery);

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

            // Update all orders back to processing for retry
            foreach ($delivery->orders as $order) {
                $order->update([
                    'status' => 'processing',
                    'notes' => ($order->notes ?? '') . "\n\nDelivery failed: {$reason}",
                ]);
            }

            DB::commit();

            Log::info('Delivery failure handled', [
                'delivery_id' => $delivery->id,
                'prescription_id' => $delivery->prescription_id,
                'reason' => $reason,
                'order_count' => $delivery->orders->count(),
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
     * Get delivery progress with order-level details
     */
    public function getDeliveryProgress(Delivery $delivery): array
    {
        $orderDetails = [];
        
        foreach ($delivery->orders as $order) {
            $pivot = $order->pivot;
            
            $orderDetails[] = [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'order_status' => $order->status,
                'supplier' => [
                    'id' => $order->supplier->id,
                    'name' => $order->supplier->company_name,
                    'address' => $order->supplier->address,
                    'phone' => $order->supplier->phone,
                ],
                'pickup_status' => $pivot->pickup_status,
                'picked_up_at' => $pivot->picked_up_at?->toIso8601String(),
                'pickup_notes' => $pivot->pickup_notes,
                'total_amount' => $order->total_amount,
                'items_count' => $order->items->count(),
                'items' => $order->items->map(function ($item) {
                    return [
                        'medicine' => $item->medicine->generic_name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                    ];
                }),
            ];
        }

        return [
            'delivery' => [
                'id' => $delivery->id,
                'number' => $delivery->delivery_number,
                'status' => $delivery->status,
                'prescription_number' => $delivery->prescription->prescription_number,
                'created_at' => $delivery->created_at->toIso8601String(),
                'scheduled_pickup' => $delivery->scheduled_pickup?->toIso8601String(),
                'actual_pickup' => $delivery->actual_pickup?->toIso8601String(),
                'estimated_delivery' => $delivery->estimated_delivery?->toIso8601String(),
                'actual_delivery' => $delivery->actual_delivery?->toIso8601String(),
            ],
            'progress' => [
                'total_orders' => $delivery->orders->count(),
                'confirmed_orders' => $delivery->confirmed_orders_count,
                'pending_orders' => $delivery->pending_orders_count,
                'picked_up_orders' => $delivery->orders->where('pivot.pickup_status', 'picked_up')->count(),
                'all_orders_confirmed' => $delivery->allOrdersConfirmed(),
                'all_orders_picked_up' => $delivery->allOrdersPickedUp(),
            ],
            'orders' => $orderDetails,
            'rider' => $delivery->rider ? [
                'id' => $delivery->rider->id,
                'name' => $delivery->rider->full_name,
                'phone' => $delivery->rider->phone,
                'rating' => $delivery->rider->rating,
            ] : null,
            'patient' => [
                'name' => $delivery->prescription->patient->full_name,
                'address' => $delivery->delivery_address,
                'phone' => $delivery->recipient_phone,
            ],
            'current_location' => $this->trackingService->getCurrentLocation($delivery),
            'eta' => $this->trackingService->calculateETA($delivery),
            'total_amount' => $delivery->total_amount,
            'delivery_fee' => $delivery->delivery_fee,
        ];
    }

    /**
     * Get fulfillment status for prescription
     */
    public function getPrescriptionFulfillmentStatus(int $prescriptionId): array
    {
        $prescription = \App\Models\Prescription::with([
            'delivery.orders.supplier',
            'delivery.rider',
            'patient',
        ])->findOrFail($prescriptionId);

        if (!$prescription->delivery) {
            return [
                'status' => 'no_delivery',
                'message' => 'No delivery created yet. Waiting for order confirmation.',
                'orders' => $prescription->orders->map(fn($o) => [
                    'order_number' => $o->order_number,
                    'status' => $o->status,
                    'supplier' => $o->supplier->company_name,
                ]),
            ];
        }

        return $this->getDeliveryProgress($prescription->delivery);
    }

    protected function sendDeliveryNotifications(Delivery $delivery): void
    {
        // Notify physician about successful delivery and commission
        // Notify patient
        // Notify operations
        Log::info('Delivery notifications queued', [
            'delivery_id' => $delivery->id,
        ]);
    }

    protected function sendFailureNotifications(Delivery $delivery, string $reason): void
    {
        // Notify operations for immediate action
        // Notify physician
        // Notify patient
        Log::info('Failure notifications queued', [
            'delivery_id' => $delivery->id,
            'reason' => $reason,
        ]);
    }
}
