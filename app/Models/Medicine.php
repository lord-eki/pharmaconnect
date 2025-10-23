<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function getCheapestSupplierPrice(?int $quantity = null)
    {
        $query = DB::table('supplier_medicines')
            ->where('medicine_id', $this->id)
            ->where('is_available', true);

        if ($quantity) {
            $query->where('stock_quantity', '>=', $quantity);
        }

        return $query->orderBy('unit_price', 'asc')
            ->value('unit_price');
    }
}
