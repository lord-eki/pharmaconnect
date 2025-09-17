<?php

namespace App\Filament\Resources\Physicians\Pages;

use App\Filament\Resources\Physicians\PhysicianResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPhysician extends EditRecord
{
    protected static string $resource = PhysicianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->icon('heroicon-o-trash'),
        ];
    }
}
