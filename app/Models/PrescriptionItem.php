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
        'dosage_instructions',
        'duration_days',
        'frequency',
        'unit_price',
        'total_price',
        'status',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'duration_days' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Auto-calculate total price if unit price and quantity are set
            if ($item->quantity && $item->unit_price) {
                $item->total_price = $item->quantity * $item->unit_price;
            }
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