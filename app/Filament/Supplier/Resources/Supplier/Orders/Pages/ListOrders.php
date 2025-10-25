<?php

namespace App\Filament\Supplier\Resources\Supplier\Orders\Pages;

use App\Filament\Supplier\Resources\Supplier\Orders\OrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make()->icon('heroicon-o-plus-circle')->label('Create Order')->button(),
        ];
    }
}
