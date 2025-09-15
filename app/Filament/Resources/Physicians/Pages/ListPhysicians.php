<?php

namespace App\Filament\Resources\Physicians\Pages;

use App\Filament\Resources\Physicians\PhysicianResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPhysicians extends ListRecords
{
    protected static string $resource = PhysicianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
