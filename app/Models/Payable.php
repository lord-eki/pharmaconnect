<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payable extends Model
{
    protected $fillable = [
        'reference', 'order_id', 'vendor_id', 'vendor_type',
        'amount', 'payment_method', 'gateway_reference',
        'due_date', 'paid_at', 'description', 'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'transactionable');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function scopeSupplierPayables($query)
    {
        return $query->where('vendor_type', 'supplier');
    }

    public function scopeCommissionPayables($query)
    {
        return $query->where('vendor_type','physician');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereNull('paid_at');
    }

    public function scopeOverdue($query)
    {
        return $query->whereNull('paid_at')->where('due_date' ,'<', now());
    }

    /**
     * Format amount for display
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'KES ' . number_format($this->amount, 2);
    }

    /**
     * Check if payable is paid
     */
    public function getIsPaidAttribute(): bool
    {
        return !is_null($this->paid_at);
    }

    /**
     * Check if payable is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        if ($this->is_paid || !$this->due_date) {
            return false;
        }

        return $this->due_date->isPast();
    }

    /**
     * Get days until due
     */
    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->due_date || $this->is_paid) {
            return null;
        }

        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Get days overdue
     */
    public function getDaysOverdueAttribute(): ?int
    {
        if (!$this->is_overdue) {
            return null;
        }

        return now()->diffInDays($this->due_date);
    }

    /**
     * Get status label
     */
    public function getStatusAttribute(): string
    {
        if ($this->is_paid) {
            return 'paid';
        }

        if ($this->is_overdue) {
            return 'overdue';
        }

        return 'pending';
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'success',
            'overdue' => 'danger',
            'pending' => 'warning',
            default => 'gray',
        };
    }
}