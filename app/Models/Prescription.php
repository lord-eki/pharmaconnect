<?php

namespace App\Models;

use App\Mail\InsuranceClaimFormMail;
use App\Mail\NewOrderNotification;
use App\Services\PricingService;
use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Prescription extends Model
{
    use HasAuditLog, HasFactory;

    protected bool $isUpdatingTotal = false;

    protected $fillable = [
        'prescription_number',
        'physician_id',
        'patient_id',
        'diagnosis',
        'notes',
        'status',
        'total_amount',
        'insurance_covered',
        'insurance_claim_id',
        'prescribed_at',
        'expires_at',
        'fulfilled_at',
    ];

    protected $casts = [
        'insurance_covered' => 'boolean',
        'prescribed_at' => 'datetime',
        'expires_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    protected $with = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($prescription) {
            if (! $prescription->physician_id) {
                $prescription->physician_id = auth()->id();
            }

            if (! $prescription->prescription_number) {
                $prescription->prescription_number = static::generatePrescriptionNumber();
            }

            if (! $prescription->prescribed_at) {
                $prescription->prescribed_at = now();
            }

            if (! $prescription->status) {
                $prescription->status = 'draft';
            }
        });
    }

    public function physician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'physician_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function insuranceClaim(): BelongsTo
    {
        return $this->belongsTo(InsuranceClaim::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    /**
     * OPTIMIZED: Generate prescription number with better caching
     */
    public static function generatePrescriptionNumber(): string
    {
        $prefix = 'RX';
        $year = date('Y');
        $month = date('m');

        $cacheKey = "last_prescription_{$year}_{$month}";
        $lockKey = "lock_{$cacheKey}";

        // Use cache lock to prevent race conditions
        return Cache::lock($lockKey, 5)->block(5, function () use ($cacheKey, $prefix, $year, $month) {
            $lastSequence = Cache::get($cacheKey, 0);

            if ($lastSequence === 0) {
                // Only query DB if cache is empty
                $lastPrescription = static::select('prescription_number')
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($lastPrescription && preg_match('/(\d{5})$/', $lastPrescription->prescription_number, $matches)) {
                    $lastSequence = intval($matches[0]);
                }
            }

            $sequence = $lastSequence + 1;
            Cache::put($cacheKey, $sequence, 3600); // Cache for 1 hour

            return sprintf('%s%s%s%05d', $prefix, $year, $month, $sequence);
        });
    }

    /**
     * Submit with  error handling and performance
     */
    public function submit(): bool
    {
        return DB::transaction(function () {
            if ($this->items->isEmpty()) {
                throw new \Exception('Cannot submit prescription without medicines');
            }

            // Check interactions asynchronously after submission
            dispatch(function () {
                $this->checkDrugInteractions();
            })->afterResponse();

            $this->status = 'submitted';
            $this->save();

            try {
                // Generate quotation
                $quotation = $this->generateQuotationSync();

                if ($quotation && $quotation->items->isNotEmpty()) {
                    // Create orders from quotation
                    $this->createOrdersFromQuotation($quotation);
                } else {
                    Log::warning('No quotation items generated', [
                        'prescription_id' => $this->id,
                    ]);
                    $this->status = 'pending';
                    $this->save();
                }
            } catch (\Exception $e) {
                Log::error('Error creating orders', [
                    'prescription_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);

                $this->status = 'pending';
                $this->save();

                throw $e;
            }

            // Notify stakeholders asynchronously
            dispatch(function () {
                $this->notifyStakeholders();
            })->afterResponse();

            return true;
        });
    }

    /**
     * OPTIMIZED: Generate quotation with bulk operations
     */
    protected function generateQuotationSync(): ?Quotation
    {
        $quotation = Quotation::create([
            'quotation_number' => Quotation::generateQuotationNumber(),
            'prescription_id' => $this->id,
            'total_amount' => 0,
            'status' => 'pending',
            'valid_until' => now()->addHours(24),
        ]);

        // OPTIMIZED: Single query to get all relevant supplier medicines
        $medicineIds = $this->items->pluck('medicine_id')->toArray();

        $supplierMedicines = DB::table('supplier_medicines')
            ->whereIn('medicine_id', $medicineIds)
            ->where('is_available', true)
            ->where('stock_quantity', '>', 0)
            ->select([
                'id',
                'medicine_id',
                'supplier_id',
                'unit_price',
                'stock_quantity',
            ])
            ->get()
            ->groupBy('medicine_id');

        $quotationItems = [];
        $hasItems = false;

        foreach ($this->items as $item) {
            $availableSuppliers = $supplierMedicines->get($item->medicine_id, collect());

            foreach ($availableSuppliers as $supplierMedicine) {
                if ($supplierMedicine->stock_quantity >= $item->quantity) {
                    $quotationItems[] = [
                        'quotation_id' => $quotation->id,
                        'prescription_item_id' => $item->id,
                        'supplier_id' => $supplierMedicine->supplier_id,
                        'supplier_medicine_id' => $supplierMedicine->id,
                        'quantity' => $item->quantity,
                        'unit_price' => $supplierMedicine->unit_price,
                        'total_price' => $supplierMedicine->unit_price * $item->quantity,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $hasItems = true;
                }
            }
        }

        if (! $hasItems) {
            Log::error('No suppliers available for prescription', [
                'prescription_id' => $this->id,
                'prescription_number' => $this->prescription_number,
            ]);

            return null;
        }

        // Bulk insert
        QuotationItem::insert($quotationItems);

        $quotation->calculateTotal();
        $quotation->optimizePricing();

        return $quotation->fresh(['items']);
    }

    /**
     * OPTIMIZED: Create orders with better grouping
     */
    protected function createOrdersFromQuotation(Quotation $quotation): void
    {
        $supplierGroups = $this->groupQuotationItemsBySupplier($quotation);

        if (empty($supplierGroups)) {
            $this->status = 'pending';
            $this->save();

            Log::error('No suppliers selected for prescription orders', [
                'prescription_id' => $this->id,
                'quotation_id' => $quotation->id,
            ]);

            return;
        }

        // Bulk create orders
        foreach ($supplierGroups as $supplierId => $groupData) {
            try {
                $this->createOrderForSupplier($quotation, $supplierId, $groupData);
            } catch (\Exception $e) {
                Log::error('Error creating order for supplier', [
                    'supplier_id' => $supplierId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->status = 'processing';
        $this->save();

    }

    /**
     * Group quotation items by best supplier
     */
    protected function groupQuotationItemsBySupplier(Quotation $quotation): array
    {
        $supplierGroups = [];

        // Eager load relationships in one query
        $quotationItems = $quotation->items()
            ->with(['prescriptionItem', 'supplier'])
            ->get()
            ->groupBy('prescription_item_id');

        foreach ($this->items as $prescriptionItem) {
            $availableItems = $quotationItems->get($prescriptionItem->id, collect());

            // Get cheapest option
            $bestItem = $availableItems->sortBy('unit_price')->first();

            if (! $bestItem) {
                Log::warning('No quotation item found', [
                    'prescription_item_id' => $prescriptionItem->id,
                ]);

                continue;
            }

            $supplierId = $bestItem->supplier_id;

            if (! isset($supplierGroups[$supplierId])) {
                $supplierGroups[$supplierId] = [
                    'supplier' => $bestItem->supplier,
                    'quotation_items' => [],
                    'total_amount' => 0,
                ];
            }

            $supplierGroups[$supplierId]['quotation_items'][] = $bestItem;
            $supplierGroups[$supplierId]['total_amount'] += $bestItem->total_price;
        }

        return $supplierGroups;
    }

    /**
     * Create order for supplier with bulk insert
     */
    protected function createOrderForSupplier(Quotation $quotation, int $supplierId, array $groupData): void
    {
        $pricingService = app(PricingService::class);

        $supplierTotal = 0;
        $markedUpTotal = 0;

        foreach ($groupData['quotation_items'] as $quotationItem) {
            $prescriptionItem = $quotationItem->prescriptionItem;
            $medicine = $prescriptionItem->medicine;

            // Get supplier price from quotation item
            $supplierPrice = $quotationItem->unit_price;

            // Calculate marked-up price
            $pricing = $pricingService->calculateFinalPrice(
                $supplierPrice,
                $medicine,
                $quotationItem->quantity
            );

            $supplierTotal += $pricing['supplier_total'];
            $markedUpTotal += $pricing['final_total'];
        }

        $markupTotal = $markedUpTotal - $supplierTotal;

        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'quotation_id' => $quotation->id,
            'prescription_id' => $this->id,
            'supplier_id' => $supplierId,
            'supplier_total' => $supplierTotal,
            'markup_total' => $markupTotal,
            'total_amount' => $markedUpTotal,
            'status' => 'pending',
            'ordered_at' => now(),
            'expected_delivery' => now()->addHours(24),
            'notes' => "Auto-generated from prescription {$this->prescription_number}",
        ]);

        // Bulk insert order items with proper pricing
        $orderItems = [];
        foreach ($groupData['quotation_items'] as $quotationItem) {
            $prescriptionItem = $quotationItem->prescriptionItem;
            $medicine = $prescriptionItem->medicine;

            // Get supplier price
            $supplierPrice = $quotationItem->unit_price;

            // Calculate marked-up price
            $pricing = $pricingService->calculateFinalPrice(
                $supplierPrice,
                $medicine,
                $quotationItem->quantity
            );

            $orderItems[] = [
                'order_id' => $order->id,
                'quotation_item_id' => $quotationItem->id,
                'medicine_id' => $prescriptionItem->medicine_id,
                'quantity' => $quotationItem->quantity,
                'supplier_price' => $pricing['supplier_price'],
                'unit_price' => $pricing['final_unit_price'],
                'total_price' => $pricing['final_total'],
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        OrderItem::insert($orderItems);

        Log::info('Order created with proper markup', [
            'order_number' => $order->order_number,
            'supplier_id' => $supplierId,
            'supplier_total' => $supplierTotal,
            'markup_total' => $markupTotal,
            'total_amount' => $markedUpTotal,
        ]);

        // Notify supplier asynchronously
        dispatch(function () use ($order, $groupData) {
            $this->notifySupplier($order, $groupData['supplier']);
        })->afterResponse();
    }

    /**
     * Create insurance claim for this prescription
     */
    public function createInsuranceClaim()
    {
        // Verify insurance coverage is enabled
        if (! $this->insurance_covered) {
            Log::warning('Attempted to create insurance claim for non-insured prescription', [
                'prescription_id' => $this->id,
            ]);
            throw new \Exception('Prescription is not marked for insurance coverage');
        }

        if (! $this->relationLoaded('patient')) {
            $this->load('patient');
        }

        if (! $this->patient->insurance_provider_id || ! $this->patient->insurance_number) {
            Log::error('Patient missing insurance information', [
                'prescription_id' => $this->id,
                'patient_id' => $this->patient_id,
                'has_provider' => ! empty($this->patient->insurance_provider_id),
                'has_number' => ! empty($this->patient->insurance_number),
            ]);
            throw new \Exception('Patient does not have complete insurance information');
        }

        // Check if claim already exists
        if ($this->insuranceClaim) {
            Log::warning('Attempted to create duplicate insurance claim', [
                'prescription_id' => $this->id,
                'existing_claim_id' => $this->insuranceClaim->id,
                'existing_claim_number' => $this->insuranceClaim->claim_number,
            ]);

            return $this->insuranceClaim;
        }

        $existingClaim = InsuranceClaim::where('prescription_id', $this->id)->first();
        if ($existingClaim) {
            Log::warning('Insurance claim exists but relationship not loaded', [
                'prescription_id' => $this->id,
                'claim_id' => $existingClaim->id,
            ]);

            return $existingClaim;
        }

        $ordersTotal = $this->orders()
            ->whereIn('status', ['confirmed', 'delivered'])
            ->sum('total_amount');

        if ($ordersTotal <= 0) {
            Log::warning('No confirmed orders found for insurance claim', [
                'prescription_id' => $this->id,
                'total_orders' => $this->orders()->count(),
            ]);
            $ordersTotal = $this->total_amount;
        }

        // Create the claim
        $claim = InsuranceClaim::create([
            'prescription_id' => $this->id,
            'insurance_provider_id' => $this->patient->insurance_provider_id,
            'patient_id' => $this->patient_id,
            'policy_number' => $this->patient->insurance_number,
            'claimed_amount' => $ordersTotal,
            'status' => 'submitted',
            'submitted_at' => now(),
            'notes' => 'Auto-generated claim for prescription '.$this->prescription_number.' after order confirmation',
        ]);

        $this->update(['insurance_claim_id' => $claim->id]);

        Log::info('Insurance claim created successfully', [
            'claim_id' => $claim->id,
            'claim_number' => $claim->claim_number,
            'prescription_id' => $this->id,
            'prescription_number' => $this->prescription_number,
            'claimed_amount' => $ordersTotal,
            'insurance_provider_id' => $this->patient->insurance_provider_id,
            'patient_id' => $this->patient_id,
        ]);

        // Notify insurance provider asynchronously
        dispatch(function () use ($claim) {
            $this->notifyInsuranceProvider($claim);
        })->afterResponse();

        return $claim;
    }

    protected function notifyInsuranceProvider(?InsuranceClaim $claim = null): void
    {
        try {
            if (! $claim) {
                $claim = $this->insuranceClaim;
            }

            if (! $claim) {
                Log::warning('Cannot notify insurance provider - no claim found', [
                    'prescription_id' => $this->id,
                ]);

                return;
            }

            $provider = $claim->insuranceProvider;

            if (! $provider) {
                Log::warning('Cannot notify insurance provider - provider not found', [
                    'claim_id' => $claim->id,
                ]);

                return;
            }

            if (! $provider->email) {
                Log::info('Insurance provider has no email address', [
                    'claim_id' => $claim->id,
                    'provider_id' => $provider->id,
                ]);

                return;
            }

            if (! class_exists(InsuranceClaimFormMail::class)) {
                Log::error('InsuranceClaimFormMail class not found', [
                    'claim_id' => $claim->id,
                ]);

                return;
            }

            // Queue email instead of sending immediately
            Mail::to($provider->email)->queue(
                new InsuranceClaimFormMail($claim)
            );

            Log::info('Insurance provider email queued', [
                'claim_id' => $claim->id,
                'provider_id' => $provider->id,
                'email' => $provider->email,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to notify insurance provider', [
                'claim_id' => $claim->id ?? null,
                'prescription_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function notifySupplier(Order $order, $supplier): void
    {
        try {
            if (! $supplier) {
                Log::warning('Cannot notify supplier - supplier not found', [
                    'order_id' => $order->id,
                ]);

                return;
            }

            if (! class_exists(NewOrderNotification::class)) {
                Log::error('NewOrderNotification mail class not found', [
                    'order_id' => $order->id,
                ]);

                return;
            }

            if ($supplier->user && $supplier->user->email) {
                try {
                    Mail::to($supplier->user->email)->queue(
                        new NewOrderNotification($order)
                    );

                    Log::info('Supplier email queued', [
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

            // Send notification if user exists
            if ($supplier->user) {
                try {
                    $supplier->user->notify(new \App\Notifications\NewOrderNotification($order));

                    Log::info('Supplier notified via notification', [
                        'order_id' => $order->id,
                        'supplier_id' => $supplier->id,
                        'user_id' => $supplier->user->id,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send notification to supplier', [
                        'order_id' => $order->id,
                        'supplier_id' => $supplier->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to notify supplier', [
                'order_id' => $order->id,
                'supplier_id' => $supplier->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check drug interactions asynchronously
     */
    protected function checkDrugInteractions(): void
    {
        $medicineIds = $this->items->pluck('medicine_id')->toArray();

        $interactions = MedicineInteraction::whereIn('medicine_id', $medicineIds)
            ->whereIn('interacting_medicine_id', $medicineIds)
            ->where('medicine_id', '!=', DB::raw('interacting_medicine_id'))
            ->get();

        if ($interactions->isNotEmpty()) {
            foreach ($interactions as $interaction) {
                if ($interaction->interaction_type === 'major') {
                    Log::warning('Major drug interaction detected', [
                        'prescription_number' => $this->prescription_number,
                        'medicine_1' => $interaction->medicine_id,
                        'medicine_2' => $interaction->interacting_medicine_id,
                    ]);
                }
            }
        }

        if ($this->patient->allergies) {
            foreach ($this->items as $item) {
                if (stripos($item->medicine->active_ingredients ?? '', $this->patient->allergies) !== false) {
                    Log::warning('Potential allergy conflict', [
                        'prescription_number' => $this->prescription_number,
                        'medicine' => $item->medicine->generic_name,
                    ]);
                }
            }
        }
    }

    protected function generateQuotation(): void
    {
        // Deprecated - kept for compatibility
    }

    protected function notifyStakeholders(): void
    {
        // Queue notifications
    }

    /**
     *  Update total with lock
     */
    public function updateTotalAmount(): void
    {
        if ($this->isUpdatingTotal) {
            return;
        }

        $this->isUpdatingTotal = true;

        try {
            $total = $this->items()->sum('total_price');

            if ($this->total_amount != $total) {
                $this->updateQuietly(['total_amount' => $total]);

                Log::info('Prescription total updated with marked-up prices', [
                    'prescription_id' => $this->id,
                    'prescription_number' => $this->prescription_number,
                    'total_amount' => $total,
                    'item_count' => $this->items()->count(),
                ]);
            }
        } finally {
            $this->isUpdatingTotal = false;
        }
    }

    public function cancel(?string $reason = null): bool
    {
        if (! in_array($this->status, ['draft', 'submitted', 'processing'])) {
            throw new \Exception('Cannot cancel prescription in current status');
        }

        return DB::transaction(function () use ($reason) {
            $this->status = 'cancelled';
            $this->notes = ($this->notes ? $this->notes."\n\n" : '').'Cancelled: '.$reason;
            $this->save();

            Quotation::where('prescription_id', $this->id)
                ->update(['status' => 'rejected']);

            Order::where('prescription_id', $this->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->update([
                    'status' => 'cancelled',
                    'notes' => DB::raw("CONCAT(COALESCE(notes, ''), '\n\nCancelled: ".addslashes($reason)."')"),
                ]);

            return true;
        });
    }

    public function markFulfilled(): bool
    {
        $this->status = 'fulfilled';
        $this->fulfilled_at = now();

        return $this->save();
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['submitted', 'processing']);
    }

    public function scopeForPhysician($query, $physicianId)
    {
        return $query->where('physician_id', $physicianId);
    }

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
