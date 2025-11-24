<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'reference', 'amount', 'currency', 'type','tranasactionable_id', 'transactionable_type',
        'status', 'completed_at', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function transactionable()
    {
        return $this->morphTo();
    }
}
