<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
     use HasFactory;

    protected $fillable = [
        'physician_id',
        'patient_number',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'phone',
        'email',
        'address',
        'county',
        'city',
        'emergency_contact_name',
        'emergency_contact_phone',
        'insurance_number',
        'insurance_provider',
        'allergies',
        'medical_conditions',
        'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

     protected static function boot()
    {
        parent::boot();

        static::creating(function ($patient) {
            if (!$patient->patient_number) {
                $patient->patient_number = static::generatePatientNumber();
            }
            
            if (!$patient->physician_id) {
                $patient->physician_id = auth()->id();
            }
        });
    }

    public function physician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'physician_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function insuranceClaims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class);
    }
 // Generate unique patient number
    public static function generatePatientNumber(): string
    {
        $prefix = 'PT';
        $year = date('Y');
        
        $lastPatient = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPatient && preg_match('/\d+$/', $lastPatient->patient_number, $matches)) {
            $sequence = intval($matches[0]) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s%s%06d', $prefix, $year, $sequence);
    }

    // Accessor for full name
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Accessor for age
    public function getAgeAttribute(): int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : 0;
    }

    // Check if patient has insurance
    public function hasInsurance(): bool
    {
        return !empty($this->insurance_number) && !empty($this->insurance_provider);
    }

    // Get active prescriptions
    public function activePrescriptions()
    {
        return $this->prescriptions()
            ->whereIn('status', ['submitted', 'processing'])
            ->orderBy('prescribed_at', 'desc');
    }

    // Scope for active patients
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
