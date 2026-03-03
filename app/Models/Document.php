<?php

namespace App\Models;

use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory ,SoftDeletes , HasAuditLog;

    protected $fillable = [
        'document_number',
        'category_id',
        'document_type',
        'title',
        'description',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'file_hash',
        'prescription_id',
        'order_id',
        'insurance_claim_id',
        'supplier_id',
        'insurance_provider_id',
        'patient_id',
        'uploaded_by',
        'uploaded_at',
        'verification_status',
        'verified_by',
        'verified_at',
        'verification_notes',
        'is_locked',
        'tags',
        'version',
        'parent_document_id',
        'metadata',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'verified_at' => 'datetime',
        'metadata' => 'array',
        'tags' => 'array',
        'is_locked' => 'boolean',
        'file_size' => 'integer',
        'version' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($document) {
            if (empty($document->document_number)) {
                $document->document_number = self::generateDocumentNumber();
            }
            if (empty($document->uploaded_at)) {
                $document->uploaded_at = now();
            }
        });

        static::deleting(function ($document) {
            if ($document->isForceDeleting() && Storage::disk('local')->exists('private/'.$document->file_path)) {
                Storage::disk('local')->move('private/'.$document->file_path, 'private/archives/'.$document->file_path);
            }
        });
    }

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function insuranceClaim(): BelongsTo
    {
        return $this->belongsTo(InsuranceClaim::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function parentDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'parent_document_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(Document::class, 'parent_document_id');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(DocumentAccessLog::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(DocumentShare::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(DocumentComment::class);
    }

    public function versionHistory(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    public function scopePending($query)
    {
        return $query->where('verification_status', 'pending');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeForPrescription($query, $prescriptionId)
    {
        return $query->where('prescription_id', $prescriptionId);
    }

    public function scopeForOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeForClaim($query, $claimId)
    {
        return $query->where('insurance_claim_id', $claimId);
    }

    // Helper Methods
    public static function generateDocumentNumber(): string
    {
        $prefix = 'DOC';
        $date = now()->format('Ymd');
        $lastDocument = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastDocument ? (int) substr($lastDocument->document_number, -4) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    public function getFileUrl(): string
    {
        return route('documents.preview', $this->id);
    }

    public function getDownloadUrl(): string
    {
        return route('documents.download', $this->id);
    }

    public function getFileSizeFormatted(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function canBeViewed(): bool
    {
        return $this->isImage() || $this->isPdf();
    }

    public function verify(User $user, ?string $notes = null): bool
    {
        $this->verification_status = 'verified';
        $this->verified_by = $user->id;
        $this->verified_at = now();
        $this->verification_notes = $notes;

        return $this->save();
    }

    public function reject(User $user, string $notes): bool
    {
        $this->verification_status = 'rejected';
        $this->verified_by = $user->id;
        $this->verified_at = now();
        $this->verification_notes = $notes;

        return $this->save();
    }

    public function archive(): bool
    {
        $this->status = 'archived';

        return $this->save();
    }

    public function lock(): bool
    {
        $this->is_locked = true;

        return $this->save();
    }

    public function unlock(): bool
    {
        $this->is_locked = false;

        return $this->save();
    }

    public function logAccess(User $user, string $action, array $metadata = []): void
    {
        DocumentAccessLog::create([
            'document_id' => $this->id,
            'user_id' => $user->id,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
            'accessed_at' => now(),
        ]);
    }

    public function shareWith(User $sharedWith, User $sharedBy, string $permission = 'view', $expiresAt = null): DocumentShare
    {
        return DocumentShare::create([
            'document_id' => $this->id,
            'shared_with' => $sharedWith->id,
            'shared_by' => $sharedBy->id,
            'permission' => $permission,
            'expires_at' => $expiresAt,
        ]);
    }

    public function createVersion(string $filePath, int $fileSize, User $user, ?string $notes = null): Document
    {
        $newVersion = $this->replicate();
        $newVersion->file_path = $filePath;
        $newVersion->file_size = $fileSize;
        $newVersion->version = $this->version + 1;
        $newVersion->parent_document_id = $this->id;
        $newVersion->uploaded_by = $user->id;
        $newVersion->uploaded_at = now();
        $newVersion->save();

        // Create version history record
        DocumentVersion::create([
            'document_id' => $this->id,
            'version_number' => $newVersion->version,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'created_by' => $user->id,
            'change_notes' => $notes,
        ]);

        return $newVersion;
    }

    public function hasAccess(User $user): bool
    {
        // Uploader always has access
        if ($this->uploaded_by === $user->id) {
            return true;
        }

        // Check if document is shared with user
        $share = $this->shares()
            ->where('shared_with', $user->id)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();

        if ($share) {
            return true;
        }

        // Role-based access
        return match ($user->role->name) {
            'admin', 'operations' => true,
            'insurer' => $this->insurance_provider_id === $user->userProfile->insurance_provider_id ?? false,
            'supplier' => $this->supplier_id === $user->userProfile->supplier_id ?? false,
            'physician' => $this->prescription?->physician_id === $user->id,
            default => false,
        };
    }
}
