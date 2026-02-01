<?php

namespace App\Filament\Rider\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Delivery;

class RiderStatsOverview extends StatsOverviewWidget
{
 protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $rider = auth()->user()->rider;
        
        if (!$rider) {
            return [];
        }

        // Get deliveries statistics
        $todayDeliveries = $rider->deliveries()
            ->whereDate('actual_delivery', today())
            ->where('status', 'delivered')
            ->count();

        $weekDeliveries = $rider->deliveries()
            ->whereBetween('actual_delivery', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('status', 'delivered')
            ->count();

        $monthDeliveries = $rider->deliveries()
            ->whereMonth('actual_delivery', now()->month)
            ->whereYear('actual_delivery', now()->year)
            ->where('status', 'delivered')
            ->count();

        // Pending deliveries available to accept
        $pendingDeliveries = Delivery::where('status', 'pending')
            ->whereNull('rider_id')
            ->count();

        // Active deliveries (assigned to this rider)
        $activeDeliveries = $rider->deliveries()
            ->whereIn('status', ['assigned', 'picked_up', 'in_transit'])
            ->count();

        // On-time delivery percentage
        $onTimeDeliveries = $rider->deliveries()
            ->where('status', 'delivered')
            ->whereNotNull('estimated_delivery')
            ->whereNotNull('actual_delivery')
            ->whereColumn('actual_delivery', '<=', 'estimated_delivery')
            ->count();
        
        $totalCompleted = $rider->deliveries()->where('status', 'delivered')->count();
        $totalCompleted = $totalCompleted > 0 ? $totalCompleted : 1;
        $onTimePercentage = round(($onTimeDeliveries / $totalCompleted) * 100, 1);

        return [

            Stat::make('Today', $todayDeliveries)
                ->description('Deliveries completed today')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('This Week', $weekDeliveries)
                ->description('Deliveries this week')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('This Month', $monthDeliveries)
                ->description('Deliveries this month')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),

            Stat::make('Active Deliveries', $activeDeliveries)
                ->description($pendingDeliveries . ' pending available')
                ->descriptionIcon('heroicon-m-clock')
                ->color($activeDeliveries > 0 ? 'info' : 'gray')
                ->extraAttributes([
                    'class' => $activeDeliveries > 0 ? 'ring-2 ring-blue-500' : '',
                ]),

            Stat::make('On-Time Rate', $onTimePercentage . '%')
                ->description('Deliveries on time')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($onTimePercentage >= 90 ? 'success' : ($onTimePercentage >= 75 ? 'warning' : 'danger')),

            Stat::make('Status', $rider->is_available ? 'Available' : 'Unavailable')
                ->description($rider->is_active ? 'Active account' : 'Inactive account')
                ->descriptionIcon($rider->is_available ? 'heroicon-m-check-badge' : 'heroicon-m-x-circle')
                ->color($rider->is_available ? 'success' : 'gray'),
        ];
    }
}
