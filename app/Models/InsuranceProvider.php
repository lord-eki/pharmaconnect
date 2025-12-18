<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'registration_number',
        'contact_person',
        'phone',
        'email',
        'address',
        'website',
        'is_active',
        'api_endpoint',
        'api_key',
        'form_template',
        'logo_path',
        'form_header',
        'form_footer',
        'required_fields',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'form_template' => 'array',
        'required_fields' => 'array',
    ];

    protected $hidden = [
        'api_key',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class, 'insurance_provider_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'billed_to_id');
    }

    public function claimForms(): HasMany
    {
        return $this->hasMany(ClaimForm::class, 'insurance_provider_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'insurance_provider_id');
    }

    /**
     * Get pending invoices amount
     */
    public function getPendingInvoicesAmountAttribute(): float
    {
        return $this->invoices()
            ->where('status', 'pending')
            ->sum('total_amount');
    }

    /**
     * Get total invoiced amount
     */
    public function getTotalInvoicedAmountAttribute(): float
    {
        return $this->invoices()
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');
    }

    /**
     * Get the form template configuration for this insurer
     */
    public function getFormTemplate(): array
    {
        return $this->form_template ?? $this->getDefaultFormTemplate();
    }

    /**
     * Get default form template structure
     */
    protected function getDefaultFormTemplate(): array
    {
        return [
            'sections' => [
                [
                    'name' => 'Patient Information',
                    'fields' => [
                        ['name' => 'policy_number', 'type' => 'text', 'required' => true, 'label' => 'Policy Number'],
                        ['name' => 'member_id', 'type' => 'text', 'required' => true, 'label' => 'Member ID'],
                        ['name' => 'relationship', 'type' => 'select', 'required' => true, 'label' => 'Relationship to Policy Holder', 'options' => ['self', 'spouse', 'child', 'dependent']],
                    ],
                ],
                [
                    'name' => 'Clinical Information',
                    'fields' => [
                        ['name' => 'icd10_code', 'type' => 'text', 'required' => false, 'label' => 'ICD-10 Code'],
                        ['name' => 'treatment_type', 'type' => 'select', 'required' => true, 'label' => 'Treatment Type', 'options' => ['consultation', 'medication', 'procedure', 'emergency']],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get required fields for this insurer
     */
    public function getRequiredFields(): array
    {
        return $this->required_fields ?? [];
    }

    /**
     * Check if a specific field is required
     */
    public function isFieldRequired(string $fieldName): bool
    {
        $requiredFields = $this->getRequiredFields();

        return in_array($fieldName, $requiredFields);
    }

    /**
     * Get the logo URL for this insurer
     */
    public function getLogoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return \Storage::url($this->logo_path);
    }
}
