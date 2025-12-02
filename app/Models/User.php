<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\HasAuditLog;
use App\Traits\HasNotifications;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasEmailAuthentication 
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles , HasAuditLog , Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
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
        'has_email_authentication',
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
            'has_email_authentication' => 'boolean',
        ];
    }


        public function hasEmailAuthentication(): bool
    {
        // This method should return true if the user has enabled email authentication.
        
        return $this->has_email_authentication;
    }

    public function toggleEmailAuthentication(bool $condition): void
    {
        // This method should save whether or not the user has enabled email authentication.
    
        $this->has_email_authentication = $condition;
        $this->save();
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
            case 'Rider':
                return $this->hasRole('Rider');
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

    public function physician(): HasOne
    {
        return $this->hasOne(Physician::class);
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
