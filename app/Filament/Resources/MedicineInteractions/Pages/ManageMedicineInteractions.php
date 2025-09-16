<?php

namespace App\Filament\Resources\MedicineInteractions\Pages;

use App\Filament\Resources\MedicineInteractions\MedicineInteractionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMedicineInteractions extends ManageRecords
{
    protected static string $resource = MedicineInteractionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
