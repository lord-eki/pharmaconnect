<?php

namespace App\Filament\Resources\Setups\Pages;

use App\Filament\Resources\Setups\SetupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSetups extends ManageRecords
{
    protected static string $resource = SetupResource::class;
    
    protected static bool $canCreateAnother = false;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-cog-6-tooth')->label('Add Setup'),
        ];
    }
}
