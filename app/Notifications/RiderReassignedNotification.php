<?php

namespace App\Notifications;

use App\Models\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RiderReassignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Delivery $delivery,
        public string $reason
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
        return (new MailMessage)
            ->subject('Delivery Reassigned - ' . $this->delivery->delivery_number)
            ->greeting('Hello ' . ($notifiable->rider->first_name ?? $notifiable->name) . '!')
            ->line('🔄 You have been reassigned to a delivery.')
            ->line('')
            ->line('**Delivery Number:** ' . $this->delivery->delivery_number)
            ->line('**Reason for Reassignment:** ' . $this->reason)
            ->line('')
            ->line('📍 **Pickup Details:**')
            ->line($this->delivery->pickup_address)
            ->line('**Scheduled Pickup:** ' . $this->delivery->scheduled_pickup->format('M d, Y h:i A'))
            ->line('')
            ->line('📦 **Delivery Details:**')
            ->line('**Recipient:** ' . $this->delivery->recipient_name)
            ->line('**Phone:** ' . $this->delivery->recipient_phone)
            ->line('**Address:** ' . $this->delivery->delivery_address)
            ->line('')
            ->line('💰 **Delivery Fee:** KES ' . number_format($this->delivery->delivery_fee, 2))
            ->action('View Delivery Details', route('filament.Rider.resources.deliveries.view', ['record' => $this->delivery->id]))
            ->line('Please proceed with this delivery as soon as possible.')
            ->line('Thank you for your cooperation!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Delivery Reassigned',
            'message' => 'Delivery ' . $this->delivery->delivery_number . ' has been reassigned to you. Reason: ' . $this->reason,
            'icon' => 'heroicon-o-arrow-path',
            'iconColor' => 'warning',
            'actions' => [
                'label' => 'View Details',
                'url' => route('filament.Rider.resources.deliveries.view', ['record' => $this->delivery->id])
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