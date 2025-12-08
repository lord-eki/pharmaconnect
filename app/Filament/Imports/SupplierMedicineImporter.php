<?php

namespace App\Filament\Imports;

use App\Models\Medicine;
use App\Models\SupplierMedicine;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class SupplierMedicineImporter extends Importer
{
    protected static ?string $model = SupplierMedicine::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('medicine')
                ->label('Medicine (Generic Name)')
                ->requiredMapping()
                ->relationship(resolveUsing: function (string $state): ?Medicine {
                    $medicine = Medicine::where('generic_name', 'LIKE', "%{$state}%")
                        ->orWhere('brand_name', 'LIKE', "%{$state}%")
                        ->first();

                    return $medicine;
                })
                ->rules(['required']),

            ImportColumn::make('unit_price')
                ->label('Unit Price (KES)')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric', 'min:0']),

            ImportColumn::make('stock_quantity')
                ->label('Stock Quantity')
                ->requiredMapping()
                ->integer()
                ->rules(['required', 'integer', 'min:0']),

            ImportColumn::make('minimum_order_quantity')
                ->label('Minimum Order Quantity')
                ->integer()
                ->rules(['integer', 'min:1']),

            ImportColumn::make('expiry_date')
                ->label('Expiry Date')
                ->rules(['date', 'after:today', 'nullable']),

            ImportColumn::make('batch_number')
                ->label('Batch Number')
                ->rules(['nullable', 'string', 'max:255']),

            ImportColumn::make('is_available')
                ->label('Available')
                ->boolean()
                ->rules(['boolean']),
        ];
    }

    public function resolveRecord(): ?SupplierMedicine
    {
        $supplierId = auth()->user()->supplier->id ?? null;

        if (! $supplierId) {
            return null;
        }

        $medicineId = $this->data['medicine'];

        // Check if this supplier already has this medicine
        return SupplierMedicine::firstOrNew([
            'supplier_id' => $supplierId,
            'medicine_id' => $medicineId,
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your supplier medicine import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }

    protected function beforeSave(): void
    {
        // Set supplier_id and last_updated timestamp
        $this->record->supplier_id = auth()->user()->supplier->id ?? null;
        $this->record->last_updated = now();

        if (! isset($this->data['stock_quantity']) || $this->data['stock_quantity'] === null) {
            $this->record->stock_quantity = 0;
        }

        if (! isset($this->data['is_available']) || $this->data['is_available'] === null) {
            $this->record->is_available = true;
        }

        if (! isset($this->data['minimum_order_quantity']) || $this->data['minimum_order_quantity'] === null) {
            $this->record->minimum_order_quantity = 1;
        }
    }
}
