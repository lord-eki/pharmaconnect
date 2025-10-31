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
use Illuminate\Support\Facades\Notification;

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

    /**
     * Create insurance claim after orders are created
     */
    protected function createInsuranceClaim(): void
    {
        // Only create insurance of insurance is covering
        if (! $this->insurance_covered) {
            return;
        }

        if ($this->orders->isEmpty()) {
            Log::warning('No orders found for prescription, skipping create insurance claim', [
                'prescription_id' => $this->id,
            ]);

            return;
        }

        // check if patient has insurance
        $patient = $this->patient;
        if (! $patient->insurance_provider_id || ! $patient->insurance_number) {
            Log::warning('Patient has no insurance details', [
                'patient_id' => $patient->id,
                'prescription_id' => $this->id,
            ]);

            return;
        }

        // check if claim already exists
        if ($this->insurance_claim_id || $this->insuranceClaim()->exists()) {
            Log::info('Insurance claim already exists for prescription', [
                'prescription_id' => $this->id,
                'insurance_claim_id' => $this->insurance_claim_id,
            ]);

            return;
        }

        try {
            // calculate total claimed amount from orders
            $claimAmount = $this->orders->sum('total_amount');

            // create the insurance claim
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

            $this->update([
                'insurance_claim_id' => $claim->id,
            ]);

            Log::info('Insurance claim created for prescription', [
                'prescription_id' => $this->id,
                'insurance_claim_id' => $claim->id,
                'claimed_amount' => $claimAmount,
            ]);

            // notify insurance provider
            $this->notifyInsuranceProvider($claim);

        } catch (\Exception $e) {
            Log::error('Failed to create insurance claim for prescription', [
                'prescription_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }

    }

    /**
     * Notify insurance provider about new claim
     */
    protected function notifyInsuranceProvider(InsuranceClaim $claim): void
    {
        try {
            $provider = $claim->insuranceProvider;

            // Option 1: Email notification
            if ($provider->email) {
                Mail::to($provider->email)->send(
                    new InsuranceClaimFormMail($claim)
                );
            }

            // Option 2: Database notification (if provider has user account)
            // if ($provider->user) {
            //     $provider->user->notify(new \App\Notifications\NewInsuranceClaimNotification($claim));
            // }

            // Option 3: API submission (if provider has API integration)
            // if ($provider->api_endpoint && $provider->api_key) {
            //     dispatch(new \App\Jobs\SubmitClaimToInsuranceAPI($claim));
            // }

            \Log::info('Insurance provider notified about claim', [
                'claim_id' => $claim->id,
                'provider_id' => $provider->id,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to notify insurance provider', [
                'claim_id' => $claim->id,
                'error' => $e->getMessage(),
            ]);
        }
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

    public static function generatePrescriptionNumber(): string
    {
        $prefix = 'RX';
        $year = date('Y');
        $month = date('m');

        $cacheKey = "last_prescription_{$year}_{$month}";

        $lastSequence = Cache::remember($cacheKey, 300, function () use ($year, $month) {
            $lastPrescription = static::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->orderBy('id', 'desc')
                ->first();

            if ($lastPrescription && preg_match('/(\d{5})$/', $lastPrescription->prescription_number, $matches)) {
                return intval($matches[0]);
            }

            return 0;
        });

        $sequence = $lastSequence + 1;
        Cache::put($cacheKey, $sequence, 300);

        return sprintf('%s%s%s%05d', $prefix, $year, $month, $sequence);
    }

    public function submit(): bool
    {
        return DB::transaction(function () {
            if ($this->items->isEmpty()) {
                throw new \Exception('Cannot submit prescription without medicines');
            }

            $this->checkDrugInteractions();

            $this->status = 'submitted';
            $this->save();

            // Generate quotation first, then create orders from it
            $quotation = $this->generateQuotationSync();

            if ($quotation) {
                // Create orders grouped by supplier from quotation
                $this->createOrdersFromQuotation($quotation);

            }

            dispatch(function () {
                $this->notifyStakeholders();
            })->afterResponse();

            return true;
        });
    }

    /**
     * Generate quotation synchronously and return it
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

        // Load all medicines and their suppliers in one go
        $this->items->load(['medicine.supplierMedicines' => function ($query) {
            $query->where('is_available', true)
                ->where('stock_quantity', '>', 0);
        }]);

        $quotationItems = [];
        $hasItems = false;

        foreach ($this->items as $item) {
            $supplierMedicines = $item->medicine->supplierMedicines
                ->where('stock_quantity', '>=', $item->quantity);

            foreach ($supplierMedicines as $supplierMedicine) {
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

        if (! $hasItems) {
            \Log::error('No suppliers available for prescription', [
                'prescription_id' => $this->id,
                'prescription_number' => $this->prescription_number,
            ]);

            return null;
        }

        // Bulk insert for performance
        QuotationItem::insert($quotationItems);

        $quotation->calculateTotal();
        $quotation->optimizePricing();

        return $quotation->fresh(['items']);
    }

    /**
     * Create orders grouped by supplier from the quotation
     */
    protected function createOrdersFromQuotation(Quotation $quotation): void
    {
        // Group quotation items by supplier (choosing best prices)
        $supplierGroups = $this->groupQuotationItemsBySupplier($quotation);

        if (empty($supplierGroups)) {
            $this->status = 'pending';
            $this->save();

            \Log::error('No suppliers selected for prescription orders', [
                'prescription_id' => $this->id,
                'quotation_id' => $quotation->id,
            ]);

            return;
        }

        // Create an order for each supplier
        foreach ($supplierGroups as $supplierId => $groupData) {
            $this->createOrderForSupplier($quotation, $supplierId, $groupData);
        }

        // Update prescription status
        $this->status = 'processing';
        $this->save();
    }

    /**
     * Group quotation items by supplier, selecting best price for each medicine
     */
    protected function groupQuotationItemsBySupplier(Quotation $quotation): array
    {
        $supplierGroups = [];
        $selectedItems = []; // Track which prescription items we've already assigned

        // Load quotation items with relationships
        $quotation->load(['items.prescriptionItem', 'items.supplier', 'items.supplierMedicine']);

        // For each prescription item, find the best supplier (cheapest)
        foreach ($this->items as $prescriptionItem) {
            $availableQuotationItems = $quotation->items
                ->where('prescription_item_id', $prescriptionItem->id)
                ->sortBy('unit_price');

            $bestQuotationItem = $availableQuotationItems->first();

            if (! $bestQuotationItem) {
                \Log::warning('No quotation item found for prescription item', [
                    'prescription_item_id' => $prescriptionItem->id,
                    'medicine' => $prescriptionItem->medicine->generic_name ?? 'Unknown',
                ]);

                continue;
            }

            $supplierId = $bestQuotationItem->supplier_id;

            if (! isset($supplierGroups[$supplierId])) {
                $supplierGroups[$supplierId] = [
                    'supplier' => $bestQuotationItem->supplier,
                    'quotation_items' => [],
                    'total_amount' => 0,
                ];
            }

            $supplierGroups[$supplierId]['quotation_items'][] = $bestQuotationItem;
            $supplierGroups[$supplierId]['total_amount'] += $bestQuotationItem->total_price;
        }

        return $supplierGroups;
    }

    /**
     * Create a single order for a specific supplier
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

        // Create order items from quotation items
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

        // Send notification to supplier
        $this->notifySupplier($order, $groupData['supplier']);

        \Log::info('Order created for supplier', [
            'order_number' => $order->order_number,
            'quotation_id' => $quotation->id,
            'supplier_id' => $supplierId,
            'supplier_name' => $groupData['supplier']->name ?? 'Unknown',
            'total_amount' => $groupData['total_amount'],
            'items_count' => count($orderItems),
        ]);
    }

    /**
     * Send notification to supplier about new order
     */
    protected function notifySupplier(Order $order, $supplier): void
    {
        try {
            // Option 1: Email notification
            if ($supplier->email) {
                \Mail::to($supplier->email)->send(
                    new \App\Mail\NewOrderNotification($order)
                );
            }

            // Option 2: Database notification
            if ($supplier->user) {
                $supplier->user->notify(new NewOrderNotification($order));
            }

            // Option 3: Filament notification (in-app)
            \Filament\Notifications\Notification::make()
                ->title('New Order Received')
                ->body("Order {$order->order_number} for KES ".number_format($order->total_amount, 2))
                ->icon('heroicon-o-shopping-bag')
                ->success()
                ->sendToDatabase($supplier->user ?? null);

        } catch (\Exception $e) {
            \Log::error('Failed to notify supplier about order', [
                'order_id' => $order->id,
                'supplier_id' => $supplier->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

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
                    \Log::warning("Major drug interaction detected in prescription {$this->prescription_number}", [
                        'medicine_1' => $interaction->medicine_id,
                        'medicine_2' => $interaction->interacting_medicine_id,
                        'description' => $interaction->description,
                    ]);
                }
            }
        }

        if ($this->patient->allergies) {
            $this->items->load('medicine');

            foreach ($this->items as $item) {
                if (stripos($item->medicine->active_ingredients, $this->patient->allergies) !== false) {
                    \Log::warning("Potential allergy conflict in prescription {$this->prescription_number}", [
                        'medicine' => $item->medicine->generic_name,
                        'patient_allergies' => $this->patient->allergies,
                    ]);
                }
            }
        }
    }

    protected function generateQuotation(): void
    {
        // This method is kept for backward compatibility
        // The actual quotation is now generated in generateQuotationSync()
    }

    protected function notifyStakeholders(): void
    {
        // Queue notifications to avoid blocking
    }

    public function updateTotalAmount(): void
    {
        if ($this->isUpdatingTotal) {
            return;
        }

        $this->isUpdatingTotal = true;

        try {
            $total = DB::table('prescription_items')
                ->where('prescription_id', $this->id)
                ->sum('total_price');

            if ($this->total_amount != $total) {
                DB::table('prescriptions')
                    ->where('id', $this->id)
                    ->update(['total_amount' => $total]);

                $this->total_amount = $total;
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

            // Cancel all pending orders
            Order::where('prescription_id', $this->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->update([
                    'status' => 'cancelled',
                    'notes' => DB::raw("CONCAT(COALESCE(notes, ''), '\n\nCancelled due to prescription cancellation: ".addslashes($reason)."')"),
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
