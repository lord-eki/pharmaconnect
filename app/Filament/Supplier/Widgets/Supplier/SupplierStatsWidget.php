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
    protected  ?string $pollingInterval = '60s'; 

    protected function getStats(): array
{
    $supplierId = Auth::user()->supplier?->id;

    if (! $supplierId) {
        return [];
    }

    [$orderStats, $stockStats] = cache()->remember(
        "supplier_stats_{$supplierId}",
        now()->addMinutes(5), 
        function () use ($supplierId) {
            $orderStats = Order::where('supplier_id', $supplierId)
                ->selectRaw("
                    SUM(CASE WHEN status IN ('confirmed', 'processing', 'shipped') THEN 1 ELSE 0 END) as active_orders,
                    SUM(CASE WHEN status IN ('confirmed', 'processing', 'shipped') AND created_at >= ? THEN 1 ELSE 0 END) as last_week_active,
                    SUM(CASE WHEN status = 'delivered' AND MONTH(delivered_at) = ? AND YEAR(delivered_at) = ? THEN total_amount ELSE 0 END) as this_month_revenue,
                    SUM(CASE WHEN status = 'delivered' AND MONTH(delivered_at) = ? AND YEAR(delivered_at) = ? THEN total_amount ELSE 0 END) as last_month_revenue,
                    AVG(CASE WHEN status = 'delivered' THEN total_amount ELSE NULL END) as avg_order_value
                ", [
                    now()->subWeek(),
                    now()->month, now()->year,
                    now()->subMonth()->month, now()->subMonth()->year,
                ])
                ->first();

            $stockStats = SupplierMedicine::where('supplier_id', $supplierId)
                ->where('is_available', true)
                ->selectRaw("
                    COUNT(*) as total_products,
                    SUM(CASE WHEN stock_quantity <= 10 THEN 1 ELSE 0 END) as low_stock
                ")
                ->first();

            return [$orderStats, $stockStats];
        }
    );

    $revenueChange = ($orderStats->last_month_revenue ?? 0) > 0
        ? (($orderStats->this_month_revenue - $orderStats->last_month_revenue) / $orderStats->last_month_revenue) * 100
        : 0;

    return [
        Stat::make('Active Orders', $orderStats->active_orders ?? 0)
            ->description(($orderStats->last_week_active ?? 0).' orders this week')
            ->descriptionIcon('heroicon-m-shopping-bag')
            ->color('success')
            ->chart([12, 8, 15, 10, 9, 11, $orderStats->last_week_active ?? 0]),

        Stat::make('Low Stock Alert', $stockStats->low_stock ?? 0)
            ->description(($stockStats->total_products ?? 0).' total products')
            ->descriptionIcon('heroicon-m-exclamation-triangle')
            ->color(($stockStats->low_stock ?? 0) > 5 ? 'danger' : 'warning'),

        Stat::make('Avg Order Value', 'KES '.number_format($orderStats->avg_order_value ?? 0, 2))
            ->description('Average per order')
            ->descriptionIcon('heroicon-m-currency-dollar')
            ->color('info'),

        Stat::make('Total Products', $stockStats->total_products ?? 0)
            ->description('In your catalog')
            ->descriptionIcon('heroicon-m-cube')
            ->color('primary'),
    ];
}
}
