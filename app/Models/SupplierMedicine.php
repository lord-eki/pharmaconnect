<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierMedicine extends Model
{
    use HasFactory;

    protected $table = 'supplier_medicines';

    protected $fillable = [
        'supplier_id',
        'medicine_id',
        'unit_price',
        'stock_quantity',
        'minimum_order_quantity',
        'expiry_date',
        'batch_number',
        'is_available',
        'last_updated',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'expiry_date' => 'date',
        'is_available' => 'boolean',
        'last_updated' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }
}
