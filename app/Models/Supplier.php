<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'registration_number',
        'license_number',
        'contact_person',
        'phone',
        'email',
        'address',
        'county',
        'city',
        'postal_code',
        'bank_account_name',
        'bank_account_number',
        'bank_name',
        'bank_branch',
        'tax_pin',
        'is_verified',
        'is_active',
        'rating',
        'fulfillment_sla_hours',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'rating' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function medicines(): BelongsToMany
    {
        return $this->belongsToMany(Medicine::class, 'supplier_medicines')
            ->withPivot('unit_price', 'stock_quantity', 'minimum_order_quantity', 'expiry_date', 'batch_number', 'is_available', 'last_updated')
            ->withTimestamps();
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function notifications() : HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
