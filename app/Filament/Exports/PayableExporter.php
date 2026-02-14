<?php

namespace App\Filament\Exports;

use App\Models\Payable;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PayableExporter extends Exporter
{
    protected static ?string $model = Payable::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('created_at')
                ->label('Date'),
            ExportColumn::make('reference')
                ->label('Reference'),
            ExportColumn::make('order.order_number')
                ->label('Order Number'),
            ExportColumn::make('vendor.name')
                ->label('Vendor'),
            ExportColumn::make('vendor_type')
                ->label('Vendor Type')
                ->formatStateUsing(fn (string $state): string => ucfirst($state)),
            ExportColumn::make('amount')
                ->label('Amount (KES)'),
            ExportColumn::make('payment_method')
                ->label('Payment Method')
                ->formatStateUsing(fn (?string $state): string => $state ? ucwords(str_replace('_', ' ', $state)) : 'N/A'),
            ExportColumn::make('due_date')
                ->label('Due Date'),
            ExportColumn::make('paid_at')
                ->label('Paid')
                ->formatStateUsing(fn ($state): string => $state ? 'Yes' : 'No'),
            ExportColumn::make('paid_at')
                ->label('Paid Date')
                ->formatStateUsing(fn ($state): string => $state ? $state->format('Y-m-d H:i:s') : 'Not paid'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your payables export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}