<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class SystemHealthWidget extends Widget
{
    protected string $view = 'filament.widgets.system-health-widget';

    protected int | string | array $columnSpan = 1;

    public function getHealthMetrics(): array
    {
        return [
            [
                'name' => 'API Systems',
                'status' => 'operational',
                'uptime' => '99.8%',
                'description' => 'All endpoints operational',
                'color' => 'success'
            ],
            [
                'name' => 'Payment Processing',
                'status' => 'processing',
                'uptime' => null,
                'description' => 'Mpesa transfers are active',
                'color' => 'success'
            ],
            [
                'name' => 'Medicine Database',
                'status' => 'degraded',
                'uptime' => null,
                'description' => 'Sync in progress (51/100%)',
                'color' => 'warning'
            ],
            [
                'name' => 'Error Monitoring',
                'status' => 'monitoring',
                'uptime' => null,
                'description' => '12 minor warnings logged',
                'color' => 'info'
            ],
        ];
    }
}
