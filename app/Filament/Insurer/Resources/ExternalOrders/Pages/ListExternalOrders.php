<?php

namespace App\Filament\Insurer\Resources\ExternalOrders\Pages;

use App\Filament\Insurer\Resources\ExternalOrders\ExternalOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExternalOrders extends ListRecords
{
    protected static string $resource = ExternalOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->outlined()->icon('heroicon-o-bars-arrow-down'),
        ];
    }
}
