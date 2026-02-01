<?php

namespace App\Filament\Rider\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class EarningsChart extends ChartWidget
{
    protected ?string $heading = 'Weekly Deliveries';

    protected ?string $maxHeight = '250px';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $rider = auth()->user()->rider;

        if (! $rider) {
            return [
                'datasets' => [
                    [
                        'label' => 'Deliveries',
                        'data' => [0, 0, 0, 0, 0, 0, 0],
                    ],
                ],
                'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            ];
        }

        // Get deliveries for the past 7 days
        $deliveriesPerDay = $rider->deliveries()
            ->where('status', 'delivered')
            ->whereBetween('actual_delivery', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->select(
                DB::raw('DATE(actual_delivery) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('DATE(actual_delivery)'))
            ->orderBy('date')
            ->pluck('count', 'date');

        // Fill in missing days with 0
        $data = [];
        $labels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateKey = $date->format('Y-m-d');
            $data[] = $deliveriesPerDay[$dateKey] ?? 0;
            $labels[] = $date->format('D');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Deliveries Completed',
                    'data' => $data,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
