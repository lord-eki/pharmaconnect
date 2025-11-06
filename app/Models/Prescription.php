<?php

namespace App\Models;

use App\Mail\InsuranceClaimFormMail;
use App\Notifications\NewOrderNotification;
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

    // REMOVED eager loading to prevent N+1
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
     * OPTIMIZED: Submit with better error handling and performance
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

        // Create insurance claim asynchronously
        if ($this->insurance_covered) {
            dispatch(function () {
                $this->createInsuranceClaim();
            })->afterResponse();
        }
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
        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'quotation_id' => $quotation->id,
            'prescription_id' => $this->id,
            'supplier_id' => $supplierId,
            'total_amount' => $groupData['total_amount'],
            'status' => 'pending',
            'ordered_at' => now(),
            'expected_delivery' => now()->addHours(24),
            'notes' => "Auto-generated from prescription {$this->prescription_number}",
        ]);

        // Bulk insert order items
        $orderItems = [];
        foreach ($groupData['quotation_items'] as $quotationItem) {
            $orderItems[] = [
                'order_id' => $order->id,
                'quotation_item_id' => $quotationItem->id,
                'medicine_id' => $quotationItem->prescriptionItem->medicine_id,
                'quantity' => $quotationItem->quantity,
                'unit_price' => $quotationItem->unit_price,
                'total_price' => $quotationItem->total_price,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        OrderItem::insert($orderItems);

        // Notify supplier asynchronously
        dispatch(function () use ($order, $groupData) {
            $this->notifySupplier($order, $groupData['supplier']);
        })->afterResponse();

        Log::info('Order created', [
            'order_number' => $order->order_number,
            'supplier_id' => $supplierId,
            'total_amount' => $groupData['total_amount'],
        ]);
    }

    /**
     * Create insurance claim
     */
    public function createInsuranceClaim(): void
    {
        Log::info('Creating insurance claim', [
            'prescription_id' => $this->id,
            'insurance_covered' => $this->insurance_covered,
            'orders_count' => $this->orders->count(),
        ]);

        // Check 1: Insurance covered and orders exist
        if (! $this->insurance_covered) {
            Log::warning('Insurance claim not created - insurance not covered', [
                'prescription_id' => $this->id,
            ]);

            return;
        }

        if ($this->orders->isEmpty()) {
            Log::warning('Insurance claim not created - no orders', [
                'prescription_id' => $this->id,
            ]);

            return;
        }

        // Check 2: Patient has insurance details
        $patient = $this->patient;
        if (! $patient) {
            Log::error('Insurance claim not created - no patient found', [
                'prescription_id' => $this->id,
            ]);

            return;
        }

        if (! $patient->insurance_provider_id || ! $patient->insurance_number) {
            Log::warning('Insurance claim not created - patient missing insurance details', [
                'prescription_id' => $this->id,
                'patient_id' => $patient->id,
                'has_provider' => (bool) $patient->insurance_provider_id,
                'has_number' => (bool) $patient->insurance_number,
            ]);

            return;
        }

        // Check 3: Claim doesn't already exist
        if ($this->insurance_claim_id) {
            Log::info('Insurance claim not created - claim_id already set', [
                'prescription_id' => $this->id,
                'existing_claim_id' => $this->insurance_claim_id,
            ]);

            return;
        }

        if ($this->insuranceClaim()->exists()) {
            Log::info('Insurance claim not created - claim relationship already exists', [
                'prescription_id' => $this->id,
            ]);

            return;
        }

        try {
            $claimAmount = $this->orders->sum('total_amount');

            Log::info('Creating insurance claim record', [
                'prescription_id' => $this->id,
                'patient_id' => $this->patient_id,
                'insurance_provider_id' => $patient->insurance_provider_id,
                'claim_amount' => $claimAmount,
            ]);

            $claim = InsuranceClaim::create([
                'claim_number' => InsuranceClaim::generateClaimNumber(),
                'prescription_id' => $this->id,
                'policy_number' => $patient->insurance_number,
                'deductible_amount' => 0,
                'patient_id' => $this->patient_id,
                'insurance_provider_id' => $patient->insurance_provider_id,
                'insurance_number' => $patient->insurance_number,
                'claimed_amount' => $claimAmount,
                'status' => 'submitted',
                'notes' => 'Auto-generated claim from prescription '.$this->prescription_number,
                'submitted_at' => now(),
            ]);

            Log::info('Insurance claim created successfully', [
                'claim_id' => $claim->id,
                'claim_number' => $claim->claim_number,
                'prescription_id' => $this->id,
            ]);

            $this->update(['insurance_claim_id' => $claim->id]);

            $this->notifyInsuranceProvider($claim);

        } catch (\Exception $e) {
            Log::error('Failed to create insurance claim', [
                'prescription_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; 
        }
    }

    protected function notifyInsuranceProvider(InsuranceClaim $claim): void
    {
        try {
            $provider = $claim->insuranceProvider;

            if ($provider->email) {
                Mail::to($provider->email)->send(
                    new InsuranceClaimFormMail($claim)
                );
            }

            Log::info('Insurance provider notified', [
                'claim_id' => $claim->id,
                'provider_id' => $provider->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to notify insurance provider', [
                'claim_id' => $claim->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function notifySupplier(Order $order, $supplier): void
    {
        try {
            if ($supplier->email) {
                Mail::to($supplier->email)->send(
                    new \App\Mail\NewOrderNotification($order)
                );
            }

            if ($supplier->user) {
                $supplier->user->notify(new NewOrderNotification($order));
            }

        } catch (\Exception $e) {
            Log::error('Failed to notify supplier', [
                'order_id' => $order->id,
                'supplier_id' => $supplier->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * OPTIMIZED: Check drug interactions asynchronously
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
     * OPTIMIZED: Update total with lock
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
