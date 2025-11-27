<?php

namespace App\Filament\Insurer\Resources\Insurance\InsurerOrders\Pages;

use App\Filament\Insurer\Resources\Insurance\InsurerOrders\InsurerOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInsurerOrders extends ListRecords
{
    protected static string $resource = InsurerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
