<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_number',
        'prescription_id',
        'insurance_provider_id',
        'patient_id',
        'policy_number',
        'claimed_amount',
        'approved_amount',
        'deductible_amount',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'rejection_reason',
        'notes',
        'pdf_path',
        'pdf_generated_at',
    ];

    protected $casts = [
        'claimed_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'deductible_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'pdf_generated_at' => 'datetime',
    ];

    /**
     * Boot method to auto-generate claim number
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($claim) {
            if (empty($claim->claim_number)) {
                $claim->claim_number = self::generateClaimNumber();
            }
        });
    }

    /**
     * Generate unique claim number
     */
    public static function generateClaimNumber(): string
    {
        $year = date('Y');
        $lastClaim = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastClaim ? ((int) substr($lastClaim->claim_number, -6)) + 1 : 1;

        return 'CLM-'.$year.'-'.str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function externalOrder(): BelongsTo
    {
        return $this->belongsTo(ExternalOrder::class, 'external_order_id');
    }

    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scopes
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeForProvider($query, int $providerId)
    {
        return $query->where('insurance_provider_id', $providerId);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function getPdfUrlAttribute(): ?string
    {
        if (! $this->pdf_path) {
            return null;
        }

        return \Storage::disk('public')->url($this->pdf_path);
    }

    public function hasPdf(): bool
    {
        return $this->pdf_path && \Storage::disk('public')->exists($this->pdf_path);
    }

    public function deletePdf(): bool
    {
        if ($this->hasPdf()) {
            \Storage::disk('public')->delete($this->pdf_path);
            $this->update([
                'pdf_path' => null,
                'pdf_generated_at' => null,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Accessors & Helpers
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'submitted' => 'primary',
            'under_review' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'paid' => 'info',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }

    public function getNetAmountAttribute(): float
    {
        if ($this->approved_amount) {
            return $this->approved_amount - $this->deductible_amount;
        }

        return $this->claimed_amount - $this->deductible_amount;
    }

    /**
     * Check if claim can be edited
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['submitted', 'under_review']);
    }

    /**
     * Check if claim can be approved
     */
    public function canBeApproved(): bool
    {
        return in_array($this->status, ['submitted', 'under_review']);
    }

    /**
     * Check if claim can be rejected
     */
    public function canBeRejected(): bool
    {
        return in_array($this->status, ['submitted', 'under_review']);
    }

    /**
     * Approve the claim
     */
    public function approve(float $approvedAmount, ?string $notes = null, ?int $reviewedBy = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_amount' => $approvedAmount,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewedBy ?? auth()->id(),
            'notes' => $notes ?? $this->notes,
        ]);
    }

    /**
     * Reject the claim
     */
    public function reject(string $reason, ?int $reviewedBy = null): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewedBy ?? auth()->id(),
        ]);
    }

    /**
     * Mark claim as paid
     */
    public function markAsPaid(): void
    {
        if ($this->status !== 'approved') {
            throw new \Exception('Only approved claims can be marked as paid.');
        }

        $this->update([
            'status' => 'paid',
        ]);
    }

    /**
     * Submit claim for review
     */
    public function submitForReview(): void
    {
        if ($this->status !== 'submitted') {
            throw new \Exception('Only submitted claims can be moved to review.');
        }

        $this->update([
            'status' => 'under_review',
        ]);
    }
}
