<?php

namespace App\Filament\Exports;

use App\Models\SupplierMedicine;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class SupplierMedicineExporter extends Exporter
{
    protected static ?string $model = SupplierMedicine::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('supplier.id'),
            ExportColumn::make('medicine.id'),
            ExportColumn::make('unit_price'),
            ExportColumn::make('stock_quantity'),
            ExportColumn::make('minimum_order_quantity'),
            ExportColumn::make('expiry_date'),
            ExportColumn::make('batch_number'),
            ExportColumn::make('is_available'),
            ExportColumn::make('last_updated'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your supplier medicine export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
