<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delivery extends Model
{
    use HasFactory;

   protected $fillable = [
        'delivery_number',
        'prescription_id',  
        'external_order_id',
        'order_id',         
        'rider_id',
        'pickup_address',
        'pickup_locations', 
        'delivery_address',
        'pickup_latitude',
        'pickup_longitude',
        'delivery_latitude',
        'delivery_longitude',
        'estimated_distance_km',
        'delivery_fee',
        'status',
        'order_statuses',   
        'scheduled_pickup',
        'actual_pickup',
        'estimated_delivery',
        'actual_delivery',
        'delivery_notes',
        'recipient_name',
        'recipient_phone',
        'proof_of_delivery',
        'delivery_note_document_id',
    ];

    protected $casts = [
        'pickup_locations' => 'array',  
        'order_statuses' => 'array',    
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

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'delivery_order')
            ->withPivot(['pickup_status', 'picked_up_at', 'pickup_notes'])
            ->withTimestamps();
    }

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

    public function deliveryNoteDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'delivery_note_document_id');
    }

    /**
     * Get all suppliers involved in this delivery
     */
    public function getSuppliers()
    {
        return $this->orders->map(fn($order) => $order->supplier)->unique('id');
    }

    /**
     * Get total value of all orders in delivery
     */
    public function getTotalAmountAttribute(): float
    {
        return $this->orders->sum('total_amount');
    }

    /**
     * Get count of confirmed orders
     */
    public function getConfirmedOrdersCountAttribute(): int
    {
        return $this->orders->where('status', 'confirmed')->count();
    }

    /**
     * Get count of pending orders
     */
    public function getPendingOrdersCountAttribute(): int
    {
        return $this->orders->whereNotIn('status', ['confirmed', 'delivered'])->count();
    }

    /**
     * Check if all orders have been confirmed
     */
    public function allOrdersConfirmed(): bool
    {
        return $this->orders()
            ->whereNotIn('status', ['confirmed', 'delivered'])
            ->doesntExist();
    }

    /**
     * Check if all orders have been picked up
     */
    public function allOrdersPickedUp(): bool
    {
        $statuses = $this->order_statuses ?? [];
        
        if (empty($statuses)) {
            return false;
        }

        foreach ($statuses as $orderStatus) {
            if ($orderStatus['pickup_status'] !== 'picked_up') {
                return false;
            }
        }

        return true;
    }

    /**
     * Mark an order as picked up
     */
    public function markOrderPickedUp(int $orderId, ?string $notes = null): void
    {
        $this->orders()->updateExistingPivot($orderId, [
            'pickup_status' => 'picked_up',
            'picked_up_at' => now(),
            'pickup_notes' => $notes,
        ]);

        // Update order statuses JSON
        $statuses = $this->order_statuses ?? [];
        $statuses[$orderId] = [
            'pickup_status' => 'picked_up',
            'picked_up_at' => now()->toIso8601String(),
        ];
        
        $this->update(['order_statuses' => $statuses]);

        \Illuminate\Support\Facades\Log::info('Order marked as picked up', [
            'delivery_id' => $this->id,
            'order_id' => $orderId,
            'all_picked_up' => $this->allOrdersPickedUp(),
        ]);

        // If all orders picked up, update delivery status
        if ($this->allOrdersPickedUp()) {
            $this->update([
                'status' => 'in_transit',
                'actual_pickup' => now(),
            ]);

            \Illuminate\Support\Facades\Log::info('All orders picked up - delivery in transit', [
                'delivery_id' => $this->id,
            ]);
        }
    }

    public static function generateDeliveryNumber(): string
    {
        $prefix = 'DEL';
        $year = date('Y');
        $month = date('m');
        $ym = $year . $month;

        $lastDelivery = static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastDelivery && preg_match('/(\d{5})$/', $lastDelivery->delivery_number, $matches)) {
            $sequence = (int)$matches[1] + 1;
        }

        return sprintf('%s%s-%s', $prefix, $ym, str_pad($sequence, 5, '0', STR_PAD_LEFT));
    }

    /**
     * Check if delivery note has been generated
     */
    public function hasDeliveryNote(): bool
    {
        return !is_null($this->delivery_note_document_id);
    }

    /**
     * Get the delivery note URL if it exists
     */
    public function getDeliveryNoteUrl(): ?string
    {
        if (!$this->hasDeliveryNote()) {
            return null;
        }

        return $this->deliveryNoteDocument->getDownloadUrl();
    }
}
