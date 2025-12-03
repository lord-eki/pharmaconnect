<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimForm extends Model
{
    protected $fillable = [
        'form_number',
        'prescription_id',
        'insurance_provider_id',
        'patient_id',
        'physician_id',
        'form_data',
        'diagnosis',
        'treatment_notes',
        'submission_type',
        'status',
        'submitted_at',
        'document_id',
        'physician_signature',
        'patient_signature',
        'signed_at',
    ];

    protected $casts = [
        'form_data' => 'array',
        'submitted_at' => 'datetime',
        'signed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($claimForm) {
            if (empty($claimForm->form_number)) {
                $claimForm->form_number = self::generateFormNumber();
            }
        });
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function physician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'physician_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public static function generateFormNumber(): string
    {
        $prefix = 'CLM';
        $date = now()->format('Ymd');
        $lastForm = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastForm ? (int) substr($lastForm->form_number, -4) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    public function submit(): bool
    {
        $this->status = 'submitted';
        $this->submitted_at = now();
        return $this->save();
    }

    public function approve(): bool
    {
        $this->status = 'approved';
        return $this->save();
    }

    public function reject(): bool
    {
        $this->status = 'rejected';
        return $this->save();
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeOnline($query)
    {
        return $query->where('submission_type', 'online');
    }

    public function scopeManual($query)
    {
        return $query->where('submission_type', 'manual');
    }
}
