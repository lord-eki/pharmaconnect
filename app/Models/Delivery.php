<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_number',
        'order_id',
        'rider_id',
        'pickup_address',
        'delivery_address',
        'pickup_latitude',
        'pickup_longitude',
        'delivery_latitude',
        'delivery_longitude',
        'estimated_distance_km',
        'delivery_fee',
        'status',
        'scheduled_pickup',
        'actual_pickup',
        'estimated_delivery',
        'actual_delivery',
        'delivery_notes',
        'recipient_name',
        'recipient_phone',
        'proof_of_delivery',
    ];

    protected $casts = [
        'pickup_latitude' => 'decimal:8',
        'pickup_longitude' => 'decimal:8',
        'delivery_latitude' => 'decimal:8',
        'delivery_longitude' => 'decimal:8',
        'estimated_distance_km' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'scheduled_pickup' => 'datetime',
        'actual_pickup' => 'datetime',
        'estimated_delivery' => 'datetime',
        'actual_delivery' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    public function tracking(): HasMany
    {
        return $this->hasMany(DeliveryTracking::class);
    }
}
