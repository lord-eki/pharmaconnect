<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\HasAuditLog;
use App\Traits\Traits\HasNotifications;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles , HasAuditLog , HasNotifications;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
        'phone',
        'first_name',
        'last_name',
        'profile_image',
        'is_active',
        'last_login_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'two_factor_recovery_codes' => 'array',
            'password' => 'hashed',
        ];
    }


    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        $panelId = $panel->getId();

        switch ($panelId) {
            case 'Admin':
                return $this->hasRole('Admin');
            case 'Supplier':
                return $this->hasRole('Supplier');
            case 'Insurer':
                return $this->hasRole('Insurer');
            case 'Operation':
                return $this->hasRole('Operation');
            case 'Physician':
                return $this->hasRole('Physician');
            default:
                return false;
        }
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'physician_id');
    }

    public function supplier(): HasOne
    {
        return $this->hasOne(Supplier::class);
    }

    public function insuranceProvider(): HasOne
    {
        return $this->hasOne(InsuranceProvider::class);
    }

    public function rider(): HasOne
    {
        return $this->hasOne(Rider::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
