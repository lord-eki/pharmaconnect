<?php

namespace App\Notifications;

use App\Models\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeliveryAssignedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Delivery $delivery)
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
        'title' => 'New Delivery Assigned',
        'message' => 'A new delivery has been assigned to you. Delivery ID: ' . $this->delivery->id,
        'icon' => 'heroicon-o-truck',
        'iconColor' => 'success',
        'actions' => [
            'label' => 'View Delivery',
            'url' => route('filament.Operation.resources.internals.deliveries.view',[ $this->delivery->id])
        ],
        'delivery_id' => $this->delivery->id,
        'delivery_number' => $this->delivery->delivery_number,
        'delivery_address' => $this->delivery->delivery_address,
        'estimated_delivery' => $this->delivery->estimated_delivery?->format('M d, Y H:i'),
       ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
