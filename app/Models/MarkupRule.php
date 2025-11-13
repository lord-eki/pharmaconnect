<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarkupRule extends Model
{
    protected $fillable = [
        'name',
        'type',
        'markup_percentage',
        'fixed_amount',
        'tiers',
        'priority',
        'is_active',
        'valid_from',
        'valid_until',
        'conditions',
    ];

    protected $casts = [
        'tiers' => 'array',
        'conditions' => 'array',
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'markup_percentage' => 'decimal:2',
        'fixed_amount' => 'decimal:2',
    ];

    /**
     * Get the markup rules ordered by priority
     */

    public static function getActiveRules()
    {
        return self::where('is_active',true)->where(function($query){
            $query->whereNull('valid_from')->orWhere('valid_from','<=',now());
        })->where(function($query){
            $query->whereNull('valid_until')->orWhere('valid_until','>=',now());
        })->orderBy('priority','desc')->get();
    }


    /**
     * Calculate markup for a given price
     */
    public function calculateMarkup(float $basePrice, array $context = []): float
    {
        if (!$this->meetsConditions($context)) {
            return 0;
        }

        switch ($this->type) {
            case 'percentage':
                return $basePrice * ($this->markup_percentage / 100);
            
            case 'fixed_amount':
                return $this->fixed_amount;
            
            case 'tiered':
                return $this->calculateTieredMarkup($basePrice);
            
            default:
                return 0;
        }
    }

    /**
     * Check if conditions are met
     */
    protected function meetsConditions(array $context): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        // Check medicine category
        if (isset($this->conditions['categories']) && isset($context['category'])) {
            if (!in_array($context['category'], $this->conditions['categories'])) {
                return false;
            }
        }

        // Check price range
        if (isset($this->conditions['min_price']) && isset($context['price'])) {
            if ($context['price'] < $this->conditions['min_price']) {
                return false;
            }
        }

        if (isset($this->conditions['max_price']) && isset($context['price'])) {
            if ($context['price'] > $this->conditions['max_price']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate tiered markup
     */
    protected function calculateTieredMarkup(float $basePrice): float
    {
        if (empty($this->tiers)) {
            return 0;
        }

        foreach ($this->tiers as $tier) {
            if ($basePrice >= $tier['min'] && $basePrice <= $tier['max']) {
                return $basePrice * ($tier['markup_percentage'] / 100);
            }
        }

        return 0;
    }
}
