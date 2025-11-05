<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rider extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'rider_code',
        'first_name',
        'last_name',
        'phone',
        'email',
        'license_number',
        'vehicle_type',
        'vehicle_registration',
        'base_county',
        'base_city',
        'is_active',
        'is_available',
        'rating',
        'total_deliveries',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_available' => 'boolean',
        'rating' => 'decimal:2',
        'total_deliveries' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeInCounty($query, string $county)
    {
        return $query->where('base_county', $county);
    }

    public function scopeInCity($query, string $city)
    {
        return $query->where('base_city', $city);
    }

    // Methods
    public function updateRating(): void
    {
        $completedDeliveries = $this->deliveries()
            ->where('status', 'delivered')
            ->whereNotNull('rating')
            ->get();

        if ($completedDeliveries->count() > 0) {
            $this->rating = $completedDeliveries->avg('rating');
            $this->save();
        }
    }

    public function incrementDeliveries(): void
    {
        $this->increment('total_deliveries');
    }
}