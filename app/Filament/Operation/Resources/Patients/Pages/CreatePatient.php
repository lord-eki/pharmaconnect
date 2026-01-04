<?php

namespace App\Filament\Operation\Resources\Patients\Pages;

use App\Filament\Operation\Resources\Patients\PatientResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePatient extends CreateRecord
{
    protected static string $resource = PatientResource::class;
}
