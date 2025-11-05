<?php

namespace App\Filament\Operation\Resources\Internals\Riders\Pages;

use App\Filament\Operation\Resources\Internals\Riders\RiderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRiders extends ListRecords
{
    protected static string $resource = RiderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->outlined()->icon('heroicon-o-truck'),
        ];
    }
}
