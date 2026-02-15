<?php

namespace App\Filament\Operation\Resources\Accountoverviews\Widgets;

use App\Models\Payable;
use App\Models\Receivable;
use Filament\Widgets\ChartWidget;

class CategorybreakdownWidget extends ChartWidget
{
    protected  ?string $heading = 'Category Breakdown';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $receivablesPatient = Receivable::where('payment_source', 'patient')
            ->whereNotNull('received_at')
            ->sum('amount');

        $receivablesInsurance = Receivable::where('payment_source', 'insurance')
            ->whereNotNull('received_at')
            ->sum('amount');

        $payablesSupplier = Payable::where('vendor_type', 'supplier')
            ->whereNotNull('paid_at')
            ->sum('amount');

        $payablesPhysician = Payable::where('vendor_type', 'physician')
            ->whereNotNull('paid_at')
            ->sum('amount');

        return [
            'datasets' => [
                [
                    'label' => 'Money Received',
                    'data' => [
                        $receivablesPatient,
                        $receivablesInsurance,
                        0,
                        0,
                    ],
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.7)',
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(0, 0, 0, 0)',
                        'rgba(0, 0, 0, 0)',
                    ],
                ],
                [
                    'label' => 'Money Paid Out',
                    'data' => [
                        0,
                        0,
                        $payablesSupplier,
                        $payablesPhysician,
                    ],
                    'backgroundColor' => [
                        'rgba(0, 0, 0, 0)',
                        'rgba(0, 0, 0, 0)',
                        'rgba(251, 146, 60, 0.7)',
                        'rgba(239, 68, 68, 0.7)',
                    ],
                ],
            ],
            'labels' => ['Patient', 'Insurance', 'Supplier', 'Physician'],
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
                        'callback' => 'function(value) { return "KES " + value.toLocaleString(); }',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return context.dataset.label + ": KES " + context.parsed.y.toLocaleString(); }',
                    ],
                ],
            ],
        ];
    }
}
