<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class RecentActivitiesWidget extends Widget
{

    protected string $view = 'filament.widgets.recent-activities-widget';
    protected int|string|array $columnSpan = 1;

    

    public function getActivities(): array
    {
        return [
            [
                'type' => 'success',
                'icon' => 'heroicon-o-user-plus',
                'title' => 'New user registration approval',
                'description' => 'Dr. Nora Ken - Physical License verified',
                'time' => '2 mins ago',
                'color' => 'success'
            ],
            [
                'type' => 'info',
                'icon' => 'heroicon-o-cog-6-tooth',
                'title' => 'System configuration updated',
                'description' => 'Pricing algorithms - markup updated',
                'time' => '8 mins ago',
                'color' => 'info'
            ],
            [
                'type' => 'warning',
                'icon' => 'heroicon-o-users',
                'title' => 'Bulk user operation completed',
                'description' => '45 supplier accounts approved',
                'time' => '6 hours ago',
                'color' => 'warning'
            ],
            [
                'type' => 'danger',
                'icon' => 'heroicon-o-exclamation-triangle',
                'title' => 'System alert triggered',
                'description' => 'Medicine database sync took - requires attention',
                'time' => '4 days ago',
                'color' => 'danger'
            ],
        ];
    }

}


