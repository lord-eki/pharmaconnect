<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountOverview extends Model
{
     protected $table = 'account_overviews_view';
    
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';
    
    // Since it's a view, disable write operations
    public static function boot()
    {
        parent::boot();
        
        static::saving(function ($model) {
            return false;
        });
        
        static::deleting(function ($model) {
            return false;
        });
    }
    
    protected $casts = [
        'date' => 'datetime',
        'amount_in' => 'decimal:2',
        'amount_out' => 'decimal:2',
        'is_completed' => 'boolean',
    ];
}
