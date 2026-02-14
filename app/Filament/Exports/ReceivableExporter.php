<?php

namespace App\Filament\Exports;

use App\Models\Receivable;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ReceivableExporter extends Exporter
{
    protected static ?string $model = Receivable::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('created_at')
                ->label('Date'),
            ExportColumn::make('reference')
                ->label('Reference'),
            ExportColumn::make('prescription.prescription_number')
                ->label('Prescription'),
            ExportColumn::make('patient.last_name')
                ->label('Patient'),
            ExportColumn::make('payment_source')
                ->label('Payer Type')
                ->formatStateUsing(fn (string $state): string => ucfirst($state)),
            ExportColumn::make('insuranceProvider.company_name')
                ->label('Insurance Company'),
            ExportColumn::make('amount')
                ->label('Amount (KES)'),
            ExportColumn::make('claim_status')
                ->label('Claim Status')
                ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'N/A'),
            ExportColumn::make('received_at')
                ->label('Received')
                ->formatStateUsing(fn ($state): string => $state ? 'Yes' : 'No'),
            ExportColumn::make('received_at')
                ->label('Received Date')
                ->formatStateUsing(fn ($state): string => $state ? $state->format('Y-m-d H:i:s') : 'Not received'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your receivables export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}