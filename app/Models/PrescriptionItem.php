<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        'quantity'              => 'integer',
        'dose_amount'           => 'decimal:2',
        'frequency_per_day'     => 'integer',
        'duration_days'         => 'integer',
        'total_volume_required' => 'decimal:2',
        'unit_price'            => 'decimal:2',
        'supplier_price'        => 'decimal:2',
        'total_price'           => 'decimal:2',
    ];

    // Frequency label → times per day
    private const FREQUENCY_MAP = [
        'OD'    => 1,
        'Stat'  => 1,
        'PRN'   => 1,
        'Nocte' => 1,
        'BDS'   => 2,
        'TDS'   => 3,
        'QID'   => 4,
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (PrescriptionItem $item) {
            // 1. Load medicine relation if needed
            if (! $item->relationLoaded('medicine') && $item->medicine_id) {
                $item->load('medicine');
            }

            // 2. Derive medicine meta from the medicine model
            if ($item->medicine) {
                $item->measurement_type = $item->medicine->measurement_type ?? 'discrete';
                $item->volume_per_unit  = $item->medicine->volume_per_unit;

                if (! $item->unit_of_measurement) {
                    $item->unit_of_measurement = $item->measurement_type === 'volume'
                        ? 'ml'
                        : ($item->medicine->unit_of_measurement ?? 'unit');
                }
            }

            // 3. Derive frequency_per_day from the frequency string
            if ($item->frequency && ! $item->frequency_per_day) {
                $item->frequency_per_day = self::FREQUENCY_MAP[$item->frequency] ?? 1;
            }

            // 4. Calculate quantity, total_volume_required, pricing — all from scratch
            $item->recalculate();
        });

        static::saved(function (PrescriptionItem $item) {
            // Keep prescription total in sync
            if ($item->prescription_id && $item->prescription) {
                $item->prescription->updateTotalAmount();
            }
        });

        static::deleted(function (PrescriptionItem $item) {
            if ($item->prescription_id && $item->prescription) {
                $item->prescription->updateTotalAmount();
            }
        });
    }

    /**
     * Full recalculation of quantity, total_volume_required, and pricing.
     * Called automatically in the saving hook.
     * Can also be called manually when editing items outside the form.
     */
    public function recalculate(): void
    {
        if (! $this->medicine) {
            Log::warning('PrescriptionItem::recalculate() — medicine not loaded', ['item_id' => $this->id ?? 'new']);
            return;
        }

        // ── Step 1: derive frequency_per_day if not already set ──────────────
        if (! $this->frequency_per_day && $this->frequency) {
            $this->frequency_per_day = self::FREQUENCY_MAP[$this->frequency] ?? 1;
        }

        // ── Step 2: calculate quantity ───────────────────────────────────────
        if ($this->dose_amount && $this->frequency_per_day && $this->duration_days) {
            $calculation = $this->medicine->calculateRequiredQuantity(
                (float) $this->dose_amount,
                (int)   $this->frequency_per_day,
                (int)   $this->duration_days
            );

            $this->total_volume_required = $calculation['total_required'];
            $this->quantity              = $calculation['quantity_needed'];
        }

        // ── Step 3: guard — quantity must exist by now ───────────────────────
        if (empty($this->quantity)) {
            throw new \RuntimeException(
                "PrescriptionItem: cannot determine quantity for medicine_id={$this->medicine_id}. " .
                "Provide dose_amount, frequency, and duration_days."
            );
        }

        // ── Step 4: fetch best supplier price and apply markup ───────────────
        $supplierPrice = DB::table('supplier_medicines')
            ->where('medicine_id', $this->medicine_id)
            ->where('is_available', true)
            ->where('stock_quantity', '>=', $this->quantity)
            ->orderBy('unit_price', 'asc')
            ->value('unit_price');

        if ($supplierPrice) {
            try {
                $pricingService = app(\App\Services\PricingService::class);
                $pricing = $pricingService->calculateFinalPrice($supplierPrice, $this->medicine, $this->quantity);

                $this->supplier_price = $pricing['supplier_price'];
                $this->unit_price     = $pricing['final_unit_price'];
                $this->markup_amount  = $pricing['markup_amount'] ?? null;
            } catch (\Exception $e) {
                Log::error('PrescriptionItem: pricing calculation failed', [
                    'medicine_id' => $this->medicine_id,
                    'error'       => $e->getMessage(),
                ]);
                // Fall back to raw supplier price
                $this->supplier_price = $supplierPrice;
                $this->unit_price     = $supplierPrice;
            }
        }

        // ── Step 5: total price ──────────────────────────────────────────────
        if ($this->unit_price && $this->quantity) {
            $this->total_price = round((float) $this->unit_price * (int) $this->quantity, 2);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Legacy method — kept for any external callers, delegates to recalculate()
    // ──────────────────────────────────────────────────────────────────────────
    public function calculateQuantityAndPrice(): void
    {
        $this->recalculate();
    }

    /**
     * Get formatted dosage information
     */
    public function getFormattedDosage(): string
    {
        if (! $this->dose_amount || ! $this->frequency_per_day || ! $this->duration_days) {
            return $this->dosage_instructions ?? 'N/A';
        }

        $unit = $this->medicine?->getDisplayUnit() ?? 'unit';

        return sprintf(
            '%s %s, %d times daily for %d days (Total: %s %s)',
            number_format((float) $this->dose_amount, 1),
            $unit,
            $this->frequency_per_day,
            $this->duration_days,
            number_format((float) $this->total_volume_required, 1),
            $unit
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Relations
    // ──────────────────────────────────────────────────────────────────────────

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



    public function getBestPrice(): ?float
    {
        $sm = $this->medicine->supplierMedicines()
            ->where('is_available', true)
            ->where('stock_quantity', '>=', $this->quantity)
            ->orderBy('unit_price')
            ->first();

        return $sm ? $sm->unit_price * $this->quantity : null;
    }

    public function isAvailable(): bool
    {
        return $this->medicine->supplierMedicines()
            ->where('is_available', true)
            ->where('stock_quantity', '>=', $this->quantity)
            ->exists();
    }

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