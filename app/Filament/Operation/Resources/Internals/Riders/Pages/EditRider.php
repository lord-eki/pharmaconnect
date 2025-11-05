<?php

namespace App\Filament\Operation\Resources\Internals\Riders\Pages;

use App\Filament\Operation\Resources\Internals\Riders\RiderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRider extends EditRecord
{
    protected static string $resource = RiderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
