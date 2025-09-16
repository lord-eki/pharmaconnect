<?php

namespace App\Filament\Resources\PrescriptionItems\Pages;

use App\Filament\Resources\PrescriptionItems\PrescriptionItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePrescriptionItems extends ManageRecords
{
    protected static string $resource = PrescriptionItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle')->label('Add Prescription Item'),
        ];
    }
}
