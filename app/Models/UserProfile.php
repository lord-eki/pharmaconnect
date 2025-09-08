<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'license_number',
        'license_expiry_date',
        'practice_name',
        'practice_address',
        'county',
        'city',
        'postal_code',
        'specialization',
        'years_experience',
        'document_path',
        'verification_status',
        'verified_at',
        'verified_by',
        'preferences',
    ];

    protected $casts = [
        'license_expiry_date' => 'date',
        'verified_at' => 'datetime',
        'preferences' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
