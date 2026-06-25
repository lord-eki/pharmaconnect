<?php

namespace App\Filament\Supplier\Widgets\Supplier;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PerformanceChartWidget extends ChartWidget
{
    protected ?string $heading = 'Daily Order Performance';

    protected static ?int $sort = 3;

    protected  ?string $pollingInterval = '60s'; 


    protected ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = 'full';

   protected function getData(): array
{
    $supplierId = Auth::user()->supplier?->id;

    if (! $supplierId) {
        return ['datasets' => [], 'labels' => []];
    }

    return cache()->remember("supplier_performance_chart_{$supplierId}", now()->addMinutes(5), function () use ($supplierId) {

        $data = Order::where('supplier_id', $supplierId)
            ->where('created_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d") as day'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered_orders'),
            )
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $days      = $data->map(fn ($item) => date('M d', strtotime($item->day)))->toArray();
        $orders    = $data->pluck('total_orders')->toArray();
        $delivered = $data->pluck('delivered_orders')->toArray();

        return [
            'datasets' => [
                [
                    'label'           => 'Total Orders',
                    'data'            => $orders,
                    'borderColor'     => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension'         => 0.4,
                    'fill'            => true,
                ],
                [
                    'label'           => 'Delivered Orders',
                    'data'            => $delivered,
                    'borderColor'     => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'tension'         => 0.4,
                    'fill'            => true,
                ],
            ],
            'labels' => $days,
        ];
    });
}

    protected function getType(): string
    {
        return 'line';
    }
}
