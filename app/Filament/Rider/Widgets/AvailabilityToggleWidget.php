<?php

namespace App\Filament\Rider\Widgets;

use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class AvailabilityToggleWidget extends Widget
{
    protected  string $view = 'filament.rider.widgets.availability-toggle-widget';

    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full';

    public function toggleAvailability()
    {
        $rider = auth()->user()->rider;

        if (!$rider) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Rider profile not found.')
                ->send();
            return;
        }

        $rider->update([
            'is_available' => !$rider->is_available,
        ]);

        Notification::make()
            ->success()
            ->title('Availability Updated')
            ->body($rider->is_available 
                ? 'You are now available for deliveries.' 
                : 'You are now unavailable for deliveries.')
            ->send();

        // Refresh the widget
        $this->dispatch('$refresh');
    }

    public function getAvailabilityStatus(): array
    {
        $rider = auth()->user()->rider;

        if (!$rider) {
            return [
                'is_available' => false,
                'is_active' => false,
            ];
        }

        return [
            'is_available' => $rider->is_available,
            'is_active' => $rider->is_active,
        ];
    }
}
