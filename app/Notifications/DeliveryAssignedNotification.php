<?php

namespace App\Notifications;

use App\Models\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeliveryAssignedNotification extends Notification implements ShouldQueue
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
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Delivery Assigned - ' . $this->delivery->delivery_number)
            ->greeting('Hello ' . ($notifiable->rider->first_name ?? $notifiable->name) . '!')
            ->line('🚚 You have been assigned a new delivery.')
            ->line('Please review the details below:')
            ->line('')
            ->line('**Delivery Number:** ' . $this->delivery->delivery_number)
            ->line('**Order Number:** ' . $this->delivery->order?->order_number ?? 'N/A')
            ->line('')
            ->line('📍 **Pickup Details:**')
            ->line($this->delivery->pickup_address)
            ->line('**Scheduled Pickup:** ' . $this->delivery->scheduled_pickup->format('M d, Y h:i A'))
            ->line('')
            ->line('📦 **Delivery Details:**')
            ->line('**Recipient:** ' . $this->delivery->recipient_name)
            ->line('**Phone:** ' . $this->delivery->recipient_phone)
            ->line('**Address:** ' . $this->delivery->delivery_address)
            ->line('**Estimated Delivery:** ' . $this->delivery->estimated_delivery->format('M d, Y h:i A'))
            ->line('')
            ->line('💰 **Delivery Fee:** KES ' . number_format($this->delivery->delivery_fee, 2))
            ->line('📏 **Distance:** ' . $this->delivery->estimated_distance_km . ' km')
            ->action('View Delivery Details', route('filament.Rider.resources.deliveries.view', ['record' => $this->delivery->id]))
            ->line('Please confirm acceptance and proceed with the pickup as scheduled.')
            ->line('Thank you for your service!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New Delivery Assigned',
            'message' => 'You have been assigned delivery ' . $this->delivery->delivery_number . '. Pickup from ' . $this->delivery->pickup_address,
            'icon' => 'heroicon-o-truck',
            'iconColor' => 'success',
            'actions' => [
                'label' => 'View Details',
                'url' => route('filament.Rider.resources.deliveries.view', ['record' => $this->delivery->id])
            ],
            'delivery_id' => $this->delivery->id,
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