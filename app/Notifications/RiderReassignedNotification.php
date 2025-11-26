<?php

namespace App\Notifications;

use App\Models\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RiderReassignedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Delivery $delivery , public string $reason)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Delivery Reassigned',
            'body' => "The delivery with ID {$this->delivery->id} has been reassigned. Reason: {$this->reason}",
            'icon' => 'heroicon-exclamation-triangle',
            'iconColor' => 'warning',
            'actions' => [
                'label' => 'View Delivery',
                'url' => route('filament.Operation.resources.internals.deliveries.view', ['record' => $this->delivery->id]),
            ],
            'delivery_id' => $this->delivery->id,
            'reason' => $this->reason,
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
