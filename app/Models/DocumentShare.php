<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentShare extends Model
{
    protected $fillable = [
        'document_id',
        'shared_by',
        'shared_with',
        'permission',
        'expires_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    public function sharedWith(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function canView(): bool
    {
        return in_array($this->permission, ['view', 'download', 'edit']) && $this->is_active && !$this->isExpired();
    }

    public function canDownload(): bool
    {
        return in_array($this->permission, ['download', 'edit']) && $this->is_active && !$this->isExpired();
    }

    public function canEdit(): bool
    {
        return $this->permission === 'edit' && $this->is_active && !$this->isExpired();
    }

    public function revoke(): bool
    {
        $this->is_active = false;
        return $this->save();
    }
}
