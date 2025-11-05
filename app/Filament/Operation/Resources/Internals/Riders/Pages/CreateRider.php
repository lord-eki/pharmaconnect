<?php

namespace App\Filament\Operation\Resources\Internals\Riders\Pages;

use App\Filament\Operation\Resources\Internals\Riders\RiderResource;
use App\Models\Rider;
use Filament\Resources\Pages\CreateRecord;

class CreateRider extends CreateRecord
{
    protected static string $resource = RiderResource::class;


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $lastRider =  Rider::latest('id')->first();
        $nextNumber = $lastRider ? ((int) substr($lastRider->rider_code,4)) +1 : 1;
        $data['rider_code'] = 'RDR-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        return $data;
    }
}
