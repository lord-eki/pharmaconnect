<?php

namespace App\Models;

use App\Services\CommissionService;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'quotation_id',
        'supplier_id',
        'prescription_id',
        'total_amount',
        'markup_total',
        'supplier_total',
        'status',
        'ordered_at',
        'expected_delivery',
        'delivered_at',
        'sent_to_supplier_at', 
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'ordered_at' => 'datetime',
        'expected_delivery' => 'datetime',
        'delivered_at' => 'datetime',
        'sent_to_supplier_at' => 'datetime', 
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (! $order->order_number) {
                $order->order_number = static::generateOrderNumber();
            }

            if (! $order->ordered_at) {
                $order->ordered_at = now();
            }

            // Set expected delivery (default 24 hours)
            if (! $order->expected_delivery) {
                $order->expected_delivery = now()->addHours(24);
            }
            
            if (! $order->status) {
                $order->status = 'pending_review';
            }
        });

        static::saved(function ($order) {
            if ($order->status === 'delivered' && $order->prescription) {
                $order->prescription->markFulfilled();
            }
        });

        static::updated(function ($order) {
            // Only handle status changes
            if ($order->isDirty('status')) {
                switch ($order->status) {
                    case 'sent_to_supplier':
                        if (!$order->sent_to_supplier_at) {
                            $order->updateQuietly(['sent_to_supplier_at' => now()]);
                        }
                        // Notify supplier
                        $order->notifyStakeholders('sent_to_supplier');
                        break;
                        
                    case 'confirmed':
                        // Check if all prescription orders are confirmed and create insurance claim
                        static::handlePrescriptionOrdersConfirmed($order);
                        
                        // Create delivery when order is confirmed
                        if (!$order->delivery) {
                            $order->createDelivery();
                            Log::info('Delivery created during order confirmation', [
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                            ]);
                        }
                        break;

                    case 'processing':
                        // Ensure delivery exists when processing starts
                        if (!$order->delivery) {
                            $order->createDelivery();
                            Log::warning('Delivery created during processing - should have been created at confirmation', [
                                'order_id' => $order->id,
                            ]);
                        }
                        break;

                    case 'delivered':
                        // Update prescription status
                        if ($order->prescription) {
                            $order->prescription->markFulfilled();
                        }
                        break;
                }
            }
        });
    }

    /**
     * Send order to supplier for processing
     */
    public function sendToSupplier(?string $notes = null): bool
    {
        if ($this->status !== 'pending_review') {
            throw new \Exception('Order must be in pending_review status to send to supplier');
        }

        $this->status = 'sent_to_supplier';
        $this->sent_to_supplier_at = now();
        
        if ($notes) {
            $this->notes = ($this->notes ? $this->notes . "\n\n" : '') . "Sent to supplier: " . $notes;
        }
        
        $saved = $this->save();
        
        if ($saved) {
            Log::info('Order sent to supplier', [
                'order_id' => $this->id,
                'order_number' => $this->order_number,
                'supplier_id' => $this->supplier_id,
            ]);
        }
        
        return $saved;
    }

    /**
     * Check if all orders for prescription are confirmed and create insurance claim
     */
    protected static function handlePrescriptionOrdersConfirmed(Order $order): void
    {
        $prescription = $order->prescription;

        if (! $prescription) {
            return;
        }

        // Reload prescription with patient insurance info
        $prescription->load(['orders', 'patient.insuranceProvider']);

        // Check if all orders are confirmed or delivered
        $allConfirmed = $prescription->orders()
            ->whereNotIn('status', ['confirmed', 'delivered'])
            ->doesntExist();

        if ($allConfirmed) {
            Log::info('All orders confirmed for prescription', [
                'prescription_id' => $prescription->id,
                'prescription_number' => $prescription->prescription_number,
                'insurance_covered' => $prescription->insurance_covered,
            ]);

            // Create insurance claim if needed
            if ($prescription->insurance_covered) {
                try {
                    // Check if patient has insurance setup
                    $patient = $prescription->patient;

                    if (! $patient->insurance_provider_id) {
                        Log::error('Cannot create insurance claim - patient has no insurance provider', [
                            'prescription_id' => $prescription->id,
                            'patient_id' => $patient->id,
                        ]);

                        return;
                    }

                    if (! $patient->insurance_number) {
                        Log::error('Cannot create insurance claim - patient has no insurance number', [
                            'prescription_id' => $prescription->id,
                            'patient_id' => $patient->id,
                        ]);

                        return;
                    }

                    // Check if claim already exists
                    if ($prescription->insuranceClaim) {
                        Log::info('Insurance claim already exists', [
                            'prescription_id' => $prescription->id,
                            'claim_id' => $prescription->insuranceClaim->id,
                            'claim_number' => $prescription->insuranceClaim->claim_number,
                        ]);

                        // Verify claim has provider_id, fix if missing
                        if (! $prescription->insuranceClaim->insurance_provider_id) {
                            $prescription->insuranceClaim->update([
                                'insurance_provider_id' => $patient->insurance_provider_id,
                            ]);

                            Log::info('Fixed missing insurance_provider_id on existing claim', [
                                'claim_id' => $prescription->insuranceClaim->id,
                                'provider_id' => $patient->insurance_provider_id,
                            ]);
                        }
                    } else {
                        // Create new insurance claim
                        $claim = $prescription->createInsuranceClaim();

                        Log::info('Insurance claim created after all orders confirmed', [
                            'prescription_id' => $prescription->id,
                            'claim_id' => $claim->id,
                            'claim_number' => $claim->claim_number,
                            'insurance_provider_id' => $claim->insurance_provider_id,
                            'claimed_amount' => $claim->claimed_amount,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error creating/updating insurance claim', [
                        'order_id' => $order->id,
                        'prescription_id' => $prescription->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            } else {
                Log::info('Prescription not marked for insurance coverage', [
                    'prescription_id' => $prescription->id,
                ]);
            }
        }
    }

    public function payables(): HasMany
    {
        return $this->hasMany(Payable::class);
    }

    public function receivables(): HasMany
    {
        return $this->hasMany(Receivable::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function commission(): HasOne
    {
        return $this->hasOne(Commission::class);
    }


    /**
     * Check if order is eligible for invoice generation
     */
    public function isEligibleForInvoice(): bool
    {
        // Must be delivered
        if ($this->status !== 'delivered') {
            return false;
        }

        // Must have a prescription with patient
        if (!$this->prescription || !$this->prescription->patient) {
            return false;
        }

        // Patient must have insurance
        if (!$this->prescription->patient->insurance_provider_id) {
            return false;
        }
        

        // Must not already have an invoice
        if ($this->invoices()->exists()) {
            return false;
        }

        return true;
    }

    // Generate unique order number (LPO)
    public static function generateOrderNumber(): string
    {
        $prefix = 'LPO';
        $year = date('Y');
        $month = date('m');
        $ym = $year.$month;

        // Get the last order for this year and month
        $lastOrder = static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;

        if ($lastOrder && preg_match('/(\d{5})$/', $lastOrder->order_number, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        // Pad the sequence to 5 digits
        $sequencePadded = str_pad($sequence, 5, '0', STR_PAD_LEFT);

        return sprintf('%s%s-%s', $prefix, $ym, $sequencePadded);
    }

    // Confirm order 
    public function confirm(): bool
    {
        //  Must be sent to supplier first
        if (!in_array($this->status, ['sent_to_supplier', 'pending'])) {
            throw new \Exception('Order must be sent to supplier before confirmation');
        }

        return DB::transaction(function () {
            $this->status = 'confirmed';
            $this->save();

            // Update stock quantities
            foreach ($this->items as $item) {
                $supplierMedicine = $item->medicine->supplierMedicines()
                    ->where('supplier_id', $this->supplier_id)
                    ->first();

                if ($supplierMedicine) {
                    $supplierMedicine->decrement('stock_quantity', $item->quantity);
                }
            }

            // Create delivery when order is confirmed
            if (!$this->delivery) {
                $this->createDelivery();
                Log::info('Delivery created during order confirmation', [
                    'order_id' => $this->id,
                    'order_number' => $this->order_number,
                ]);
            }

            // Notify physician and operations
            $this->notifyStakeholders('confirmed');

            return true;
        });
    }

    // Mark as shipped
    public function ship(): bool
    {
        if ($this->status !== 'confirmed') {
            throw new \Exception('Order must be confirmed before shipping');
        }

        $this->status = 'shipped';
        $this->save();

        if (!$this->delivery) {
            $this->createDelivery();
            Log::warning('Delivery created during shipping - should have existed from confirmation', [
                'order_id' => $this->id,
            ]);
        }

        // Notify stakeholders
        $this->notifyStakeholders('shipped');

        return true;
    }

    // Create delivery assignment
    public function createDelivery(): void
    {
        if ($this->delivery) {
            Log::info('Delivery already exists for order', [
                'order_id' => $this->id,
                'delivery_id' => $this->delivery->id,
            ]);
            return;
        }

        $patient = $this->prescription->patient;
        $supplier = $this->supplier;

        $delivery = Delivery::create([
            'delivery_number' => Delivery::generateDeliveryNumber(),
            'order_id' => $this->id,
            'pickup_address' => $supplier->address ?? 'Supplier address not set',
            'delivery_address' => $patient->address ?? "{$patient->city}, {$patient->county}",
            'delivery_latitude' => null,
            'delivery_longitude' => null,
            'estimated_distance_km' => $this->estimateDistance(),
            'delivery_fee' => $this->calculateDeliveryFee(),
            'status' => 'pending',
            'recipient_name' => $patient->full_name,
            'recipient_phone' => $patient->phone,
            'scheduled_pickup' => now()->addHours(2),
            'estimated_delivery' => now()->addHours(4),
        ]);

        Log::info('Delivery created for order', [
            'order_id' => $this->id,
            'delivery_id' => $delivery->id,
            'delivery_number' => $delivery->delivery_number,
            'status' => 'pending',
        ]);
    }

    // Calculate delivery fee based on distance/location
    protected function calculateDeliveryFee(): float
    {
        $patient = $this->prescription->patient;
        $supplier = $this->supplier;

        if ($patient->county === $supplier->county) {
            return 200.00;
        }

        return 500.00;
    }

    // Estimate distance
    protected function estimateDistance(): float
    {
        $patient = $this->prescription->patient;
        $supplier = $this->supplier;

        if ($patient->county === $supplier->county) {
            return 10.0;
        }

        return 50.0;
    }

    // Mark as delivered
    public function markDelivered(array $deliveryData = []): bool
    {
        return DB::transaction(function () use ($deliveryData) {
            $this->status = 'delivered';
            $this->delivered_at = now();
            $this->save();

            // Update delivery record
            if ($this->delivery) {
                $this->delivery->update([
                    'status' => 'delivered',
                    'actual_delivery' => now(),
                    'proof_of_delivery' => $deliveryData['proof'] ?? null,
                ]);
            }

            $prescription = $this->prescription;

            // Only attempt insurance claim if insurance is covered AND patient has complete info
            if ($prescription->insurance_covered && !$prescription->insuranceClaim) {
                $patient = $prescription->patient;

                if ($patient->insurance_provider_id && $patient->insurance_number) {
                    try {
                        Log::info('Creating insurance claim during delivery', [
                            'order_id' => $this->id,
                            'prescription_id' => $prescription->id,
                        ]);

                        $prescription->createInsuranceClaim();
                        $prescription->load('insuranceClaim');

                    } catch (\Exception $e) {
                        Log::error('Failed to create insurance claim during delivery', [
                            'order_id' => $this->id,
                            'prescription_id' => $prescription->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } else {
                    Log::info('Skipping insurance claim - patient has incomplete insurance info', [
                        'order_id' => $this->id,
                        'prescription_id' => $prescription->id,
                        'patient_id' => $patient->id,
                        'has_provider' => !empty($patient->insurance_provider_id),
                        'has_number' => !empty($patient->insurance_number),
                    ]);
                }
            }

            // Process payments
            $paymentService = app(PaymentService::class);
            try {
                $paymentService->processOrderPayments($this);
            } catch (\Exception $e) {
                Log::error('Failed to process order payments', [
                    'order_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Calculate commission
            $commissionService = app(CommissionService::class);
            try {
                $commissionService->calculateCommissionForOrder($this);
            } catch (\Exception $e) {
                Log::error('Failed to calculate commission', [
                    'order_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Update prescription status
            $this->prescription->markFulfilled();

            // Notify stakeholders
            $this->notifyStakeholders('delivered');

            return true;
        });
    }

    // Send notifications to stakeholders
    protected function notifyStakeholders(string $event): void
    {
        if ($event === 'sent_to_supplier') {
            // Send notification to supplier
            if ($this->supplier && $this->supplier->user) {
                try {
                    $this->supplier->user->notify(new \App\Notifications\NewOrderNotification($this));
                    Log::info('Supplier notified of new order', [
                        'order_id' => $this->id,
                        'supplier_id' => $this->supplier_id,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to notify supplier', [
                        'order_id' => $this->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
        
    }

    // Cancel order
    public function cancel(string $reason): bool
    {
        if (! in_array($this->status, ['pending_review', 'sent_to_supplier', 'confirmed'])) {
            throw new \Exception('Cannot cancel order in current status');
        }

        return DB::transaction(function () use ($reason) {
            $this->status = 'cancelled';
            $this->notes = ($this->notes ? $this->notes."\n\n" : '').'Cancelled: '.$reason;
            $this->save();

            // Restore stock quantities if order was confirmed
            if ($this->wasChanged('status') && $this->getOriginal('status') === 'confirmed') {
                foreach ($this->items as $item) {
                    $supplierMedicine = $item->medicine->supplierMedicines()
                        ->where('supplier_id', $this->supplier_id)
                        ->first();

                    if ($supplierMedicine) {
                        $supplierMedicine->increment('stock_quantity', $item->quantity);
                    }
                }
            }

            // Cancel delivery if exists
            if ($this->delivery) {
                $this->delivery->update(['status' => 'cancelled']);
            }

            // Notify stakeholders
            $this->notifyStakeholders('cancelled');

            return true;
        });
    }

    // Scopes
    public function scopePendingReview($query)
    {
        return $query->where('status', 'pending_review');
    }
    
    public function scopeSentToSupplier($query)
    {
        return $query->where('status', 'sent_to_supplier');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['confirmed', 'processing', 'shipped']);
    }

    public function scopeForSupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId)
            ->whereIn('status', ['sent_to_supplier', 'confirmed', 'processing', 'shipped', 'delivered']);
    }

    // Accessors
    public function getIsOverdueAttribute(): bool
    {
        if ($this->status === 'delivered' || ! $this->expected_delivery) {
            return false;
        }

        return $this->expected_delivery->isPast();
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending_review' => 'warning',
            'sent_to_supplier' => 'info',
            'pending' => 'warning',
            'confirmed' => 'info',
            'processing' => 'info',
            'shipped' => 'primary',
            'delivered' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }
    
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending_review' => 'Pending Review',
            'sent_to_supplier' => 'Sent to Supplier',
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            default => 'Unknown',
        };
    }
}