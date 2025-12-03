<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentComment extends Model
{
    protected $fillable = [
        'document_id',
        'user_id',
        'comment',
        'parent_id',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(DocumentComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(DocumentComment::class, 'parent_id');
    }

    public function scopeRootComments($query)
    {
        return $query->whereNull('parent_id');
    }
}
