<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Physician extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'license_number',
        'license_expiry_date',
        'medical_council_registration',
        'specialization',
        'years_experience',
        'qualification_level',
        'practice_name',
        'practice_address',
        'county',
        'city',
        'postal_code',
        'practice_phone',
        'practice_email',
        'practice_type',
        'verification_status',
        'verified_at',
        'verified_by',
        'verification_notes',
        'document_path',
        'commission_rate',
        'total_commissions_earned',
        'total_prescriptions',
        'total_fulfilled_prescriptions',
        'prescription_preferences',
        'allow_generic_substitution',
        'require_patient_consent',
        'notification_preferences',
        'is_active',
        'accepting_prescriptions',
        'practice_start_time',
        'practice_end_time',
        'working_days',
    ];

    protected $casts = [
        'license_expiry_date' => 'date',
        'verified_at' => 'datetime',
        'commission_rate' => 'decimal:2',
        'total_commissions_earned' => 'decimal:2',
        'prescription_preferences' => 'array',
        'notification_preferences' => 'array',
        'working_days' => 'array',
        'allow_generic_substitution' => 'boolean',
        'require_patient_consent' => 'boolean',
        'is_active' => 'boolean',
        'accepting_prescriptions' => 'boolean',
        'practice_start_time' => 'datetime:H:i',
        'practice_end_time' => 'datetime:H:i',
    ];

    protected $attributes = [
        'commission_rate' => 5.00,
        'total_commissions_earned' => 0,
        'total_prescriptions' => 0,
        'total_fulfilled_prescriptions' => 0,
        'allow_generic_substitution' => true,
        'require_patient_consent' => true,
        'is_active' => true,
        'accepting_prescriptions' => true,
    ];

    /**
     * Get the user that owns the physician profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who verified this physician.
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get all prescriptions created by this physician.
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'physician_id', 'user_id');
    }

    /**
     * Get all patients treated by this physician.
     */
    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class, 'physician_id', 'user_id');
    }

    /**
     * Get all commissions earned by this physician.
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class, 'physician_id', 'user_id');
    }

    /**
     * Scope to get only verified physicians.
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    /**
     * Scope to get only active physicians.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get physicians accepting prescriptions.
     */
    public function scopeAcceptingPrescriptions($query)
    {
        return $query->where('accepting_prescriptions', true);
    }

    /**
     * Scope to get physicians by location.
     */
    public function scopeByLocation($query, $county = null, $city = null)
    {
        if ($county) {
            $query->where('county', $county);
        }
        if ($city) {
            $query->where('city', $city);
        }
        return $query;
    }

    /**
     * Scope to get physicians by specialization.
     */
    public function scopeBySpecialization($query, $specialization)
    {
        return $query->where('specialization', $specialization);
    }

    /**
     * Check if physician is currently within working hours.
     */
    public function isWithinWorkingHours(): bool
    {
        if (!$this->practice_start_time || !$this->practice_end_time || !$this->working_days) {
            return true; // Default to available if no schedule set
        }

        $now = now();
        $currentDay = strtolower($now->format('l')); // Get current day name
        $currentTime = $now->format('H:i:s');

        // Check if today is a working day
        if (!in_array($currentDay, $this->working_days)) {
            return false;
        }

        // Check if current time is within working hours
        return $currentTime >= $this->practice_start_time && $currentTime <= $this->practice_end_time;
    }

    /**
     * Get physician's success rate (fulfilled vs total prescriptions).
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_prescriptions == 0) {
            return 0;
        }
        
        return round(($this->total_fulfilled_prescriptions / $this->total_prescriptions) * 100, 2);
    }

    /**
     * Get physician's average commission per prescription.
     */
    public function getAverageCommissionPerPrescriptionAttribute(): float
    {
        if ($this->total_fulfilled_prescriptions == 0) {
            return 0;
        }
        
        return round($this->total_commissions_earned / $this->total_fulfilled_prescriptions, 2);
    }

    /**
     * Check if physician's license is expired or expiring soon.
     */
    public function isLicenseExpiring(int $daysThreshold = 30): bool
    {
        if (!$this->license_expiry_date) {
            return false;
        }

        return $this->license_expiry_date->diffInDays(now()) <= $daysThreshold;
    }

    /**
     * Check if physician's license is expired.
     */
    public function isLicenseExpired(): bool
    {
        if (!$this->license_expiry_date) {
            return false;
        }

        return $this->license_expiry_date->isPast();
    }

    /**
     * Update prescription statistics.
     */
    public function updatePrescriptionStats(): void
    {
        $this->total_prescriptions = $this->prescriptions()->count();
        $this->total_fulfilled_prescriptions = $this->prescriptions()
            ->where('status', 'fulfilled')
            ->count();
        
        $this->save();
    }

    /**
     * Update commission earnings.
     */
    public function updateCommissionEarnings(): void
    {
        $this->total_commissions_earned = $this->commissions()
            ->where('status', 'paid')
            ->sum('commission_amount');
        
        $this->save();
    }

    /**
     * Get the full name of the physician.
     */
    public function getFullNameAttribute(): string
    {
        return $this->user->first_name . ' ' . $this->user->last_name;
    }

    /**
     * Get the physician's qualification display.
     */
    public function getQualificationDisplayAttribute(): string
    {
        $qualifications = [
            'diploma' => 'Diploma',
            'degree' => 'Degree',
            'masters' => 'Master\'s',
            'phd' => 'PhD',
            'fellowship' => 'Fellowship'
        ];

        return $qualifications[$this->qualification_level] ?? 'Not specified';
    }
}
