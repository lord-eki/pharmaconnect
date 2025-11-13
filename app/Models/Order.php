<?php

namespace App\Models;

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
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'ordered_at' => 'datetime',
        'expected_delivery' => 'datetime',
        'delivered_at' => 'datetime',
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
                    case 'confirmed':
                        // Check if all prescription orders are confirmed and create insurance claim
                        static::handlePrescriptionOrdersConfirmed($order);
                        break;

                    case 'processing':
                        $order->createDelivery();
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
     * Check if all orders for prescription are confirmed and create insurance claim
     */
    protected static function handlePrescriptionOrdersConfirmed(Order $order): void
    {
        $prescription = $order->prescription;

        if (! $prescription) {
            return;
        }

        $prescription->load('orders');

        // Check if all orders are confirmed or delivered
        $allConfirmed = $prescription->orders()
            ->whereNotIn('status', ['confirmed', 'delivered'])
            ->doesntExist();

        if ($allConfirmed) {
            Log::info('All orders confirmed, creating insurance claim', [
                'prescription_id' => $prescription->id,
            ]);

            try {
                // Only create if insurance is covered and claim doesn't exist
                if ($prescription->insurance_covered && ! $prescription->insuranceClaim) {
                    $prescription->createInsuranceClaim();

                    Log::info('Insurance claim created after order confirmation', [
                        'prescription_id' => $prescription->id,
                        'order_id' => $order->id,
                    ]);
                } elseif ($prescription->insuranceClaim) {
                    Log::info('Insurance claim already exists', [
                        'prescription_id' => $prescription->id,
                        'claim_id' => $prescription->insuranceClaim->id,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error creating insurance claim', [
                    'order_id' => $order->id,
                    'prescription_id' => $prescription->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
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

    // Confirm order (supplier accepts)
    public function confirm(): bool
    {
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

        // Create delivery record
        $this->createDelivery();

        // Notify stakeholders
        $this->notifyStakeholders('shipped');

        return true;
    }

    // Create delivery assignment
    public function createDelivery(): void
    {
        if ($this->delivery) {
            return; // Delivery already exists
        }

        $patient = $this->prescription->patient;

        Delivery::create([
            'delivery_number' => Delivery::generateDeliveryNumber(),
            'order_id' => $this->id,
            'pickup_address' => $this->supplier->address,
            'delivery_address' => $patient->address ?? 'Not specified',
            'delivery_latitude' => null,
            'delivery_longitude' => null,
            'delivery_fee' => $this->calculateDeliveryFee(),
            'status' => 'pending',
            'recipient_name' => $patient->full_name,
            'recipient_phone' => $patient->phone,
        ]);
    }

    // Calculate delivery fee based on distance/location
    protected function calculateDeliveryFee(): float
    {
        // Based on county/city or actual distance calculation

        $patient = $this->prescription->patient;
        $supplier = $this->supplier;

        // Same county = KES 200
        // Different county = KES 500
        if ($patient->county === $supplier->county) {
            return 200.00;
        }

        return 500.00;
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

            // Process payments
            $this->processPayments();

            // Calculate and create commission
            $this->calculateCommission();

            // Update prescription status
            $this->prescription->markFulfilled();

            // Notify stakeholders
            $this->notifyStakeholders('delivered');

            return true;
        });
    }

    // Process payments for the order
    protected function processPayments(): void
    {
        $patient = $this->prescription->patient;
        $physician = $this->prescription->physician;

        // Calculate amounts
        $totalAmount = $this->total_amount;
        $deliveryFee = $this->delivery ? $this->delivery->delivery_fee : 0;
        $grandTotal = $totalAmount + $deliveryFee;

        // Insurance portion (if applicable)
        $insuranceCovered = 0;
        if ($this->prescription->insurance_covered && $this->prescription->insuranceClaim) {
            $claim = $this->prescription->insuranceClaim;
            if ($claim->status === 'approved') {
                $insuranceCovered = $claim->approved_amount;
            }
        }

        // Patient portion
        $patientPortion = $grandTotal - $insuranceCovered;

        // Create payment record for patient
        if ($patientPortion > 0) {
            Payment::create([
                'payment_reference' => Payment::generateReference(),
                'payer_id' => $patient->physician_id, // Physician handles payment collection
                'order_id' => $this->id,
                'prescription_id' => $this->prescription_id,
                'amount' => $patientPortion,
                'currency' => 'KES',
                'payment_method' => 'mpesa', // Default - can be changed
                'status' => 'pending',
            ]);
        }

        // Create payment record for insurance (if applicable)
        if ($insuranceCovered > 0) {
            Payment::create([
                'payment_reference' => Payment::generateReference(),
                'order_id' => $this->id,
                'prescription_id' => $this->prescription_id,
                'amount' => $insuranceCovered,
                'currency' => 'KES',
                'payment_method' => 'insurance',
                'status' => 'processing',
            ]);
        }
    }

    // Calculate and create physician commission
    protected function calculateCommission(): void
    {
        $physician = $this->prescription->physician;

        // Get commission rate (default 10% - can be configured)
        $commissionRate = $this->getCommissionRate();

        $grossAmount = $this->total_amount;
        $commissionAmount = $grossAmount * ($commissionRate / 100);

        Commission::create([
            'physician_id' => $physician->id,
            'prescription_id' => $this->prescription_id,
            'order_id' => $this->id,
            'commission_rate' => $commissionRate,
            'gross_amount' => $grossAmount,
            'commission_amount' => $commissionAmount,
            'status' => 'pending',
        ]);
    }

    // Get commission rate for physician
    protected function getCommissionRate(): float
    {
        // Make this dynamic based on:
        // - Physician tier/level
        // - Order volume
        // - Medicine category
        // For now, return fixed rate

        return 10.0; // 10%
    }

    // Send notifications to stakeholders
    protected function notifyStakeholders(string $event): void
    {
        // Implement notification logic
        // - Email to physician
        // - SMS to patient
        // - System notification to operations
        // - Update to supplier
    }

    // Cancel order
    public function cancel(string $reason): bool
    {
        if (! in_array($this->status, ['pending', 'confirmed'])) {
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
        return $query->where('supplier_id', $supplierId);
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
            'pending' => 'warning',
            'confirmed' => 'info',
            'processing' => 'info',
            'shipped' => 'primary',
            'delivered' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }
}
