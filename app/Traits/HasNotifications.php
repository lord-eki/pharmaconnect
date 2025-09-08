<?php

namespace App\Traits\Traits;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasNotifications
{
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadNotifications(): HasMany
    {
        return $this->notifications()->unread();
    }

    public function notify(string $type, string $title, string $message, array $data = [], array $channels = ['in-app']): Notification
    {
        return $this->notifications()->create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'channels' => $channels,
        ]);
    }

    public function getUnreadNotificationsCountAttribute(): int
    {
        return $this->unreadNotifications()->count();
    }
}
