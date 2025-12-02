<?php

namespace App\Filament\Rider\Resources\Deliveries\Pages;

use App\Filament\Rider\Resources\Deliveries\DeliveryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDelivery extends ViewRecord
{
    protected static string $resource = DeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // EditAction::make(),
        ];
    }
}
