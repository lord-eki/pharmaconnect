<?php

namespace App\Filament\Physician\Resources\Physician\Patients\Pages;

use App\Filament\Physician\Resources\Physician\Patients\PatientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPatients extends ListRecords
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add Patient')->icon('heroicon-o-user-plus'),
        ];
    }
}
