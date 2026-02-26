<?php

namespace App\Filament\Physician\Resources\Physician\Patients\Pages;

use App\Filament\Physician\Resources\Physician\Patients\PatientResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPatient extends EditRecord
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->icon('heroicon-o-trash'),
        ];
    }
}
