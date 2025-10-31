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

    protected $with = [];

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
     * Get cheapest supplier price 
     */
    public function getCheapestSupplierPrice(?int $quantity = 1): ?float
    {
        if (!$quantity || $quantity <= 0) {
            $quantity = 1;
        }

        $cacheQuantity = $quantity <= 10 ? $quantity : (ceil($quantity / 10) * 10);
        $cacheKey = "medicine:{$this->id}:price:{$cacheQuantity}_v2";

        try {
            return Cache::remember($cacheKey, 600, function () use ($quantity) {
                return DB::table('supplier_medicines')
                    ->where('medicine_id', $this->id)
                    ->where('is_available', true)
                    ->where('stock_quantity', '>=', $quantity)
                    ->min('unit_price');
            });
        } catch (\Exception $e) {
            \Log::error('Error fetching cheapest price', [
                'medicine_id' => $this->id,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     *  Get available suppliers
     */
    public function getAvailableSuppliers(int $quantity = 1)
    {
        $cacheKey = "medicine:{$this->id}:suppliers:{$quantity}_v2";
        
        return Cache::remember($cacheKey, 600, function () use ($quantity) {
            return DB::table('supplier_medicines')
                ->join('suppliers', 'suppliers.id', '=', 'supplier_medicines.supplier_id')
                ->where('supplier_medicines.medicine_id', $this->id)
                ->where('supplier_medicines.is_available', true)
                ->where('supplier_medicines.stock_quantity', '>=', $quantity)
                ->where('supplier_medicines.expiry_date', '>', now()->addMonths(1))
                ->select([
                    'supplier_medicines.id',
                    'supplier_medicines.supplier_id',
                    'supplier_medicines.unit_price',
                    'supplier_medicines.stock_quantity',
                    'supplier_medicines.expiry_date',
                    'supplier_medicines.batch_number',
                    'suppliers.name as supplier_name',
                    'suppliers.phone as supplier_phone',
                    'suppliers.email as supplier_email'
                ])
                ->orderBy('supplier_medicines.unit_price', 'asc')
                ->limit(10)
                ->get();
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Subquery for stock check
     */
    public function scopeWithStock($query, int $minQuantity = 1)
    {
        return $query->whereExists(function ($q) use ($minQuantity) {
            $q->select(DB::raw(1))
              ->from('supplier_medicines')
              ->whereColumn('supplier_medicines.medicine_id', 'medicines.id')
              ->where('supplier_medicines.is_available', true)
              ->where('supplier_medicines.stock_quantity', '>=', $minQuantity);
        });
    }

    /**
     *  Get display name without cache 
     */
    public function getDisplayNameAttribute(): string
    {
        $brandInfo = $this->brand_name ? " ({$this->brand_name})" : '';
        $strength = $this->strength ?: '';
        $form = $this->dosage_form ?: '';
        
        return trim("{$this->generic_name}{$brandInfo} - {$strength} - {$form}");
    }

    /**
     * OPTIMIZED: Clear caches more efficiently
     */
    public function clearCaches(): void
    {
        // Clear specific cache patterns
        $patterns = [
            "medicine:{$this->id}:price:*",
            "medicine:{$this->id}:suppliers:*",
            "medicine:{$this->id}:has_stock",
            "medicine_name_{$this->id}",
        ];

        foreach ($patterns as $pattern) {
            // For Redis
            if (config('cache.default') === 'redis') {
                $keys = Cache::getRedis()->keys(config('cache.prefix') . ':' . $pattern);
                if (!empty($keys)) {
                    Cache::getRedis()->del($keys);
                }
            } else {
                // For other drivers, clear common quantities
                foreach ([1, 5, 10, 20, 30, 50, 60, 90, 100] as $qty) {
                    Cache::forget("medicine:{$this->id}:price:{$qty}_v2");
                    Cache::forget("medicine:{$this->id}:suppliers:{$qty}_v2");
                }
            }
        }

        // Clear medicine options cache
        Cache::forget('medicine_options_v2');
    }

    /**
     * Bulk clear caches for multiple medicines
     */
    public static function clearCachesForMedicines(array $medicineIds): void
    {
        foreach ($medicineIds as $id) {
            $medicine = new static(['id' => $id]);
            $medicine->exists = true;
            $medicine->clearCaches();
        }
        
        // Clear global medicine options
        Cache::forget('medicine_options_v2');
    }

    /**
     * Boot method with optimized cache clearing
     */
    protected static function boot()
    {
        parent::boot();

        static::updated(function ($medicine) {
            // Defer cache clearing to after response
            dispatch(function () use ($medicine) {
                $medicine->clearCaches();
            })->afterResponse();
        });

        static::deleted(function ($medicine) {
            dispatch(function () use ($medicine) {
                $medicine->clearCaches();
            })->afterResponse();
        });
    }

    /**
     * Check if medicine has stock (cached with shorter TTL)
     */
    public function hasStock(): bool
    {
        return Cache::remember("medicine:{$this->id}:has_stock", 180, function () {
            return DB::table('supplier_medicines')
                ->where('medicine_id', $this->id)
                ->where('is_available', true)
                ->where('stock_quantity', '>', 0)
                ->exists();
        });
    }

    /**
     * Get medicine with cheapest price (for listings)
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