<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_number',
        'prescription_id',
        'supplier_id',
        'total_amount',
        'status',
        'valid_until',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'valid_until' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($quotation) {
            if (! $quotation->quotation_number) {
                $quotation->quotation_number = static::generateQuotationNumber();
            }
        });
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    // Generate unique quotation number
    public static function generateQuotationNumber(): string
    {
        $prefix = 'QT';
        $year = date('Y');
        $month = date('m');
        $ym = $year.$month;

        $cacheKey = "last_quotation_{$ym}";
        $lockKey = "lock_{$cacheKey}";

        return Cache::lock($lockKey, 5)->block(5, function () use ($cacheKey, $prefix, $ym, $year, $month) {
            $lastSequence = Cache::get($cacheKey, 0);

            if ($lastSequence === 0) {
                // Only query DB if cache is empty
                $lastQuotation = static::select('quotation_number')
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($lastQuotation && preg_match('/(\d{5})$/', $lastQuotation->quotation_number, $matches)) {
                    $lastSequence = (int) $matches[1];
                }
            }

            $sequence = $lastSequence + 1;

            if ($sequence > 99999) {
                Log::error('Quotation sequence overflow', [
                    'year_month' => $ym,
                    'sequence' => $sequence,
                ]);
                throw new \Exception('Monthly quotation limit reached. Please contact support.');
            }

            Cache::put($cacheKey, $sequence, 3600);

            return sprintf('%s%s-%05d', $prefix, $ym, $sequence);
        });
    }

    // Calculate total amount from items
    public function calculateTotal(): void
    {
        $total = $this->items()->sum('total_price');

        if ($this->total_amount != $total) {
            $this->total_amount = $total;
            $this->saveQuietly();
        }
    }

    // Price optimization algorithm
    public function optimizePricing(): void
    {
        DB::transaction(function () {
            // Group items by prescription item
            $prescriptionItems = $this->prescription->items;

            foreach ($prescriptionItems as $prescriptionItem) {
                // Get all quotation items for this prescription item
                $quotationItems = $this->items()
                    ->where('prescription_item_id', $prescriptionItem->id)
                    ->orderBy('total_price', 'asc')
                    ->get();

                if ($quotationItems->isEmpty()) {
                    continue;
                }

                // Select the best price (lowest)
                $bestQuotation = $quotationItems->first();

                // Apply pricing rules and markup
                $finalPrice = $this->applyPricingRules($bestQuotation);

                // Update prescription item with selected price
                $prescriptionItem->update([
                    'unit_price' => $finalPrice / $prescriptionItem->quantity,
                    'total_price' => $finalPrice,
                    'status' => 'quoted',
                ]);
            }

            // Update quotation status
            $this->status = 'sent';
            $this->save();

            // Update prescription status
            $this->prescription->update(['status' => 'processing']);

            // Recalculate totals
            $this->prescription->updateTotalAmount();
            $this->calculateTotal();
        });
    }

    // Apply pricing rules (markup, margins, etc.)
    protected function applyPricingRules(QuotationItem $item): float
    {
        $basePrice = $item->total_price;

        // Get active pricing rules
        $pricingRules = PricingRule::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            })
            ->orderBy('priority', 'desc')
            ->get();

        $finalPrice = $basePrice;

        foreach ($pricingRules as $rule) {
            // Check if rule conditions are met
            if ($this->checkRuleConditions($rule, $item)) {
                switch ($rule->rule_type) {
                    case 'markup_percentage':
                        $markup = $basePrice * ($rule->markup_percentage / 100);
                        $finalPrice = $basePrice + $markup;
                        break;

                    case 'fixed_amount':
                        $finalPrice = $basePrice + $rule->fixed_amount;
                        break;

                    case 'tier_based':
                        // Implement tier-based pricing logic
                        $finalPrice = $this->applyTierPricing($basePrice, $rule);
                        break;

                    case 'volume_discount':
                        // Implement volume discount logic
                        $finalPrice = $this->applyVolumeDiscount($basePrice, $item->quantity, $rule);
                        break;
                }

                // Apply margin constraints
                if ($rule->minimum_margin) {
                    $minimumPrice = $basePrice * (1 + ($rule->minimum_margin / 100));
                    $finalPrice = max($finalPrice, $minimumPrice);
                }

                if ($rule->maximum_margin) {
                    $maximumPrice = $basePrice * (1 + ($rule->maximum_margin / 100));
                    $finalPrice = min($finalPrice, $maximumPrice);
                }

                break; // Use first matching rule (highest priority)
            }
        }

        return round($finalPrice, 2);
    }

    // Check if rule conditions are met
    protected function checkRuleConditions(PricingRule $rule, QuotationItem $item): bool
    {
        $conditions = $rule->conditions;

        if (empty($conditions)) {
            return true;
        }

        // Implement condition checking logic based on the  business rules
        // Example: Check medicine category, supplier rating, order value, etc.

        return true;
    }

    // Apply tier-based pricing
    protected function applyTierPricing(float $basePrice, PricingRule $rule): float
    {
        // Implement tier logic from conditions
        $conditions = $rule->conditions;

        if (isset($conditions['tiers'])) {
            foreach ($conditions['tiers'] as $tier) {
                if ($basePrice >= $tier['min'] && $basePrice <= $tier['max']) {
                    return $basePrice * (1 + ($tier['markup'] / 100));
                }
            }
        }

        return $basePrice;
    }

    // Apply volume discount
    protected function applyVolumeDiscount(float $basePrice, int $quantity, PricingRule $rule): float
    {
        $conditions = $rule->conditions;

        if (isset($conditions['volume_tiers'])) {
            foreach ($conditions['volume_tiers'] as $tier) {
                if ($quantity >= $tier['min_quantity']) {
                    $discount = $basePrice * ($tier['discount_percentage'] / 100);

                    return $basePrice - $discount;
                }
            }
        }

        return $basePrice;
    }

    // Generate order from accepted quotation
    public function generateOrder(): Order
    {
        return DB::transaction(function () {
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'quotation_id' => $this->id,
                'supplier_id' => $this->getOptimalSupplier(),
                'prescription_id' => $this->prescription_id,
                'total_amount' => $this->total_amount,
                'status' => 'pending',
                'ordered_at' => now(),
            ]);

            // Create order items
            foreach ($this->items as $quotationItem) {
                $order->items()->create([
                    'quotation_item_id' => $quotationItem->id,
                    'medicine_id' => $quotationItem->prescriptionItem->medicine_id,
                    'quantity' => $quotationItem->quantity,
                    'unit_price' => $quotationItem->unit_price,
                    'total_price' => $quotationItem->total_price,
                    'status' => 'pending',
                ]);
            }

            // Update quotation status
            $this->update(['status' => 'accepted']);

            return $order;
        });
    }

    // Get optimal supplier based on price and other factors
    protected function getOptimalSupplier(): int
    {
        // For now, return the supplier with the best overall pricing
        // You can enhance this with supplier rating, delivery time, etc.

        return $this->items()
            ->select('supplier_id')
            ->groupBy('supplier_id')
            ->orderByRaw('SUM(total_price) ASC')
            ->first()
            ->supplier_id;
    }

    // Check if quotation is expired
    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    // Scope for active quotations
    public function scopeActive($query)
    {
        return $query->where('status', 'sent')
            ->where('valid_until', '>', now());
    }
}
