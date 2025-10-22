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
    use HasFactory , HasAuditLog;

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



    //Automatically set physician_id and generate prescription_number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($prescription) {
            if (!$prescription->physician_id) {
                $prescription->physician_id = auth()->id();
            }
            
            if (!$prescription->prescription_number) {
                $prescription->prescription_number = static::generatePrescriptionNumber();
            }
            
            if (!$prescription->prescribed_at) {
                $prescription->prescribed_at = now();
            }

            // Set default status
            if (!$prescription->status) {
                $prescription->status = 'draft';
            }
        });

        // Update total_amount after saving items
        static::saved(function ($prescription) {
            $prescription->updateTotalAmount();
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



    // Generate unique prescription number
    public static function generatePrescriptionNumber(): string
    {
        $prefix = 'RX';
        $year = date('Y');
        $month = date('m');
        
        // Get the last prescription number for this month
        $lastPrescription = static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPrescription && preg_match('/\d+$/', $lastPrescription->prescription_number, $matches)) {
            $sequence = intval($matches[0]) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s%s%s%05d', $prefix, $year, $month, $sequence);
    }

    // Submit prescription for processing
    public function submit(): bool
    {
        return DB::transaction(function () {
            // Validate prescription has items
            if ($this->items->isEmpty()) {
                throw new \Exception('Cannot submit prescription without medicines');
            }

            // Check for drug interactions
            $this->checkDrugInteractions();

            // Update status
            $this->status = 'submitted';
            $this->save();

            // Trigger quotation generation process
            $this->generateQuotation();

            // Send notifications
            $this->notifyStakeholders();

            return true;
        });
    }

    // Check for drug interactions
    protected function checkDrugInteractions(): void
    {
        $medicineIds = $this->items->pluck('medicine_id')->toArray();
        
        $interactions = MedicineInteraction::where(function ($query) use ($medicineIds) {
            $query->whereIn('medicine_id', $medicineIds)
                  ->whereIn('interacting_medicine_id', $medicineIds);
        })->get();

        // Store interactions for physician review
        if ($interactions->isNotEmpty()) {
            // You can implement a notification system here
            // For now, we'll just log them
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

        // Check patient allergies
        if ($this->patient->allergies) {
            foreach ($this->items as $item) {
                $medicine = $item->medicine;
                // Simple check - you might want to make this more sophisticated
                if (stripos($medicine->active_ingredients, $this->patient->allergies) !== false) {
                    \Log::warning("Potential allergy conflict in prescription {$this->prescription_number}", [
                        'medicine' => $medicine->generic_name,
                        'patient_allergies' => $this->patient->allergies,
                    ]);
                }
            }
        }
    }

    // Generate quotation
    protected function generateQuotation(): void
    {
        $quotation = Quotation::create([
            'quotation_number' => Quotation::generateQuotationNumber(),
            'prescription_id' => $this->id,
            'total_amount' => 0, // Will be calculated
            'status' => 'pending',
            'valid_until' => now()->addHours(24), // 24-hour validity
        ]);

        // Create quotation items and request quotes from suppliers
        foreach ($this->items as $item) {
            // Get all suppliers who have this medicine
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

        // Update quotation total
        $quotation->calculateTotal();
        
        // Trigger price optimization
        $quotation->optimizePricing();
    }

    // Send notifications to stakeholders
    protected function notifyStakeholders(): void
    {
        // Notify patient
        // Notify operations team
        // Create system notifications
        // You'll implement actual notification logic based on your notification system
    }

    // Update total amount
    public function updateTotalAmount(): void
    {
        $total = $this->items()->sum('total_price');
        
        if ($this->total_amount != $total) {
            $this->total_amount = $total;
            $this->saveQuietly(); // Avoid infinite loop
        }
    }

    // Cancel prescription
    public function cancel(?string $reason = null): bool
    {
        if (!in_array($this->status, ['draft', 'submitted', 'processing'])) {
            throw new \Exception('Cannot cancel prescription in current status');
        }

        return DB::transaction(function () use ($reason) {
            $this->status = 'cancelled';
            $this->notes = ($this->notes ? $this->notes . "\n\n" : '') . "Cancelled: " . $reason;
            $this->save();

            // Cancel related quotations and orders
            if ($this->quotation) {
                $this->quotation->update(['status' => 'rejected']);
            }

            return true;
        });
    }

    // Mark as fulfilled
    public function markFulfilled(): bool
    {
        $this->status = 'fulfilled';
        $this->fulfilled_at = now();
        return $this->save();
    }

    // Scope for filtering
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['submitted', 'processing']);
    }

    public function scopeForPhysician($query, $physicianId)
    {
        return $query->where('physician_id', $physicianId);
    }

    // Accessor for full status display
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'processing' => 'Processing',
            'fulfilled' => 'Fulfilled',
            'cancelled' => 'Cancelled',
            default => 'Unknown',
        };
    }

}
