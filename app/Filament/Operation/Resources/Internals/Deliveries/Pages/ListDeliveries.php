<?php

namespace App\Filament\Operation\Resources\Internals\Deliveries\Pages;

use App\Filament\Operation\Resources\Internals\Deliveries\DeliveryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDeliveries extends ListRecords
{
    protected static string $resource = DeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
