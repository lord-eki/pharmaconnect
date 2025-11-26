<?php

namespace App\Notifications;

use App\Models\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeliveryStatusUpdatedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Delivery $delivery , public string $oldStatus, public string $newStatus)
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
        $statusColors = [
            'pending' => 'gray',
            'assigned' => 'info',
            'in_transit' => 'primary',
            'delivered' => 'success',
            'failed' => 'danger',
            'picked_up' => 'success',
            'ready_for_pickup' => 'warning',
            'cancelled' => 'gray',
        ];

        return [
            'title' => 'Delivery Status Updated',
            'message' => 'The status of Delivery ID: ' . $this->delivery->id . ' has been updated from ' . ucfirst(str_replace('_', ' ', $this->oldStatus)) . ' to ' . ucfirst(str_replace('_', ' ', $this->newStatus)) . '.',
            'icon' => 'heroicon-o-arrow-path',
            'iconColor' => $statusColors[$this->newStatus] ?? 'info',
            'actions' => [
                'label' => 'View Details',
                'url' => route('filament.Operation.resources.internals.deliveries.view',['record' => $this->delivery->id])
            ],
            'delivery_id' => $this->delivery->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
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
