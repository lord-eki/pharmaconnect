<?php

namespace App\Filament\Imports;

use App\Models\Medicine;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class MedicineImporter extends Importer
{
    protected static ?string $model = Medicine::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('category')
                ->requiredMapping()
                ->relationship()
                ->rules(['required']),
            ImportColumn::make('generic_name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('brand_name')
                ->rules(['max:255']),
            ImportColumn::make('strength')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('dosage_form')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('pack_size')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('manufacturer')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('active_ingredients')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('description'),
            ImportColumn::make('usage_instructions'),
            ImportColumn::make('side_effects'),
            ImportColumn::make('contraindications'),
            ImportColumn::make('storage_requirements'),
            ImportColumn::make('prescription_required')
                ->requiredMapping()
                ->boolean()
                ->rules(['required', 'boolean']),
            ImportColumn::make('controlled_substance')
                ->requiredMapping()
                ->boolean()
                ->rules(['required', 'boolean']),
            ImportColumn::make('ppb_registration_number')
                ->rules(['max:255']),
            ImportColumn::make('is_active')
                ->requiredMapping()
                ->boolean()
                ->rules(['required', 'boolean']),
        ];
    }

    public function resolveRecord(): Medicine
    {
        return new Medicine;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your medicine import has completed and '.Number::format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
