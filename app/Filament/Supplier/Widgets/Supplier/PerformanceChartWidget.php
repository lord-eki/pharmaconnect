<?php

namespace App\Filament\Supplier\Widgets\Supplier;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PerformanceChartWidget extends ChartWidget
{
    protected ?string $heading = 'Monthly Order Performance';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $supplierId = Auth::user()->userProfile->id ?? null;

        // Get last 6 months data
        $data = Order::where('supplier_id', $supplierId)
            ->where('created_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered_orders'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $months = [];
        $orders = [];
        $delivered = [];
        $revenue = [];

        foreach ($data as $item) {
            $months[] = date('M Y', strtotime($item->month.'-01'));
            $orders[] = $item->total_orders;
            $delivered[] = $item->delivered_orders;
            $revenue[] = $item->revenue;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Orders',
                    'data' => $orders,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
                [
                    'label' => 'Delivered Orders',
                    'data' => $delivered,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
