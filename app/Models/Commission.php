<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{

    protected $fillable = [
        'physician_id',
        'prescription_id',
        'order_id',
        'commission_rate',
        'gross_amount',
        'commission_amount',
        'status',
        'approved_at',
        'approved_by',
        'paid_at',
        'payment_reference',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($commission) {
            if ($commission->gross_amount && $commission->commission_rate) {
                $commission->commission_amount = ($commission->gross_amount * $commission->commission_rate) / 100;
            }
        });
    }

    public function physician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'physician_id');
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
