<?php

namespace App\Filament\Operation\Resources\Internals\Deliveries\Pages;

use App\Filament\Operation\Resources\Internals\Deliveries\DeliveryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDelivery extends EditRecord
{
    protected static string $resource = DeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->icon('heroicon-o-trash'),
        ];
    }
}
