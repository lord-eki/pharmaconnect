<?php

namespace App\Notifications;

use App\Models\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeliveryStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Delivery $delivery,
        public string $oldStatus,
        public string $newStatus
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusColors = [
            'pending' => '#6B7280',
            'assigned' => '#3B82F6',
            'in_transit' => '#8B5CF6',
            'delivered' => '#10B981',
            'failed' => '#EF4444',
            'picked_up' => '#10B981',
            'ready_for_pickup' => '#F59E0B',
            'cancelled' => '#6B7280',
        ];

        $message = (new MailMessage)
            ->subject('Delivery Status Updated - ' . $this->delivery->delivery_number)
            ->greeting('Hello ' . ($notifiable->rider->first_name ?? $notifiable->name) . '!')
            ->line('The status of your delivery has been updated.')
            ->line('**Delivery Number:** ' . $this->delivery->delivery_number)
            ->line('**Previous Status:** ' . ucfirst(str_replace('_', ' ', $this->oldStatus)))
            ->line('**New Status:** ' . ucfirst(str_replace('_', ' ', $this->newStatus)));

        // Add specific information based on status
        switch ($this->newStatus) {
            case 'assigned':
                $message->line('📦 A delivery has been assigned to you.')
                    ->line('**Pickup Address:** ' . $this->delivery->pickup_address)
                    ->line('**Delivery Address:** ' . $this->delivery->delivery_address)
                    ->line('**Recipient:** ' . $this->delivery->recipient_name);
                break;

            case 'picked_up':
                $message->line('✅ Pickup confirmed. Please proceed to the delivery address.');
                break;

            case 'in_transit':
                $message->line('🚚 Delivery is now in transit.');
                break;

            case 'delivered':
                $message->line('🎉 Delivery completed successfully!')
                    ->line('**Delivery Fee:** KES ' . number_format($this->delivery->delivery_fee, 2));
                break;

            case 'failed':
                $message->line('⚠️ Delivery marked as failed. Please contact operations for more details.');
                break;

            case 'cancelled':
                $message->line('❌ This delivery has been cancelled.');
                break;
        }

        $message->action(
            'View Delivery Details',
            route('filament.Rider.resources.deliveries.view', ['record' => $this->delivery->id])
        )
            ->line('Thank you for your service!');

        return $message;
    }

    /**
     * Get the database representation of the notification.
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
                'url' => route('filament.Rider.resources.deliveries.view', ['record' => $this->delivery->id])
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