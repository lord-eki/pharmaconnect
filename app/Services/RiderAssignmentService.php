<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\Order;
use App\Models\Rider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RiderAssignmentService
{
    /**
     * Assign rider to prescription-level delivery
     */
    public function assignRider(Delivery $delivery): ?Rider
    {
        try {
            // Don't assign if not ready
            if ($delivery->status !== 'ready_for_pickup') {
                Log::warning('Cannot assign rider - delivery not ready', [
                    'delivery_id' => $delivery->id,
                    'current_status' => $delivery->status,
                    'confirmed_orders' => $delivery->confirmed_orders_count,
                    'pending_orders' => $delivery->pending_orders_count,
                ]);
                return null;
            }

            $patient = $delivery->prescription->patient;
            $deliveryCounty = $patient->county;
            $deliveryCity = $patient->city;

            // Find available rider in same location
            $rider = Rider::active()
                ->available()
                ->where(function ($query) use ($deliveryCounty, $deliveryCity) {
                    $query->where('base_county', $deliveryCounty)
                        ->orWhere('base_city', $deliveryCity);
                })
                ->orderBy('rating', 'desc')
                ->orderBy('total_deliveries', 'asc')
                ->first();

            // Fallback: any available rider
            if (!$rider) {
                $rider = Rider::active()
                    ->available()
                    ->orderBy('rating', 'desc')
                    ->first();
            }

            if ($rider) {
                $delivery->update([
                    'rider_id' => $rider->id,
                    'status' => 'assigned',
                    'scheduled_pickup' => now()->addHours(1),
                ]);

                $rider->update(['is_available' => false]);

                Log::info('Rider assigned to prescription delivery', [
                    'delivery_id' => $delivery->id,
                    'prescription_id' => $delivery->prescription_id,
                    'rider_id' => $rider->id,
                    'order_count' => $delivery->orders->count(),
                    'pickup_locations' => count($delivery->pickup_locations ?? []),
                ]);

                return $rider;
            }

            Log::warning('No available riders found', [
                'delivery_id' => $delivery->id,
                'county' => $deliveryCounty,
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Error assigning rider', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Create delivery record when order is confirmed
     */
    public function createDeliveryForOrder(Order $order): Delivery
    {
        $patient = $order->prescription->patient;
        $supplier = $order->supplier;

        // Generate delivery number
        $deliveryNumber = $this->generateDeliveryNumber();

        // Calculate delivery fee based on distance/location
        $deliveryFee = $this->calculateDeliveryFee($supplier, $patient);

        $delivery = Delivery::create([
            'delivery_number' => $deliveryNumber,
            'order_id' => $order->id,
            'pickup_address' => $supplier->address,
            'delivery_address' => $patient->address ?? "{$patient->city}, {$patient->county}",
            'pickup_latitude' => null, 
            'pickup_longitude' => null,
            'delivery_latitude' => null,
            'delivery_longitude' => null,
            'estimated_distance_km' => $this->estimateDistance($supplier, $patient),
            'delivery_fee' => $deliveryFee,
            'status' => 'pending',
            'recipient_name' => $patient->full_name,
            'recipient_phone' => $patient->phone,
        ]);


        return $delivery;
    }

    /**
     * Generate unique delivery number
     */
    protected function generateDeliveryNumber(): string
    {
        $prefix = 'DEL';
        $year = date('Y');
        $month = date('m');
        $ym = $year . $month;

        $lastDelivery = Delivery::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;

        if ($lastDelivery && preg_match('/(\d{5})$/', $lastDelivery->delivery_number, $matches)) {
            $sequence = (int)$matches[1] + 1;
        }

        $sequencePadded = str_pad($sequence, 5, '0', STR_PAD_LEFT);

        return sprintf('%s%s-%s', $prefix, $ym, $sequencePadded);
    }

    /**
     * Calculate delivery fee based on location
     */
    protected function calculateDeliveryFee($supplier, $patient): float
    {
        // Same county = KES 200
        // Different county in same region = KES 500
        // Different region = KES 800

        if ($supplier->county === $patient->county) {
            return 200.00;
        }

        if ($this->isSameRegion($supplier->county, $patient->county)) {
            return 500.00;
        }

        return 800.00;
    }

    /**
     * Estimate distance between supplier and patient
     */
    protected function estimateDistance($supplier, $patient): float
    {
        // Simple estimation based on county
        // Can be enhanced with actual geocoding
        if ($supplier->county === $patient->county) {
            return 10.0; // 10km within same county
        }

        return 50.0; // 50km different counties
    }

    /**
     * Check if counties are in same region
     */
    protected function isSameRegion(string $county1, string $county2): bool
    {
        // Define regions
        $regions = [
            'central' => ['Nairobi', 'Kiambu', 'Murang\'a', 'Nyeri', 'Kirinyaga'],
            'coast' => ['Mombasa', 'Kilifi', 'Kwale', 'Lamu', 'Taita Taveta'],
            'eastern' => ['Machakos', 'Kitui', 'Makueni', 'Embu', 'Tharaka Nithi'],
            // Add more regions as needed
        ];

        foreach ($regions as $region => $counties) {
            if (in_array($county1, $counties) && in_array($county2, $counties)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update delivery status with order-level tracking
     */
    public function updateDeliveryStatus(Delivery $delivery, string $status, array $data = []): bool
    {
        try {
            DB::beginTransaction();

            $updates = ['status' => $status];

            switch ($status) {
                case 'picked_up':
                    // This is called when ALL orders are picked up
                    $updates['actual_pickup'] = now();
                    break;

                case 'in_transit':
                    if (!$delivery->estimated_delivery) {
                        $updates['estimated_delivery'] = now()->addHours(2);
                    }
                    break;

                case 'delivered':
                    $updates['actual_delivery'] = now();
                    $updates['proof_of_delivery'] = $data['proof_of_delivery'] ?? null;
                    
                    if ($delivery->rider) {
                        $delivery->rider->update(['is_available' => true]);
                        $delivery->rider->incrementDeliveries();
                    }

                    // Mark ALL orders as delivered
                    foreach ($delivery->orders as $order) {
                        $order->update([
                            'status' => 'delivered',
                            'delivered_at' => now(),
                        ]);
                    }
                    break;

                case 'failed':
                    $updates['delivery_notes'] = $data['failure_reason'] ?? 'Delivery failed';
                    
                    if ($delivery->rider) {
                        $delivery->rider->update(['is_available' => true]);
                    }
                    break;
            }

            $delivery->update($updates);

            DB::commit();

            Log::info('Prescription delivery status updated', [
                'delivery_id' => $delivery->id,
                'prescription_id' => $delivery->prescription_id,
                'status' => $status,
                'order_count' => $delivery->orders->count(),
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating delivery status', [
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
            } else {
                // Auto-assignment
                $rider = $this->assignRider($delivery);
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

}