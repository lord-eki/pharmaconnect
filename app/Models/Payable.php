<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payable extends Model
{
    protected $fillable = [
        'reference', 'order_id', 'vendor_id', 'vendor_type',
        'amount', 'payment_method', 'gateway_reference',
        'due_date', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
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
}
