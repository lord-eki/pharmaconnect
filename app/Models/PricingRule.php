<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'rule_type',
        'conditions',
        'markup_percentage',
        'fixed_amount',
        'minimum_margin',
        'maximum_margin',
        'is_active',
        'priority',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'conditions' => 'array',
        'markup_percentage' => 'decimal:2',
        'fixed_amount' => 'decimal:2',
        'minimum_margin' => 'decimal:2',
        'maximum_margin' => 'decimal:2',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')
                  ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', now());
            });
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}
