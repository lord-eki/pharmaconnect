<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['database'];

        // if ($notifiable->email) {
        //     $channels[] = 'mail';
        // }

        // Add SMS channel if available
        // if ($notifiable->phone) {
        //     $channels[] = 'sms';
        // }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Order: {$this->order->order_number}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("You have received a new order.")
            ->line("**Order Number:** {$this->order->order_number}")
            ->line("**Prescription:** {$this->order->prescription->prescription_number}")
            ->line("**Total Amount:** KES " . number_format($this->order->total_amount, 2))
            ->line("**Items:** {$this->order->items->count()} medicine(s)")
            ->line("**Expected Delivery:** {$this->order->expected_delivery->format('M d, Y H:i')}")
            ->action('View Order', url("/supplier/orders/{$this->order->id}"))
            ->line('Please confirm this order as soon as possible.')
            ->line('Thank you for your partnership!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'prescription_number' => $this->order->prescription->prescription_number ?? 'N/A',
            'total_amount' => $this->order->total_amount,
            'items_count' => $this->order->items->count(),
            'expected_delivery' => $this->order->expected_delivery,
            'message' => "New order {$this->order->order_number} received for KES " . number_format($this->order->total_amount, 2),
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total_amount' => $this->order->total_amount,
        ];
    }
}