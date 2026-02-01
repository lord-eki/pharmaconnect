<?php

namespace App\Models;

use App\Mail\NewOrderNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ExternalOrder extends Model
{
     protected $fillable = [
        'order_number',
        'insurance_provider_id',
        'created_by_user_id',
        'recipient_name',
        'recipient_phone',
        'recipient_email',
        'delivery_address',
        'delivery_county',
        'delivery_city',
        'delivery_latitude',
        'delivery_longitude',
        'total_amount',
        'status',
        'notes',
        'reference_number',
        'ordered_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'ordered_at' => 'datetime',
        'delivery_latitude' => 'decimal:8',
        'delivery_longitude' => 'decimal:8',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($externalOrder) {
            if (!$externalOrder->order_number) {
                $externalOrder->order_number = static::generateOrderNumber();
            }

            if (!$externalOrder->ordered_at) {
                $externalOrder->ordered_at = now();
            }

            if (!$externalOrder->status) {
                $externalOrder->status = 'draft';
            }

            if (!$externalOrder->created_by_user_id) {
                $externalOrder->created_by_user_id = auth()->id();
            }
        });
    }

    /**
     * Relationships
     */
    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExternalOrderItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'external_order_id');
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class, 'external_order_id');
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'EXT';
        $year = date('Y');
        $month = date('m');

        $cacheKey = "last_external_order_{$year}_{$month}";
        $lockKey = "lock_{$cacheKey}";

        return Cache::lock($lockKey, 5)->block(5, function () use ($cacheKey, $prefix, $year, $month) {
            $lastSequence = Cache::get($cacheKey, 0);

            if ($lastSequence === 0) {
                $lastOrder = static::select('order_number')
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($lastOrder && preg_match('/(\d{5})$/', $lastOrder->order_number, $matches)) {
                    $lastSequence = intval($matches[0]);
                }
            }

            $sequence = $lastSequence + 1;
            Cache::put($cacheKey, $sequence, 3600);

            return sprintf('%s%s%s%05d', $prefix, $year, $month, $sequence);
        });
    }

    /**
     * Submit external order and create supplier orders
     */
    public function submit(): bool
    {
        return DB::transaction(function () {
            if ($this->items->isEmpty()) {
                throw new \Exception('Cannot submit order without medicines');
            }

            // Update status to submitted
            $this->status = 'submitted';
            $this->save();

            try {
                // Create supplier orders from items
                $this->createSupplierOrders();

                Log::info('External order submitted successfully', [
                    'external_order_id' => $this->id,
                    'order_number' => $this->order_number,
                    'orders_count' => $this->orders()->count(),
                ]);

            } catch (\Exception $e) {
                Log::error('Error creating supplier orders', [
                    'external_order_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);

                // Revert to draft status if order creation fails
                $this->status = 'draft';
                $this->save();

                throw $e;
            }

            return true;
        });
    }

    /**
     * Create supplier orders from external order items
     * Similar to prescription flow but without prescription
     */
    protected function createSupplierOrders(): void
    {
        // Group items by supplier
        $itemsBySupplier = [];
        
        foreach ($this->items as $item) {
            $supplierMedicine = DB::table('supplier_medicines')
                ->where('medicine_id', $item->medicine_id)
                ->where('is_available', true)
                ->where('stock_quantity', '>=', $item->quantity)
                ->orderBy('unit_price', 'asc')
                ->first();

            if (!$supplierMedicine) {
                throw new \Exception("No available supplier found for medicine ID: {$item->medicine_id}");
            }

            $supplierId = $supplierMedicine->supplier_id;
            
            if (!isset($itemsBySupplier[$supplierId])) {
                $itemsBySupplier[$supplierId] = [];
            }
            
            $itemsBySupplier[$supplierId][] = [
                'item' => $item,
                'supplier_medicine' => $supplierMedicine,
            ];
        }

        // Create an order for each supplier
        $createdOrders = [];
        foreach ($itemsBySupplier as $supplierId => $supplierItems) {
            // Calculate totals before creating the order
            $orderTotal = 0;
            $supplierTotal = 0;

            foreach ($supplierItems as $itemData) {
                $item = $itemData['item'];
                $supplierMedicine = $itemData['supplier_medicine'];
                
                $orderTotal += $item->total_price;
                $supplierTotal += ($supplierMedicine->unit_price * $item->quantity);
            }

            // Create order with all required fields
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'supplier_id' => $supplierId,
                'external_order_id' => $this->id,
                'quotation_id' => null, // External orders don't have quotations
                'prescription_id' => null, // External orders don't have prescriptions
                'status' => 'pending_review',
                'ordered_at' => now(),
                'expected_delivery' => now()->addHours(24),
                'total_amount' => $orderTotal,
                'supplier_total' => $supplierTotal,
                'markup_total' => $orderTotal - $supplierTotal,
            ]);

            // Create order items
            foreach ($supplierItems as $itemData) {
                $item = $itemData['item'];
                $supplierMedicine = $itemData['supplier_medicine'];

                OrderItem::create([
                    'order_id' => $order->id,
                    'medicine_id' => $item->medicine_id,
                    'supplier_medicine_id' => $supplierMedicine->id,
                    'quotation_item_id' => null, // External orders don't have quotation items
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'supplier_price' => $supplierMedicine->unit_price,
                    'total_price' => $item->total_price,
                    'supplier_total' => $supplierMedicine->unit_price * $item->quantity,
                ]);
            }

            $createdOrders[] = $order;

            // Send order to supplier
            $order->sendToSupplier();

            Log::info('Supplier order created from external order', [
                'external_order_id' => $this->id,
                'order_id' => $order->id,
                'supplier_id' => $supplierId,
                'items_count' => count($supplierItems),
                'total_amount' => $orderTotal,
            ]);
        }

        // Notify stakeholders
        dispatch(function () use ($createdOrders) {
            foreach ($createdOrders as $order) {
                $this->notifySupplier($order);
            }
        })->afterResponse();
    }

    /**
     * Create consolidated delivery for external order
     * Called when first supplier order is confirmed
     */
    public function createConsolidatedDelivery($orders): Delivery
    {
        if ($orders instanceof \Illuminate\Support\Collection) {
            if ($orders->isEmpty()) {
                throw new \Exception('Cannot create delivery without orders');
            }
        } elseif (is_array($orders)) {
            if (empty($orders)) {
                throw new \Exception('Cannot create delivery without orders');
            }
            $orders = collect($orders);
        } else {
            throw new \Exception('Orders must be a Collection or array');
        }

        // Collect all supplier pickup locations
        $pickupLocations = [];
        foreach ($orders as $order) {
            if (!$order->relationLoaded('supplier')) {
                $order->load('supplier');
            }
            $supplier = $order->supplier;

            $pickupLocations[] = [
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->company_name,
                'address' => $supplier->address ?? 'Address not set',
                'county' => $supplier->county,
                'city' => $supplier->city,
                'phone' => $supplier->phone,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ];
        }

        // Calculate delivery fee
        $deliveryFee = $this->calculateDeliveryFee($pickupLocations);

        $delivery = Delivery::create([
            'delivery_number' => Delivery::generateDeliveryNumber(),
            'external_order_id' => $this->id,
            'pickup_locations' => $pickupLocations,
            'pickup_address' => $this->getPrimaryPickupAddress($pickupLocations),
            'delivery_address' => $this->delivery_address,
            'delivery_latitude' => $this->delivery_latitude,
            'delivery_longitude' => $this->delivery_longitude,
            'estimated_distance_km' => 10, 
            'delivery_fee' => $deliveryFee,
            'status' => 'pending',
            'recipient_name' => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'scheduled_pickup' => now()->addHours(2),
            'estimated_delivery' => now()->addHours(6),
        ]);

        // Attach orders to delivery
        foreach ($orders as $order) {
            $delivery->orders()->attach($order->id, [
                'pickup_status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Log::info('Delivery created for external order', [
            'external_order_id' => $this->id,
            'delivery_id' => $delivery->id,
            'orders_count' => $orders->count(),
        ]);

        return $delivery;
    }

    /**
     * Get primary pickup address 
     */
    protected function getPrimaryPickupAddress(array $pickupLocations): string
    {
        if (empty($pickupLocations)) {
            return 'N/A';
        }

        $first = $pickupLocations[0];
        return "{$first['address']}, {$first['city']}, {$first['county']}";
    }

    /**
     * Calculate delivery fee based on distance and pickup points
     */
    protected function calculateDeliveryFee(array $pickupLocations): float
    {
        $baseDeliveryFee = 200.00; 
        $perPickupFee = 50.00; 

        $numberOfPickups = count($pickupLocations);
        $totalFee = $baseDeliveryFee + (($numberOfPickups - 1) * $perPickupFee);

        return $totalFee;
    }

    /**
     * Notify supplier about new order
     */
    protected function notifySupplier(Order $order): void
    {
        try {
            $supplier = $order->supplier;
            
            if (!$supplier) {
                Log::warning('Cannot notify supplier - supplier not found', [
                    'order_id' => $order->id,
                ]);
                return;
            }

            if ($supplier->user && $supplier->user->email) {
                try {
                    Mail::to($supplier->user->email)->queue(
                        new NewOrderNotification($order)
                    );

                    Log::info('Supplier email queued for external order', [
                        'order_id' => $order->id,
                        'supplier_id' => $supplier->id,
                        'email' => $supplier->user->email,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to queue email to supplier', [
                        'order_id' => $order->id,
                        'supplier_id' => $supplier->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to notify supplier', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update total amount from items
     */
    public function updateTotalAmount(): void
    {
        $total = $this->items()->sum('total_price');

        if ($this->total_amount != $total) {
            $this->updateQuietly(['total_amount' => $total]);

            Log::info('External order total updated', [
                'external_order_id' => $this->id,
                'order_number' => $this->order_number,
                'total_amount' => $total,
                'item_count' => $this->items()->count(),
            ]);
        }
    }

    /**
     * Cancel external order
     */
    public function cancel(?string $reason = null): bool
    {
        if (!in_array($this->status, ['draft', 'submitted', 'processing'])) {
            throw new \Exception('Cannot cancel order in current status');
        }

        return DB::transaction(function () use ($reason) {
            $this->status = 'cancelled';
            $this->notes = ($this->notes ? $this->notes . "\n\n" : '') . 'Cancelled: ' . $reason;
            $this->save();

            // Cancel all related supplier orders
            Order::where('external_order_id', $this->id)
                ->whereIn('status', ['pending_review', 'sent_to_supplier', 'confirmed'])
                ->each(function ($order) use ($reason) {
                    $order->cancel($reason);
                });

            return true;
        });
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['submitted', 'processing']);
    }

    public function scopeForInsuranceProvider($query, $providerId)
    {
        return $query->where('insurance_provider_id', $providerId);
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'processing' => 'Processing',
            'fulfilled' => 'Fulfilled',
            'cancelled' => 'Cancelled',
            default => 'Unknown',
        };
    }
}
