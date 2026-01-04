<?php

namespace App\Filament\Operation\Resources\Patients\Pages;

use App\Filament\Operation\Resources\Patients\PatientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPatients extends ListRecords
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
