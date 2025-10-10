<?php

namespace App\Filament\Physician\Resources\Physician\Prescriptions\Pages;

use App\Filament\Physician\Resources\Physician\Prescriptions\PrescriptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrescriptions extends ListRecords
{
    protected static string $resource = PrescriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle')->label('New Prescription')
        ];
    }
}
