<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receivable extends Model
{
    protected $fillable = [
        'reference', 'order_id', 'prescription_id', 'patient_id',
        'insurance_provider_id', 'amount', 'payment_source',
        'claim_status', 'claim_reference', 'claim_submitted_at',
        'received_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'claim_submitted_at' => 'date',
        'received_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'transactionable');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class);
    }
}
