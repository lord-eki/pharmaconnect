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

     // Approve commission
    public function approve(int $approvedBy): bool
    {
        if ($this->status !== 'pending') {
            throw new \Exception('Commission must be pending to approve');
        }

        $this->status = 'approved';
        $this->approved_at = now();
        $this->approved_by = $approvedBy;
        $this->save();

        // Notify physician
        $this->notifyPhysician();

        return true;
    }

    // Process payment of commission
    public function processPayout(string $paymentReference): bool
    {
        if ($this->status !== 'approved') {
            throw new \Exception('Commission must be approved before payout');
        }

        $this->status = 'paid';
        $this->paid_at = now();
        $this->payment_reference = $paymentReference;
        $this->save();

        // Create payment record
        Payment::create([
            'payment_reference' => $paymentReference,
            'payee_id' => $this->physician_id,
            'amount' => $this->commission_amount,
            'currency' => 'KES',
            'payment_method' => 'bank_transfer',
            'status' => 'completed',
            'processed_at' => now(),
            'notes' => "Commission payment for order {$this->order->order_number}",
        ]);

        return true;
    }

    // Notify physician
    protected function notifyPhysician(): void
    {
        // Implement notification logic
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeForPhysician($query, int $physicianId)
    {
        return $query->where('physician_id', $physicianId);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Calculate total commissions for a physician
    public static function totalForPhysician(int $physicianId, string $status = null): float
    {
        $query = static::where('physician_id', $physicianId);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        return $query->sum('commission_amount');
    }

    // Get monthly earnings
    public static function monthlyEarnings(int $physicianId, int $year = null, int $month = null): float
    {
        $year = $year ?? date('Y');
        $month = $month ?? date('m');
        
        return static::where('physician_id', $physicianId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where('status', 'paid')
            ->sum('commission_amount');
    }
}
