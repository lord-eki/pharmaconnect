<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrescriptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'medicine_id',
        'quantity',
        'dose_amount',
        'frequency',
        'frequency_per_day',
        'duration_days',
        'total_volume_required',
        'dosage_instructions',
        'unit_price',
        'supplier_price',
        'measurement_type',
        'volume_per_unit',
        'markup_amount',
        'total_price',
        'status',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'dose_amount' => 'decimal:2',
        'frequency_per_day' => 'integer',
        'duration_days' => 'integer',
        'total_volume_required' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Load medicine if not already loaded
            if (! $item->relationLoaded('medicine') && $item->medicine_id) {
                $item->load('medicine');
            }

            // Set measurement type and volume per unit from medicine
            if ($item->medicine) {
                // Only set if not already set
                if (! $item->measurement_type) {
                    $item->measurement_type = $item->medicine->measurement_type ?? 'discrete';
                }

                if (! $item->volume_per_unit) {
                    $item->volume_per_unit = $item->medicine->volume_per_unit;
                }

                // Set unit_of_measurement based on measurement_type
                if (! $item->unit_of_measurement) {
                    if ($item->measurement_type === 'volume') {
                        $item->unit_of_measurement = 'ml';
                    } else {
                        $item->unit_of_measurement = $item->medicine->unit_of_measurement ?? 'unit';
                    }
                }
            }

            // Calculate quantity and price
            $item->calculateQuantityAndPrice();

            // Auto-calculate total price if unit price and quantity are set
            if ($item->quantity && $item->unit_price) {
                $item->total_price = $item->quantity * $item->unit_price;
            }

            dd($item); // Debugging line to check item state before saving
        });

        static::saved(function ($item) {
            if ($item->prescription_id && $item->prescription) {
                $item->prescription->updateTotalAmount();
            }
        });

        static::deleted(function ($item) {
            // Update prescription total when item is deleted
            if ($item->prescription_id && $item->prescription) {
                $item->prescription->updateTotalAmount();
            }
        });
    }

    /**
     * Calculate quantity and total price based on medicine type
     */
    public function calculateQuantityAndPrice(): void
    {
        // Load medicine if not already loaded
        if (! $this->relationLoaded('medicine') && $this->medicine_id) {
            $this->load('medicine');
        }

        if (! $this->medicine) {
            return;
        }

        // If dose_amount, frequency_per_day, and duration_days are set
        if ($this->dose_amount && $this->frequency_per_day && $this->duration_days) {
            $calculation = $this->medicine->calculateRequiredQuantity(
                $this->dose_amount,
                $this->frequency_per_day,
                $this->duration_days
            );

            $this->total_volume_required = $calculation['total_required'];
            $this->quantity = $calculation['quantity_needed'];
        }

        // Calculate total price if unit_price is set
        if ($this->unit_price && $this->quantity) {
            $this->total_price = $this->unit_price * $this->quantity;
        }

    }

    /**
     * Get formatted dosage information
     */
    public function getFormattedDosage(): string
    {
        if (! $this->dose_amount || ! $this->frequency_per_day || ! $this->duration_days) {
            return $this->dosage_instructions ?? 'N/A';
        }

        $unit = $this->medicine->getDisplayUnit();

        return sprintf(
            '%s %s, %d times daily for %d days (Total: %s %s)',
            number_format($this->dose_amount, 1),
            $unit,
            $this->frequency_per_day,
            $this->duration_days,
            number_format($this->total_volume_required, 1),
            $unit
        );
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function quotationItems(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    // Get the best available price for this item
    public function getBestPrice(): ?float
    {
        $supplierMedicine = $this->medicine->supplierMedicines()
            ->where('is_available', true)
            ->where('stock_quantity', '>=', $this->quantity)
            ->orderBy('unit_price', 'asc')
            ->first();

        return $supplierMedicine ? $supplierMedicine->unit_price * $this->quantity : null;
    }

    // Check if medicine is available
    public function isAvailable(): bool
    {
        return $this->medicine->supplierMedicines()
            ->where('is_available', true)
            ->where('stock_quantity', '>=', $this->quantity)
            ->exists();
    }

    // Get available suppliers for this item
    public function getAvailableSuppliers()
    {
        return $this->medicine->supplierMedicines()
            ->where('is_available', true)
            ->where('stock_quantity', '>=', $this->quantity)
            ->with('supplier')
            ->get()
            ->pluck('supplier')
            ->unique('id');
    }
}
