<?php

namespace App\Models;

use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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

    public static function generatePrescriptionNumber(): string
    {
        $prefix = 'RX';
        $year = date('Y');
        $month = date('m');

        $lastPrescription = static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPrescription && preg_match('/(\d{6})$/', $lastPrescription->prescription_number, $matches)) {
            $sequence = intval($matches[0]) + 1;
        } else {
            $sequence = 1;
        }

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

            $this->generateQuotation();
            $this->notifyStakeholders();

            return true;
        });
    }

    protected function checkDrugInteractions(): void
    {
        $medicineIds = $this->items->pluck('medicine_id')->toArray();

        $interactions = MedicineInteraction::where(function ($query) use ($medicineIds) {
            $query->whereIn('medicine_id', $medicineIds)
                ->whereIn('interacting_medicine_id', $medicineIds);
        })->get();

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
            foreach ($this->items as $item) {
                $medicine = $item->medicine;
                if (stripos($medicine->active_ingredients, $this->patient->allergies) !== false) {
                    \Log::warning("Potential allergy conflict in prescription {$this->prescription_number}", [
                        'medicine' => $medicine->generic_name,
                        'patient_allergies' => $this->patient->allergies,
                    ]);
                }
            }
        }
    }

    protected function generateQuotation(): void
    {
        $quotation = Quotation::create([
            'quotation_number' => Quotation::generateQuotationNumber(),
            'prescription_id' => $this->id,
            'total_amount' => 0,
            'status' => 'pending',
            'valid_until' => now()->addHours(24),
        ]);

        foreach ($this->items as $item) {
            $supplierMedicines = $item->medicine->supplierMedicines()
                ->where('is_available', true)
                ->where('stock_quantity', '>=', $item->quantity)
                ->get();

            foreach ($supplierMedicines as $supplierMedicine) {
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'prescription_item_id' => $item->id,
                    'supplier_id' => $supplierMedicine->supplier_id,
                    'supplier_medicine_id' => $supplierMedicine->id,
                    'quantity' => $item->quantity,
                    'unit_price' => $supplierMedicine->unit_price,
                    'total_price' => $supplierMedicine->unit_price * $item->quantity,
                ]);
            }
        }

        $quotation->calculateTotal();
        $quotation->optimizePricing();
    }

    protected function notifyStakeholders(): void
    {
        // Implement notification logic
    }

    public function updateTotalAmount(): void
    {
        // Prevent recursive calls
        if ($this->isUpdatingTotal) {
            return;
        }

        $this->isUpdatingTotal = true;

        try {
            $total = $this->items()->sum('total_price');

            if ($this->total_amount != $total) {
                DB::table('prescriptions')
                    ->where('id', $this->id)
                    ->update(['total_amount' => $total]);

                // Update the model instance
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

            if ($this->quotation) {
                $this->quotation->update(['status' => 'rejected']);
            }

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
