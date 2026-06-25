<?php

namespace App\Filament\Supplier\Widgets\Supplier;

use App\Models\Order;
use App\Models\Quotation;
use App\Models\SupplierMedicine;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SupplierStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $supplierId = Auth::user()->supplier?->id;

        if (! $supplierId) {
            return [];
        }

        // Pending Quotations
        $pendingQuotations = Quotation::whereHas('items', function ($q) use ($supplierId) {
            $q->where('supplier_id', $supplierId);
        })
            ->where('status', 'pending')
            ->count();

        $lastWeekPendingQuotations = Quotation::whereHas('items', function ($q) use ($supplierId) {
            $q->where('supplier_id', $supplierId);
        })
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subWeek())
            ->count();

        // Active Orders
        $activeOrders = Order::where('supplier_id', $supplierId)
            ->whereIn('status', ['confirmed', 'processing', 'shipped'])
            ->count();

        $lastWeekActiveOrders = Order::where('supplier_id', $supplierId)
            ->whereIn('status', ['confirmed', 'processing', 'shipped'])
            ->where('created_at', '>=', now()->subWeek())
            ->count();

        // This Month Revenue
        $thisMonthRevenue = Order::where('supplier_id', $supplierId)
            ->where('status', 'delivered')
            ->whereMonth('delivered_at', now()->month)
            ->whereYear('delivered_at', now()->year)
            ->sum('total_amount');

        $lastMonthRevenue = Order::where('supplier_id', $supplierId)
            ->where('status', 'delivered')
            ->whereMonth('delivered_at', now()->subMonth()->month)
            ->whereYear('delivered_at', now()->subMonth()->year)
            ->sum('total_amount');

        $revenueChange = $lastMonthRevenue > 0
            ? (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100
            : 0;

        // Low Stock Items
        $lowStockItems = SupplierMedicine::where('supplier_id', $supplierId)
            ->where('is_available', true)
            ->where('stock_quantity', '<=', 10)
            ->count();

        // Total Products
        $totalProducts = SupplierMedicine::where('supplier_id', $supplierId)
            ->where('is_available', true)
            ->count();

        // Average Order Value
        $avgOrderValue = Order::where('supplier_id', $supplierId)
            ->where('status', 'delivered')
            ->avg('total_amount') ?? 0;

        return [
        

            Stat::make('Active Orders', $activeOrders)
                ->description($lastWeekActiveOrders.' orders this week')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success')
                ->chart([12, 8, 15, 10, 9, 11, $lastWeekActiveOrders]),


            Stat::make('Low Stock Alert', $lowStockItems)
                ->description($totalProducts.' total products')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockItems > 5 ? 'danger' : 'warning'),

            Stat::make('Avg Order Value', 'KES '.number_format($avgOrderValue, 2))
                ->description('Average per order')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),

            Stat::make('Total Products', $totalProducts)
                ->description('In your catalog')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),
        ];
    }
}
