<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Medicine extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'generic_name',
        'brand_name',
        'strength',
        'dosage_form',
        'pack_size',
        'manufacturer',
        'active_ingredients',
        'description',
        'usage_instructions',
        'side_effects',
        'contraindications',
        'storage_requirements',
        'prescription_required',
        'controlled_substance',
        'ppb_registration_number',
        'is_active',
    ];

    protected $casts = [
        'prescription_required' => 'boolean',
        'controlled_substance' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Eager load to prevent N+1 queries
    protected $with = ['category'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MedicineCategory::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(MedicineInteraction::class);
    }

    public function supplierMedicines(): HasMany
    {
        return $this->hasMany(SupplierMedicine::class);
    }

    public function reverseInteractions(): HasMany
    {
        return $this->hasMany(MedicineInteraction::class, 'interacting_medicine_id');
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'supplier_medicines')
            ->withPivot('unit_price', 'stock_quantity', 'minimum_order_quantity', 'expiry_date', 'batch_number', 'is_available', 'last_updated')
            ->withTimestamps();
    }

    public function prescriptionItems(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    /**
     * Optimized method to get cheapest supplier price
     * 
     */
    public function getCheapestSupplierPrice(?int $quantity = 1): ?float
    {
        if (!$quantity || $quantity <= 0) {
            $quantity = 1;
        }

        $cacheKey = "medicine:{$this->id}:price:{$quantity}";

        return Cache::remember($cacheKey, 900, function () use ($quantity) {
            return DB::table('supplier_medicines')
                ->where('medicine_id', $this->id)
                ->where('is_available', true)
                ->where('stock_quantity', '>=', $quantity)
                ->useIndex('idx_medicine_available_stock,idx_medicine_price')
                ->min('unit_price');
        });
    }

    /**
     * Get all available suppliers with stock
     */
    public function getAvailableSuppliers(int $quantity = 1)
    {
        $cacheKey = "medicine:{$this->id}:suppliers:{$quantity}";
        
        return Cache::remember($cacheKey, 600, function () use ($quantity) {
            return $this->supplierMedicines()
                ->with('supplier:id,name,phone,email') // Only load needed columns
                ->select(['id', 'supplier_id', 'medicine_id', 'unit_price', 'stock_quantity', 'expiry_date', 'batch_number'])
                ->where('is_available', true)
                ->where('stock_quantity', '>=', $quantity)
                ->where('expiry_date', '>', now()->addMonths(1)) // Don't show expiring stock
                ->orderBy('unit_price', 'asc')
                ->limit(10) // Only return top 10 cheapest
                ->get();
        });
    }

    /**
     * Scope for active medicines only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for medicines with available stock
     * OPTIMIZED: Better subquery performance
     */
    public function scopeWithStock($query, int $minQuantity = 1)
    {
        return $query->whereHas('supplierMedicines', function ($q) use ($minQuantity) {
            $q->where('is_available', true)
              ->where('stock_quantity', '>=', $minQuantity);
        });
    }

    /**
     * Get formatted medicine display name
     * OPTIMIZED: Better cache key naming, added fallback
     */
    public function getDisplayNameAttribute(): string
    {
        return Cache::remember("medicine:{$this->id}:display", 3600, function () {
            $brandInfo = $this->brand_name ? " ({$this->brand_name})" : '';
            $strength = $this->strength ?: '';
            $form = $this->dosage_form ?: '';
            
            return trim("{$this->generic_name}{$brandInfo} - {$strength} - {$form}");
        });
    }

    /**
     * Clear medicine-related caches
     * IMPROVED: Clear all cache variations efficiently
     */
    public function clearCaches(): void
    {
        // Clear display name cache
        Cache::forget("medicine:{$this->id}:display");
        
        // Clear price caches for common quantities
        foreach ([1, 10, 30, 60, 90, 100] as $qty) {
            Cache::forget("medicine:{$this->id}:price:{$qty}");
            Cache::forget("medicine:{$this->id}:suppliers:{$qty}");
        }

        // Clear tags if using Redis/Memcached
        if (in_array(config('cache.default'), ['redis', 'memcached'])) {
            Cache::tags(['medicine', "medicine:{$this->id}"])->flush();
        }
    }

    /**
     * Bulk clear caches for multiple medicines
     * NEW: For mass updates
     */
    public static function clearCachesForMedicines(array $medicineIds): void
    {
        if (in_array(config('cache.default'), ['redis', 'memcached'])) {
            foreach ($medicineIds as $id) {
                Cache::tags(['medicine', "medicine:{$id}"])->flush();
            }
        } else {
            // Fallback for database/file cache
            foreach ($medicineIds as $id) {
                $medicine = new static(['id' => $id]);
                $medicine->clearCaches();
            }
        }
    }

    /**
     * Boot method to clear caches on updates
     */
    protected static function boot()
    {
        parent::boot();

        static::updated(function ($medicine) {
            $medicine->clearCaches();
        });

        static::deleted(function ($medicine) {
            $medicine->clearCaches();
        });

        // Clear cache when new supplier medicine is added
        static::saved(function ($medicine) {
            Cache::forget("medicine:{$medicine->id}:has_stock");
        });
    }

    /**
     * NEW: Check if medicine has any stock (cached)
     */
    public function hasStock(): bool
    {
        return Cache::remember("medicine:{$this->id}:has_stock", 300, function () {
            return $this->supplierMedicines()
                ->where('is_available', true)
                ->where('stock_quantity', '>', 0)
                ->exists();
        });
    }

    /**
     * NEW: Get medicine with cheapest price (for listings)
     */
    public function scopeWithCheapestPrice($query)
    {
        return $query->addSelect([
            'cheapest_price' => DB::table('supplier_medicines')
                ->selectRaw('MIN(unit_price)')
                ->whereColumn('medicine_id', 'medicines.id')
                ->where('is_available', true)
                ->where('stock_quantity', '>', 0)
        ]);
    }
}