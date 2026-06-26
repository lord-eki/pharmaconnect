<?php

namespace App\Filament\Operation\Resources\Medicines\Pages;

use App\Filament\Operation\Resources\Medicines\MedicineResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMedicine extends CreateRecord
{
    protected static string $resource = MedicineResource::class;
}
