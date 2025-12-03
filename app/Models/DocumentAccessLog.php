<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAccessLog extends Model
{
    protected $fillable = [
        'document_id',
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'metadata',
        'accessed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'accessed_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForDocument($query, $documentId)
    {
        return $query->where('document_id', $documentId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('accessed_at', '>=', now()->subDays($days));
    }
}
