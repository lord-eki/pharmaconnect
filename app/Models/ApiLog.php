<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'method',
        'url',
        'headers',
        'payload',
        'response_status',
        'response_time',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('method', strtoupper($method));
    }

    public function scopeByStatus($query, int $status)
    {
        return $query->where('response_status', $status);
    }

    public function scopeSlowRequests($query, int $thresholdMs = 1000)
    {
        return $query->where('response_time', '>', $thresholdMs);
    }

    public function scopeErrors($query)
    {
        return $query->where('response_status', '>=', 400);
    }
}
