<?php

namespace App\Filament\Supplier\Widgets\Supplier;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class OrdersReportWidget extends StatsOverviewWidget
{

    protected  ?string $pollingInterval = '60s'; 


   protected function getStats(): array
{
    $supplier = Auth::user()->supplier;

    if (! $supplier) {
        return [];
    }

    return cache()->remember("supplier_orders_report_{$supplier->id}", now()->addMinutes(5), function () use ($supplier) {

        $stats = Order::where('supplier_id', $supplier->id)
            ->selectRaw("
                SUM(CASE WHEN MONTH(ordered_at) = ? AND YEAR(ordered_at) = ?
                    AND status IN ('confirmed','processing','shipped','delivered') THEN supplier_total ELSE 0 END) as current_revenue,
                SUM(CASE WHEN MONTH(ordered_at) = ? AND YEAR(ordered_at) = ?
                    AND status IN ('confirmed','processing','shipped','delivered') THEN supplier_total ELSE 0 END) as previous_revenue,
                SUM(CASE WHEN MONTH(ordered_at) = ? AND YEAR(ordered_at) = ?
                    AND status IN ('confirmed','processing','shipped','delivered') THEN 1 ELSE 0 END) as current_count,
                SUM(CASE WHEN MONTH(ordered_at) = ? AND YEAR(ordered_at) = ?
                    AND status IN ('confirmed','processing','shipped','delivered') THEN 1 ELSE 0 END) as previous_count,
                SUM(CASE WHEN status = 'delivered'
                    AND MONTH(ordered_at) = ? AND YEAR(ordered_at) = ? THEN 1 ELSE 0 END) as delivered_count,
                SUM(CASE WHEN status IN ('confirmed','processing','shipped','pending','sent_to_supplier') THEN 1 ELSE 0 END) as pending_count
            ", [
                now()->month, now()->year,
                now()->subMonth()->month, now()->subMonth()->year,
                now()->month, now()->year,
                now()->subMonth()->month, now()->subMonth()->year,
                now()->month, now()->year,
            ])
            ->first();

        // Single query for both charts
        $chartData = Order::where('supplier_id', $supplier->id)
            ->where('ordered_at', '>=', now()->subDays(6)->startOfDay())
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->selectRaw("
                DATE(ordered_at) as day,
                SUM(supplier_total) as revenue,
                COUNT(*) as order_count
            ")
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $revenueChart = [];
        $ordersChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $revenueChart[] = $chartData->get($day)?->revenue ?? 0;
            $ordersChart[]  = $chartData->get($day)?->order_count ?? 0;
        }

        $currentRevenue  = $stats->current_revenue ?? 0;
        $previousRevenue = $stats->previous_revenue ?? 0;
        $currentCount    = $stats->current_count ?? 0;
        $previousCount   = $stats->previous_count ?? 0;
        $deliveredCount  = $stats->delivered_count ?? 0;
        $pendingCount    = $stats->pending_count ?? 0;

        $revenueChange = $previousRevenue > 0
            ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100
            : 0;

        $ordersChange = $previousCount > 0
            ? (($currentCount - $previousCount) / $previousCount) * 100
            : 0;

        $avgOrderValue = $currentCount > 0 ? $currentRevenue / $currentCount : 0;

        return [
            Stat::make('Monthly Revenue', 'KES ' . number_format($currentRevenue, 2))
                ->description(number_format(abs($revenueChange), 1) . '% ' . ($revenueChange >= 0 ? 'increase' : 'decrease'))
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger')
                ->chart($revenueChart),

            Stat::make('Orders This Month', $currentCount)
                ->description(number_format(abs($ordersChange), 1) . '% ' . ($ordersChange >= 0 ? 'increase' : 'decrease'))
                ->descriptionIcon($ordersChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($ordersChange >= 0 ? 'success' : 'danger')
                ->chart($ordersChart),

            Stat::make('Delivered Orders', $deliveredCount)
                ->description('Successfully completed')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Pending Orders', $pendingCount)
                ->description('Awaiting fulfillment')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Avg Order Value', 'KES ' . number_format($avgOrderValue, 2))
                ->description('This month')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),

            Stat::make('Fulfillment Rate',
                $currentCount > 0
                    ? number_format(($deliveredCount / $currentCount) * 100, 1) . '%'
                    : '0%'
            )
                ->description('Orders completed')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),
        ];
    });
}

    protected function getRevenueChart(int $supplierId): array
    {
        // Get last 7 days revenue
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $revenue = Order::where('supplier_id', $supplierId)
                ->whereDate('ordered_at', $date)
                ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                ->sum('supplier_total');
            $data[] = $revenue;
        }
        return $data;
    }

    protected function getOrdersChart(int $supplierId): array
    {
        // Get last 7 days order count
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = Order::where('supplier_id', $supplierId)
                ->whereDate('ordered_at', $date)
                ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                ->count();
            $data[] = $count;
        }
        return $data;
    }
}
