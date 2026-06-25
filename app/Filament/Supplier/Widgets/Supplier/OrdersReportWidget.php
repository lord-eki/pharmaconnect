<?php

namespace App\Filament\Supplier\Widgets\Supplier;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class OrdersReportWidget extends StatsOverviewWidget
{
   protected function getStats(): array
    {
        $supplier = Auth::user()->supplier;

        // Get current month data
        $currentMonthOrders = Order::where('supplier_id', $supplier->id)
            ->whereMonth('ordered_at', now()->month)
            ->whereYear('ordered_at', now()->year)
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered']);

        // Get previous month data for comparison
        $previousMonthOrders = Order::where('supplier_id', $supplier->id)
            ->whereMonth('ordered_at', now()->subMonth()->month)
            ->whereYear('ordered_at', now()->subMonth()->year)
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered']);

        $currentMonthRevenue = $currentMonthOrders->sum('supplier_total');
        $previousMonthRevenue = $previousMonthOrders->sum('supplier_total');

        $currentMonthCount = $currentMonthOrders->count();
        $previousMonthCount = $previousMonthOrders->count();

        // Calculate percentage changes
        $revenueChange = $previousMonthRevenue > 0 
            ? (($currentMonthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100 
            : 0;

        $ordersChange = $previousMonthCount > 0 
            ? (($currentMonthCount - $previousMonthCount) / $previousMonthCount) * 100 
            : 0;

        // Get delivered orders
        $deliveredOrders = Order::where('supplier_id', $supplier->id)
            ->where('status', 'delivered')
            ->whereMonth('ordered_at', now()->month)
            ->whereYear('ordered_at', now()->year)
            ->count();

        // Get pending orders
        $pendingOrders = Order::where('supplier_id', $supplier->id)
            ->whereIn('status', ['confirmed', 'processing', 'shipped','pending','sent_to_supplier'])
            ->count();


        // Average order value
        $avgOrderValue = $currentMonthCount > 0 
            ? $currentMonthRevenue / $currentMonthCount 
            : 0;

        return [
            Stat::make('Monthly Revenue', 'KES ' . number_format($currentMonthRevenue, 2))
                ->description($revenueChange >= 0 
                    ? number_format(abs($revenueChange), 1) . '% increase' 
                    : number_format(abs($revenueChange), 1) . '% decrease')
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger')
                ->chart($this->getRevenueChart($supplier->id)),

            Stat::make('Orders This Month', $currentMonthCount)
                ->description($ordersChange >= 0 
                    ? number_format(abs($ordersChange), 1) . '% increase' 
                    : number_format(abs($ordersChange), 1) . '% decrease')
                ->descriptionIcon($ordersChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($ordersChange >= 0 ? 'success' : 'danger')
                ->chart($this->getOrdersChart($supplier->id)),

            Stat::make('Delivered Orders', $deliveredOrders)
                ->description('Successfully completed')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Pending Orders', $pendingOrders)
                ->description('Awaiting fulfillment')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(route('filament.Supplier.resources.supplier.orders.index', ['tableFilters' => ['status' => ['values' => ['confirmed', 'processing', 'shipped']]]])),

            Stat::make('Avg Order Value', 'KES ' . number_format($avgOrderValue, 2))
                ->description('This month')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),

            Stat::make('Fulfillment Rate', 
                $currentMonthCount > 0 
                    ? number_format(($deliveredOrders / $currentMonthCount) * 100, 1) . '%' 
                    : '0%'
            )
                ->description('Orders completed on time')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),
        ];
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
