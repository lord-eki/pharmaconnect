<?php

namespace App\Filament\Physician\Resources\Physician\Patients\Pages;

use App\Filament\Physician\Resources\Physician\Patients\PatientResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePatient extends CreateRecord
{
    protected static string $resource = PatientResource::class;

    protected static bool $canCreateAnother = false;


}
