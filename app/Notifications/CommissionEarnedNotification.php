<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommissionEarnedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public $commission
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Commission Earned',
            'body' => "You earned KES {$this->commission->commission_amount} from prescription #{$this->commission->prescription->prescription_number}",
            'icon' => 'heroicon-o-currency-dollar',
            'iconColor' => 'success',
            'actions' => [
                [
                    'label' => 'View Commission',
                    'url' => route('filament.Physician.resources.commissions.view', [
                        'record' => $this->commission->id
                    ]),
                ],
            ],
            'commission_id' => $this->commission->id,
            'commission_amount' => $this->commission->commission_amount,
            'prescription_number' => $this->commission->prescription->prescription_number,
        ];
    }
}
