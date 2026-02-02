<?php

namespace App\Filament\Widgets;

use App\Models\Medicine;
use App\Models\Order;
use App\Models\Prescription;
use App\Models\Transaction;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class AdminStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected  ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        return [
            $this->getTodayOrdersStat(),
            $this->getTodayRevenueStat(),
            $this->getActivePrescriptionsStat(),
            $this->getPendingOrdersStat(),
        ];
    }

    protected function getTodayOrdersStat(): Stat
    {
        $todayCount = Cache::remember('today_orders_count', 300, function () {
            return Order::whereDate('created_at', today())->count();
        });

        $yesterdayCount = Cache::remember('yesterday_orders_count', 300, function () {
            return Order::whereDate('created_at', today()->subDay())->count();
        });

        $change = $yesterdayCount > 0 
            ? round((($todayCount - $yesterdayCount) / $yesterdayCount) * 100, 1) 
            : 0;

        return Stat::make('Orders Today', $todayCount)
            ->description($change >= 0 ? "+{$change}% from yesterday" : "{$change}% from yesterday")
            ->descriptionIcon($change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->color($change >= 0 ? 'success' : 'danger')
            ->chart($this->getOrdersChartData());
    }

    protected function getTodayRevenueStat(): Stat
    {
        $todayRevenue = Cache::remember('today_revenue', 300, function () {
            return Order::whereDate('created_at', today())
                ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                ->sum('total_amount') ?? 0;
        });

        $yesterdayRevenue = Cache::remember('yesterday_revenue', 300, function () {
            return Order::whereDate('created_at', today()->subDay())
                ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                ->sum('total_amount') ?? 0;
        });

        $change = $yesterdayRevenue > 0 
            ? round((($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100, 1) 
            : 0;

        return Stat::make('Revenue Today', 'KES ' . number_format($todayRevenue, 0))
            ->description($change >= 0 ? "+{$change}% from yesterday" : "{$change}% from yesterday")
            ->descriptionIcon($change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->color($change >= 0 ? 'success' : 'danger')
            ->chart($this->getRevenueChartData());
    }

    protected function getActivePrescriptionsStat(): Stat
    {
        $activeCount = Cache::remember('active_prescriptions', 300, function () {
            return Prescription::whereIn('status', ['submitted', 'processing'])->count();
        });

        $totalCount = Cache::remember('total_prescriptions_week', 300, function () {
            return Prescription::where('created_at', '>=', now()->subWeek())->count();
        });

        return Stat::make('Active Prescriptions', $activeCount)
            ->description("{$totalCount} submitted this week")
            ->descriptionIcon('heroicon-m-document-text')
            ->color('info');
    }

    protected function getPendingOrdersStat(): Stat
    {
        $pendingCount = Cache::remember('pending_orders', 60, function () {
            return Order::whereIn('status', ['pending_review', 'sent_to_supplier'])->count();
        });

        $confirmedToday = Cache::remember('confirmed_orders_today', 300, function () {
            return Order::whereDate('updated_at', today())
                ->where('status', 'confirmed')
                ->count();
        });

        return Stat::make('Pending Orders', $pendingCount)
            ->description("{$confirmedToday} confirmed today")
            ->descriptionIcon('heroicon-m-clock')
            ->color($pendingCount > 20 ? 'warning' : 'success');
    }

    protected function getOrdersChartData(): array
    {
        return Cache::remember('orders_chart_7days', 600, function () {
            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = today()->subDays($i);
                $count = Order::whereDate('created_at', $date)->count();
                $data[] = $count;
            }
            return $data;
        });
    }

    protected function getRevenueChartData(): array
    {
        return Cache::remember('revenue_chart_7days', 600, function () {
            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = today()->subDays($i);
                $revenue = Order::whereDate('created_at', $date)
                    ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
                    ->sum('total_amount') ?? 0;
                $data[] = (float) $revenue;
            }
            return $data;
        });
    }
}