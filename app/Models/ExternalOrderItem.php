<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalOrderItem extends Model
{
    protected $fillable = [
        'external_order_id',
        'medicine_id',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($item) {
            // Update parent external order total
            if ($item->externalOrder) {
                $item->externalOrder->updateTotalAmount();
            }
        });

        static::deleted(function ($item) {
            // Update parent external order total
            if ($item->externalOrder) {
                $item->externalOrder->updateTotalAmount();
            }
        });
    }

    /**
     * Relationships
     */
    public function externalOrder(): BelongsTo
    {
        return $this->belongsTo(ExternalOrder::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }
}
