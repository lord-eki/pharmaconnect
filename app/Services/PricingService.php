<?php

namespace App\Services;

use App\Models\MarkupRule;
use App\Models\Medicine;
use Illuminate\Support\Facades\Log;

class PricingService
{
    /**
     * Calculate final price with markup
     */
    public function calculateFinalPrice(float $supplierPrice, Medicine $medicine, int $quantity = 1): array
    {
        $context = [
            'price' => $supplierPrice,
            'category' => $medicine->category,
            'medicine_id' => $medicine->id,
            'quantity' => $quantity,
        ];

        $markup = $this->calculateMarkup($supplierPrice, $context);
        $finalUnitPrice = $supplierPrice + $markup;
        
        return [
            'supplier_price' => round($supplierPrice, 2),
            'markup_amount' => round($markup, 2),
            'final_unit_price' => round($finalUnitPrice, 2),
            'supplier_total' => round($supplierPrice * $quantity, 2),
            'final_total' => round($finalUnitPrice * $quantity, 2),
            'markup_percentage' => $supplierPrice > 0 ? round(($markup / $supplierPrice) * 100, 2) : 0,
        ];
    }

    /**
     * Calculate markup based on active rules
     */
    protected function calculateMarkup(float $basePrice, array $context): float
    {
        $rules = MarkupRule::getActiveRules();
        
        foreach ($rules as $rule) {
            $markup = $rule->calculateMarkup($basePrice, $context);
            
            if ($markup > 0) {
                Log::info('Markup applied', [
                    'rule' => $rule->name,
                    'base_price' => $basePrice,
                    'markup' => $markup,
                ]);
                
                return $markup; // Return first matching rule
            }
        }

        // Default markup if no rules match (e.g., 15%)
        $defaultMarkup = $basePrice * 0.15;
        
        Log::info('Default markup applied', [
            'base_price' => $basePrice,
            'markup' => $defaultMarkup,
        ]);
        
        return $defaultMarkup;
    }

    /**
     * Get pricing breakdown for transparency (internal use only)
     */
    public function getPricingBreakdown(float $supplierPrice, float $finalPrice): array
    {
        $markup = $finalPrice - $supplierPrice;
        
        return [
            'supplier_price' => $supplierPrice,
            'markup_amount' => $markup,
            'markup_percentage' => $supplierPrice > 0 ? ($markup / $supplierPrice) * 100 : 0,
            'final_price' => $finalPrice,
            'profit_margin' => $finalPrice > 0 ? ($markup / $finalPrice) * 100 : 0,
        ];
    }
}