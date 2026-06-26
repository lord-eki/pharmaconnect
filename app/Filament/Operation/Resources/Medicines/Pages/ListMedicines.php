<?php

namespace App\Filament\Operation\Resources\Medicines\Pages;

use App\Filament\Operation\Resources\Medicines\MedicineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMedicines extends ListRecords
{
    protected static string $resource = MedicineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
